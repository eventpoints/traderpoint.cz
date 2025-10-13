<?php

namespace App\Controller\Controller;

use App\DataTransferObject\MapLocationDto;
use App\Entity\Engagement;
use App\Entity\Payment;
use App\Entity\Quote;
use App\Entity\User;
use App\Enum\CurrencyCodeEnum;
use App\Enum\FlashEnum;
use App\Enum\PaymentStatusEnum;
use App\Enum\PaymentTypeEnum;
use App\Form\Form\EngagementFormType;
use App\Form\Form\QuoteFormType;
use App\Repository\EngagementRepository;
use App\Repository\PaymentRepository;
use App\Repository\QuoteRepository;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
        private readonly PaymentRepository $paymentRepository,
        private readonly StripeClient $stripe
    )
    {
    }

    #[Route(path: 'trader/engagement/{id}', name: 'trader_show_engagement')]
    public function traderShow(Engagement $engagement, Request $request, #[CurrentUser] User $currentUser): Response
    {
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
            $this->quoteRepository->save(entity: $quote, flush: true);
            $this->addFlash(type: FlashEnum::SUCCESS->value, message: $this->translator->trans('flash.quote-successful'));
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
        $quotes = $this->quoteRepository->findByEngagement($engagement);
        return $this->render('engagement/client/show.html.twig', [
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

            /** @var MapLocationDto $mapLocationDto */
            $mapLocationDto = $engagementForm->get('location')->getData();
            $engagement->setLatitude($mapLocationDto->getLatitude());
            $engagement->setLongitude($mapLocationDto->getLatitude());
            $engagement->setAddress($mapLocationDto->getAddress());

            $this->engagementRepository->save(entity: $engagement, flush: true);

            $payment = new Payment(
                owner: $currentUser,
                engagement: $engagement,
                amountMinor: 9900,
                currency: CurrencyCodeEnum::CZK,
                type: PaymentTypeEnum::POSTING_FEE,
                status: PaymentStatusEnum::PENDING
            );
            $engagement->addPayment($payment);
            $this->engagementRepository->save($engagement, true);

            $base = $this->generateUrl('check_payment', [], UrlGeneratorInterface::ABSOLUTE_URL);

            $successUrl = $base . '?result=success&session_id={CHECKOUT_SESSION_ID}';
            $cancelUrl = $base . '?result=cancel&session_id={CHECKOUT_SESSION_ID}';

            // 3) Create Stripe Checkout Session
            $session = $this->stripe->checkout->sessions->create([
                'mode' => 'payment',
                'payment_method_types' => ['card'],
                'customer_email' => $currentUser->getEmail(),
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'czk',
                        'product_data' => [
                            'name' => 'Job posting fee',
                        ],
                        'unit_amount' => 9900,
                    ],
                    'quantity' => 1,
                ]],
                'metadata' => [
                    'payment_id' => (string) $payment->getId(),
                    'engagement_id' => (string) $engagement->getId(),
                    'user_id' => (string) $currentUser->getId(),
                ],
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'locale' => 'cs',
            ]);

            // 4) Store session ids on Payment
            $payment->setStripeCheckoutSessionId($session->id);
            $this->paymentRepository->save(entity: $payment, flush: true);
            return $this->redirect($session->url, 303);
        }

        return $this->render('engagement/create.html.twig', [
            'map' => $map,
            'engagementForm' => $engagementForm,
            'engagement' => $engagement,
        ]);
    }
}