<?php

namespace App\Controller\Controller;

use App\DataTransferObject\MapLocationDto;
use App\Entity\Engagement;
use App\Entity\Image;
use App\Entity\Quote;
use App\Entity\User;
use App\Enum\FlashEnum;
use App\Form\Form\EngagementFormType;
use App\Form\Form\QuoteFormType;
use App\Message\Message\EngagementPostedMessage;
use App\Repository\EngagementRepository;
use App\Repository\QuoteRepository;
use App\Repository\UserRepository;
use App\Security\Voter\EngagementVoter;
use App\Service\EmailService\EmailService;
use App\Service\ImageOptimizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Map\Bridge\Leaflet\LeafletOptions;
use Symfony\UX\Map\Bridge\Leaflet\Option\TileLayer;
use Symfony\UX\Map\Icon\Icon;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;

class EngagmentController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly QuoteRepository $quoteRepository,
        private readonly EngagementRepository $engagementRepository,
        private readonly UserRepository $userRepository,
        private readonly ImageOptimizer $imageOptimizer,
        private readonly EmailService $emailService,
        private readonly EventDispatcherInterface $dispatcher
    )
    {
    }

    #[Route(path: 'trader/engagement/{id}', name: 'trader_show_engagement')]
    public function traderShow(Engagement $engagement, Request $request, #[CurrentUser] User $currentUser): Response
    {
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
            ]);
        }

        return $this->render('engagement/trader/show.html.twig', [
            'quoteForm' => $quoteForm,
            'engagement' => $engagement,
            'map' => $map,
        ]);
    }

    #[Route(path: 'client/engagement/{id}', name: 'client_show_engagement')]
    public function clientShow(Engagement $engagement, Request $request, #[CurrentUser] User $currentUser): Response
    {
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

        $quotes = $this->quoteRepository->findByEngagement($engagement);
        return $this->render('engagement/client/show.html.twig', [
            'map' => $map,
            'engagement' => $engagement,
            'quotes' => $quotes,
        ]);
    }

    #[Route(path: 'engagement/create', name: 'create_engagement')]
    public function create(Request $request, #[CurrentUser] User $currentUser): Response
    {
        $engagement = new Engagement(owner: $currentUser);

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
        ]);
        $engagementForm->handleRequest($request);
        if ($engagementForm->isSubmitted() && $engagementForm->isValid()) {

            if ($engagementForm->has('phoneNumber')) {
                $phoneNumber = $engagementForm->get('phoneNumber')->getData();
                $currentUser->setPhoneNumber($phoneNumber);
                $this->userRepository->save(entity: $currentUser, flush: true);
            }

            /** @var UploadedFile[] $files */
            $files = $engagementForm->get('images')->getData() ?? [];

            $positionBase = count($engagement->getImages());
            foreach ($files as $idx => $file) {
                $optimisedFile = $this->imageOptimizer->getOptimizedFile($file);
                $img = new Image();
                $img->setImageFile($optimisedFile);
                $img->setEngagement($engagement);
                $img->setPosition($positionBase + $idx + 1);
                $engagement->addImage($img);
            }

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
}