<?php

namespace App\Controller\Controller;

use App\DataTransferObject\MapLocationDto;
use App\Entity\Engagement;
use App\Entity\EngagementIssue;
use App\Entity\Image;
use App\Entity\Message;
use App\Entity\Quote;
use App\Entity\Skill;
use App\Entity\User;
use App\Enum\FlashEnum;
use App\Enum\QuoteFilterEnum;
use App\Factory\ConversationFactory;
use App\Factory\UserFactory;
use App\Form\Form\EngagementFormType;
use App\Form\Form\EngagementIssueFormType;
use App\Form\Form\MessageFormType;
use App\Form\Form\QuoteFormType;
use App\Repository\ConversationRepository;
use App\Repository\EngagementRepository;
use App\Repository\QuoteRepository;
use App\Repository\ReactionRepository;
use App\Repository\SkillRepository;
use App\Repository\UserRepository;
use App\Security\Voter\EngagementVoter;
use App\Service\EmailService\EmailService;
use App\Service\EngagementWorkflowService;
use App\Service\ImageOptimizer;
use App\Verification\Sender\ElksSmsSender;
use Doctrine\Common\Collections\ArrayCollection;
use Nette\Utils\Strings;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Map\Bridge\Leaflet\LeafletOptions;
use Symfony\UX\Map\Bridge\Leaflet\Option\TileLayer;
use Symfony\UX\Map\Icon\Icon;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;

class EngagementController extends AbstractController
{
    private const MAX_QUOTES_PER_TRADER = 3;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly QuoteRepository $quoteRepository,
        private readonly EngagementRepository $engagementRepository,
        private readonly UserRepository $userRepository,
        private readonly ImageOptimizer $imageOptimizer,
        private readonly EmailService $emailService,
        private readonly SkillRepository $skillRepository,
        private readonly UserFactory $userFactory,
        private readonly Security $security,
        private readonly ReactionRepository $reactionRepository,
        private readonly ConversationRepository $conversationRepository,
        private readonly ConversationFactory $conversationFactory,
        private readonly ElksSmsSender $elksSmsSender,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly EngagementWorkflowService $workflowService,
    )
    {
    }

    #[Route(path: 'trader/engagement/{id}', name: 'trader_show_engagement')]
    public function traderShow(Engagement $engagement, Request $request, #[CurrentUser] User $currentUser): Response
    {
        $this->denyAccessUnlessGranted(EngagementVoter::TRADER_VIEW, $engagement);

        $tab = $request->query->get('tab', 'quote-form');
        $focusedMessageId = $request->query->get('focused');
        $reactions = $this->reactionRepository->findAll();

        $map = null;

        if ($engagement->getQuote() instanceof Quote) {
            $center = new Point($engagement->getLatitude(), $engagement->getLongitude());

            $map = (new Map('default'))
                ->center($center)
                ->zoom(20)
                ->options(
                    (new LeafletOptions())
                        ->tileLayer(new TileLayer(
                            url: 'https://api.maptiler.com/maps/streets-v2/{z}/{x}/{y}.png?key=1IDdEWmfCtjKNlJ6Ij3W',
                            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                            options: [
                                'maxZoom' => 25,
                                'tileSize' => 512,
                                'zoomOffset' => -1,
                            ]
                        ))
                );

            $map->addMarker(new Marker(
                position: $center,
                icon: Icon::svg('<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"><circle cx="9" cy="9" r="7" fill="#EC4E20" stroke="white" stroke-width="2"/></svg>')
            ));
        }

        [$conversation, $participant] = $this->conversationFactory
            ->getOrCreateForEngagement($engagement, $currentUser);
        $message = new Message($participant, $conversation);
        $messageForm = $this->createForm(MessageFormType::class, $message);
        $messageForm->handleRequest($request);

        if ($messageForm->isSubmitted() && $messageForm->isValid()) {
            $conversation->addMessage($message);
            $participant->addMessage($message);
            $this->conversationRepository->save($conversation, true);

            if ($engagement->getOwner() !== $currentUser) {
                $locale = $engagement->getOwner()->getPreferredLanguage() ?? 'cs';
                $this->emailService->sendEngagementMessageEmail(user: $engagement->getOwner(), locale: $locale, context: [
                    'engagement' => $engagement,
                    'tab' => 'questions',
                    'focused' => $message->getId(),
                ]);
            }

            return $this->redirectToRoute('trader_show_engagement', [
                'id' => $engagement->getId(),
                'tab' => 'questions',
                'focused' => $message->getId(),
            ]);
        }

        $quote = new Quote($engagement, $currentUser);
        $quoteForm = $this->createForm(QuoteFormType::class, $quote);
        $quoteForm->handleRequest($request);
        if ($quoteForm->isSubmitted() && $quoteForm->isValid()) {
            // Validate engagement is still accepting quotes
            if ($engagement->getStatus() !== \App\Enum\EngagementStatusEnum::RECEIVING_QUOTES) {
                $this->addFlash(FlashEnum::ERROR->value, $this->translator->trans('quote.error.engagement_not_accepting_quotes'));
                return $this->redirectToRoute('trader_show_engagement', [
                    'id' => $engagement->getId(),
                    'tab' => 'quote-form',
                ]);
            }

            // Validate no quote has been accepted yet
            if ($engagement->getQuote() instanceof \App\Entity\Quote) {
                $this->addFlash(FlashEnum::ERROR->value, $this->translator->trans('quote.error.quote_already_accepted'));
                return $this->redirectToRoute('trader_show_engagement', [
                    'id' => $engagement->getId(),
                    'tab' => 'quote-form',
                ]);
            }

            // Check maximum number of quotes per trader
            $traderQuoteCount = $engagement->getQuoteCountFor($currentUser);

            if ($traderQuoteCount >= self::MAX_QUOTES_PER_TRADER) {
                $this->addFlash(FlashEnum::ERROR->value, $this->translator->trans('quote.error.max_quotes_reached', [
                    'max' => self::MAX_QUOTES_PER_TRADER,
                ]));
                return $this->redirectToRoute('trader_show_engagement', [
                    'id' => $engagement->getId(),
                    'tab' => 'quote-form',
                ]);
            }

            // Set the version number for this quote (count + 1)
            $quote->setVersion($traderQuoteCount + 1);

            if($engagement->getOwner()->getNotificationSettings()->isClientReceiveEmailOnQuote()) {
                $locale = $engagement->getOwner()->getPreferredLanguage() ?? 'cs';
                $this->emailService->sendQuoteMadeEmail($engagement->getOwner(), $locale, [
                    'quote' => $quote,
                    'engagement' => $engagement,
                    'user' => $engagement->getOwner(),
                ]);
            }

            if($engagement->getOwner()->getNotificationSettings()->isClientReceiveSmsOnQuote()) {
                $locale = $engagement->getOwner()->getPreferredLanguage() ?? 'cs';
                $url = $this->urlGenerator->generate(
                    'client_show_engagement',
                    [
                        'id' => $engagement->getId(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
                $this->elksSmsSender->send($engagement->getOwner()->getPhoneNumber()->getE164(), $this->translator->trans(id: 'sms.client.engagement.new-quote', parameters: [
                    'url' => $url,
                ], domain: 'sms', locale: $locale));
                $this->emailService->sendQuoteMadeEmail($engagement->getOwner(), $locale, [
                    'quote' => $quote,
                    'engagement' => $engagement,
                    'user' => $engagement->getOwner(),
                ]);
            }

            $this->quoteRepository->save(entity: $quote, flush: true);
            $this->addFlash(type: FlashEnum::SUCCESS->value, message: $this->translator->trans(id: 'flash.quote-sent-successful', domain: 'flash'));
            return $this->redirectToRoute(route: 'trader_show_engagement', parameters: [
                'id' => $engagement->getId(),
                'tab' => 'quote-form',
            ]);
        }

        return $this->render('engagement/trader/show.html.twig', [
            'messageForm' => $messageForm,
            'reactions' => $reactions,
            'quoteForm' => $quoteForm,
            'engagement' => $engagement,
            'map' => $map,
            'tab' => $tab,
            'focused' => $focusedMessageId,
        ]);
    }

    #[Route(path: 'client/engagement/{id}', name: 'client_show_engagement')]
    public function clientShow(Engagement $engagement, Request $request, #[CurrentUser] User $currentUser): Response
    {
        $tab = $request->query->get('tab', 'quotes');
        $focusedMessageId = $request->query->get('focused');
        $quotesFilter = QuoteFilterEnum::tryFrom($request->query->get('qs', QuoteFilterEnum::ACTIVE->value)) ?? QuoteFilterEnum::ACTIVE;

        if ($currentUser->isTrader()) {
            $this->addFlash(FlashEnum::WARNING->value, $this->translator->trans('nice-try'));
            return $this->redirectToRoute('trader_show_engagement', [
                'id' => $engagement->getId(),
            ]);
        }

        $this->denyAccessUnlessGranted(EngagementVoter::CLIENT_VIEW, $engagement);

        $center = new Point($engagement->getLatitude(), $engagement->getLongitude());

        $map = (new Map('default'))
            ->center($center)
            ->zoom(20)
            ->options(
                (new LeafletOptions())
                    ->tileLayer(new TileLayer(
                        url: 'https://api.maptiler.com/maps/streets-v2/{z}/{x}/{y}.png?key=1IDdEWmfCtjKNlJ6Ij3W',
                        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                        options: [
                            'maxZoom' => 25,
                            'tileSize' => 512,
                            'zoomOffset' => -1,
                        ]
                    ))
            );

        $map->addMarker(new Marker(
            position: $center,
            icon: Icon::svg('<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"><circle cx="9" cy="9" r="7" fill="#EC4E20" stroke="white" stroke-width="2"/></svg>')
        ));

        [$conversation, $participant] = $this->conversationFactory
            ->getOrCreateForEngagement($engagement, $currentUser);
        $message = new Message($participant, $conversation);
        $messageForm = $this->createForm(MessageFormType::class, $message);
        $messageForm->handleRequest($request);

        if ($messageForm->isSubmitted() && $messageForm->isValid()) {
            $conversation->addMessage($message);
            $participant->addMessage($message);
            $this->conversationRepository->save($conversation, true);

            return $this->redirectToRoute('client_show_engagement', [
                'id' => $engagement->getId(),
                'tab' => 'questions',
                'focused' => $message->getId(),
            ]);
        }

        $quotes = $this->quoteRepository->findByEngagementAndFilter($engagement, $quotesFilter);
        return $this->render('engagement/client/show.html.twig', [
            'messageForm' => $messageForm,
            'map' => $map,
            'engagement' => $engagement,
            'quotes' => $quotes,
            'quotesFilter' => $quotesFilter,
            'tab' => $tab,
            'focused' => $focusedMessageId,
        ]);
    }

    #[Route(path: 'engagement/create', name: 'create_engagement')]
    public function create(Request $request, #[CurrentUser] null|User $currentUser = null): Response
    {
        if ($currentUser instanceof User && $currentUser->isTrader()) {
            $this->addFlash(FlashEnum::WARNING->value, $this->translator->trans('traders-can-not-create-engagements'));
            return $this->redirectToRoute('trader_dashboard');
        }

        $skills = new ArrayCollection();
        $skillId = $request->query->get('skill');
        if (! empty($skillId)) {
            $skillUuid = Uuid::fromString($skillId);
            $skill = $this->skillRepository->find($skillUuid);

            if (! $skill instanceof Skill) {
                return $this->redirectToRoute('landing');
            }

            $skills->add($skill);
        }

        $engagement = new Engagement();
        if ($currentUser instanceof User) {
            $engagement->setOwner($currentUser);
        }

        $map = (new Map('default'))
            ->center(new Point(50.07897895366278, 14.430823454571573))
            ->zoom(11)
            ->options(
                (new LeafletOptions())
                    ->tileLayer(new TileLayer(
                        url: 'https://api.maptiler.com/maps/streets-v2/{z}/{x}/{y}.png?key=1IDdEWmfCtjKNlJ6Ij3W',
                        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                        options: [
                            'maxZoom' => 25,
                            'tileSize' => 512,
                            'zoomOffset' => -1,
                        ]
                    ))
            );

        $engagementForm = $this->createForm(EngagementFormType::class, $engagement, [
            'map' => $map,
            'skills' => $skills,
            'is_edit' => false,
        ]);

        $engagementForm->handleRequest($request);
        if ($engagementForm->isSubmitted() && $engagementForm->isValid()) {

            if (! $currentUser instanceof User) {
                $email = $engagementForm->get('email')->getData();
                $firstName = $engagementForm->get('firstName')->getData();
                $lastName = $engagementForm->get('lastName')->getData();
                $currentUser = $this->userFactory->createClientUser(email: $email);
                $currentUser->setFirstName($firstName);
                $currentUser->setLastName($lastName);
                $engagement->setOwner($currentUser);
                $this->userRepository->save(entity: $currentUser, flush: true);
                $this->security->login($currentUser, 'security.authenticator.form_login.main');
            }

            $this->handleImageUpload(files: $engagementForm->get('images')->getData(), engagement: $engagement);

            /** @var MapLocationDto $mapLocationDto */
            $mapLocationDto = $engagementForm->get('location')->getData();
            $engagement->setLatitude($mapLocationDto->getLatitude());
            $engagement->setLongitude($mapLocationDto->getLongitude());
            $engagement->setAddress($mapLocationDto->getAddress());

            $this->engagementRepository->save(entity: $engagement, flush: true);

            $this->addFlash(FlashEnum::SUCCESS->value, $this->translator->trans('engagement.submitted_for_admin_review'));

            return $this->redirectToRoute('client_show_engagement', [
                'id' => $engagement->getId(),
            ]);
        }

        return $this->render('engagement/create.html.twig', [
            'map' => $map,
            'engagementForm' => $engagementForm,
            'engagement' => $engagement,
        ]);
    }

    #[Route(path: 'engagement/edit/{id}', name: 'edit_engagement')]
    public function edit(
        Engagement $engagement,
        Request $request,
        #[CurrentUser]
        ?User $currentUser = null
    ): Response
    {
        // 1) Decide what lat/lng to use for the map centre
        $latitude = $engagement->getLatitude();
        $longitude = $engagement->getLongitude();

        if ($latitude === null || $longitude === null) {
            // fallback for older jobs / first-time creation
            // (Prague-ish, or whatever you want)
            $latitude = 50.07897895366278;
            $longitude = 14.430823454571573;
        }

        $map = (new Map('default'))
            ->center(new Point($latitude, $longitude))
            ->zoom(11)
            ->options(
                (new LeafletOptions())
                    ->tileLayer(new TileLayer(
                        url: 'https://api.maptiler.com/maps/streets-v2/{z}/{x}/{y}.png?key=1IDdEWmfCtjKNlJ6Ij3W',
                        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                        options: [
                            'maxZoom' => 25,
                            'tileSize' => 512,
                            'zoomOffset' => -1,
                        ]
                    ))
            );

        $engagementForm = $this->createForm(EngagementFormType::class, $engagement, [
            'map' => $map,
            'skills' => $engagement->getSkills(),
            'is_edit' => true,
        ]);

        $engagementForm->handleRequest($request);
        if ($engagementForm->isSubmitted() && $engagementForm->isValid()) {
            /** @var MapLocationDto $mapLocationDto */
            $mapLocationDto = $engagementForm->get('location')->getData();
            $engagement->setLatitude($mapLocationDto->getLatitude());
            $engagement->setLongitude($mapLocationDto->getLongitude());
            $engagement->setAddress($mapLocationDto->getAddress());

            $this->handleImageUpload(files: $engagementForm->get('images')->getData(), engagement: $engagement);

            $this->engagementRepository->save(entity: $engagement, flush: true);

            return $this->redirectToRoute('client_show_engagement', [
                'id' => $engagement->getId(),
            ]);
        }

        return $this->render('engagement/create.html.twig', [
            'map' => $map,
            'engagementForm' => $engagementForm,
            'engagement' => $engagement,
        ]);
    }

    #[Route(path: 'engagement/issue/{quote}/{user}', name: 'engagement_issue')]
    public function engagementIssue(Quote $quote, User $user, Request $request, #[CurrentUser] User $currentUser): Response
    {
        $engagement = $quote->getEngagement();
        $engagementIssue = new EngagementIssue(engagement: $engagement, owner: $currentUser, target: $user, quote: $quote);
        $engagementIssueForm = $this->createForm(EngagementIssueFormType::class, $engagementIssue);

        $engagementIssueForm->handleRequest($request);

        if ($engagementIssueForm->isSubmitted() && $engagementIssueForm->isValid()) {
            try {
                // Use workflow to raise issue and transition engagement state
                $this->workflowService->raiseIssue($engagement, $engagementIssue);
                $this->addFlash(FlashEnum::SUCCESS->value, $this->translator->trans(id: 'flash.issue-created', domain: 'flash'));
            } catch (\LogicException $e) {
                $this->addFlash(FlashEnum::ERROR->value, $this->translator->trans('issue.cannot_raise', [
                    'error' => $e->getMessage(),
                ]));
            }

            return $this->redirectToRoute('client_show_engagement', [
                'id' => $engagement->getId(),
            ]);
        }

        return $this->render('engagement/issue.html.twig', [
            'engagementIssue' => $engagementIssue,
            'engagementIssueForm' => $engagementIssueForm,
        ]);
    }

    /**
     * @param UploadedFile[] $files
     */
    private function handleImageUpload(array $files, Engagement $engagement): void
    {
        if ($files === []) {
            return;
        }

        $files = array_slice(array_values($files), 0, 4);

        foreach ($engagement->getImages()->toArray() as $existing) {
            $engagement->removeImage($existing);
        }

        foreach ($files as $idx => $file) {
            $optimisedFile = $this->imageOptimizer->getOptimizedFile($file);

            $img = new Image();
            $img->setImageFile($optimisedFile);
            $img->setEngagement($engagement);
            $img->setPosition($idx + 1);

            $engagement->addImage($img);
        }
    }

    #[Route(path: 'engagement/delete/{id}', name: 'client_delete_engagement')]
    public function delete(Engagement $engagement, #[CurrentUser] User $currentUser): Response
    {
        if (Strings::compare($currentUser->getId(), $engagement->getOwner()->getId())) {
            $engagement->setIsDeleted(isDeleted: true);
            $this->engagementRepository->save(entity: $engagement, flush: true);
            $this->addFlash(FlashEnum::SUCCESS->value, $this->translator->trans(id: 'flash.engagement-deleted', domain: 'flash'));
            return $this->redirectToRoute('client_dashboard');
        } else {
            $this->security->logout();
            return $this->redirectToRoute('app_login');
        }
    }
}