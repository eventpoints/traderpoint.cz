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
use App\Factory\ConversationFactory;
use App\Factory\UserFactory;
use App\Form\Form\EngagementFormType;
use App\Form\Form\EngagementIssueFormType;
use App\Form\Form\MessageFormType;
use App\Form\Form\QuoteFormType;
use App\Message\Message\EngagementPostedMessage;
use App\Repository\ConversationRepository;
use App\Repository\EngagementRepository;
use App\Repository\QuoteRepository;
use App\Repository\ReactionRepository;
use App\Repository\SkillRepository;
use App\Repository\UserRepository;
use App\Security\Voter\EngagementVoter;
use App\Service\EmailService\EmailService;
use App\Service\ImageOptimizer;
use Doctrine\Common\Collections\ArrayCollection;
use Nette\Utils\Strings;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly QuoteRepository $quoteRepository,
        private readonly EngagementRepository $engagementRepository,
        private readonly UserRepository $userRepository,
        private readonly ImageOptimizer $imageOptimizer,
        private readonly EmailService $emailService,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly SkillRepository $skillRepository,
        private readonly UserFactory $userFactory,
        private readonly Security $security,
        private readonly ReactionRepository $reactionRepository,
        private readonly ConversationRepository $conversationRepository,
        private readonly ConversationFactory $conversationFactory
    )
    {
    }

    #[Route(path: 'trader/engagement/{id}', name: 'trader_show_engagement')]
    public function traderShow(Engagement $engagement, Request $request, #[CurrentUser] User $currentUser): Response
    {
        $tab = $request->query->get('tab', 'quote-form');
        $focusedMessageId = $request->query->get('focused');

        $reactions = $this->reactionRepository->findAll();
        $this->denyAccessUnlessGranted(EngagementVoter::TRADER_VIEW, $engagement);

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

            $locale = $engagement->getOwner()->getPreferredLanguage() ?? 'cs';

            $this->emailService->sendQuoteMadeEmail($engagement->getOwner(), $locale, [
                'quote' => $quote,
                'engagement' => $engagement,
                'user' => $engagement->getOwner(),
            ]);

            $this->quoteRepository->save(entity: $quote, flush: true);
            $this->addFlash(type: FlashEnum::SUCCESS->value, message: $this->translator->trans(id: 'flash.quote-sent-successful', domain: 'flash'));
            return $this->redirectToRoute(route: 'trader_show_engagement', parameters: [
                'id' => $engagement->getId(),
                'tab' => 'questions',
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

        $quotes = $this->quoteRepository->findByEngagement($engagement);
        return $this->render('engagement/client/show.html.twig', [
            'messageForm' => $messageForm,
            'map' => $map,
            'engagement' => $engagement,
            'quotes' => $quotes,
            'tab' => $tab,
            'focused' => $focusedMessageId,
        ]);
    }

    #[Route(path: 'engagement/create', name: 'create_engagement')]
    public function create(Request $request, #[CurrentUser] null|User $currentUser = null): Response
    {
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
            $this->dispatcher->dispatch(new EngagementPostedMessage(engagementId: $engagement->getId()));

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
        $engagementIssue = new EngagementIssue(engagement: $quote->getEngagement(), owner: $currentUser, target: $user, quote: $quote);
        $engagementIssueForm = $this->createForm(EngagementIssueFormType::class, $engagementIssue);

        $engagementIssueForm->handleRequest($request);

        if ($engagementIssueForm->isSubmitted() && $engagementIssueForm->isValid()) {
            $this->engagementRepository->save($quote->getEngagement(), true);
            $this->addFlash(FlashEnum::SUCCESS->value, $this->translator->trans(id: 'flash.issue-created', domain: 'flash'));
            return $this->redirectToRoute('client_show_engagement', [
                'id' => $quote->getEngagement()->getId(),
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