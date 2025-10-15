<?php

namespace App\Controller\Controller;

use App\Entity\User;
use App\Form\Form\AccountFormType;
use App\Form\Form\TraderAccountFormType;
use App\Repository\EngagementRepository;
use App\Repository\QuoteRepository;
use App\Repository\TraderProfileRepository;
use App\Repository\UserRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\UX\Map\Bridge\Leaflet\LeafletOptions;
use Symfony\UX\Map\Bridge\Leaflet\Option\TileLayer;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Point;

#[Route(path: '/trader')]
class TraderController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EngagementRepository $engagementRepository,
        private readonly PaginatorInterface $paginator,
        private readonly TraderProfileRepository $traderProfileRepository,
        private readonly QuoteRepository $quoteRepository,
    )
    {
    }

    #[Route(path: '/traders', name: 'trader_index')]
    public function index(Request $request): Response
    {
        return $this->render('trader/landing.html.twig');
    }

    #[Route(path: '/trader/quotes', name: 'trader_quotes')]
    public function sent(Request $request, #[CurrentUser] User $currentUser): Response
    {
        $quotes = $this->quoteRepository->findBy([
            'owner' => $currentUser,
        ]);
        return $this->render('trader/quotes.html.twig', [
            'quotes' => $quotes,
        ]);
    }

    #[Route(path: '/dashboard', name: 'trader_dashboard')]
    public function dashboard(#[CurrentUser] User $currentUser, Request $request): Response
    {

        if (! $currentUser->isTrader()) {
            return $this->redirectToRoute('client_dashboard');
        }

        $engagementsQuery = $this->engagementRepository->findUpcomingBySkillsAndLocation(
            user: $currentUser,
            isQuery: true
        );

        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 5);

        $pagination = $this->paginator->paginate(target: $engagementsQuery, page: $page, limit: $limit);

        return $this->render('trader/dashboard.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route(path: '/conversations', name: 'user_conversations', methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function conversations(Request $request, #[CurrentUser] User $currentUser): Response
    {
        return $this->render('user/conversations.html.twig');
    }

    #[Route(path: '/account', name: 'trader_account', methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function account(Request $request, #[CurrentUser] User $currentUser): Response
    {
        $traderProfile = $currentUser->getTraderProfile();
        $latitude = $traderProfile->getLatitude() ?: 50.07897895366278;
        $longitude = $traderProfile->getLongitude() ?: 14.430823454571573;
        $map = (new Map('default'))
            ->center(new Point($latitude, $longitude))
            ->zoom(11)
            ->options(
                (new LeafletOptions())
                    ->tileLayer(new TileLayer(
                        url: 'https://api.maptiler.com/maps/streets-v2/{z}/{x}/{y}.png?key=1IDdEWmfCtjKNlJ6Ij3W',
                        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                        options: [
                            'fitBounds' => 'true',
                            'maxZoom' => 25,
                            'tileSize' => 512,
                            'zoomOffset' => -1,
                        ]
                    ))
            );

        $traderAccountForm = $this->createForm(TraderAccountFormType::class, $currentUser->getTraderProfile(), [
            'map' => $map,
        ]);
        $traderAccountForm->handleRequest($request);
        if ($traderAccountForm->isSubmitted() && $traderAccountForm->isValid()) {
            $location = $traderAccountForm->get('location')->getData();
            $currentUser->getTraderProfile()->setLatitude($location->getLatitude());
            $currentUser->getTraderProfile()->setLongitude($location->getLongitude());
            $currentUser->getTraderProfile()->setServiceRadius($location->getRadiusKm());
            $currentUser->getTraderProfile()->setAddress($location->getAddress());
            $this->traderProfileRepository->save(entity: $currentUser->getTraderProfile(), flush: true);
            return $this->redirectToRoute('trader_account');
        }

        $accountForm = $this->createForm(AccountFormType::class, $currentUser);

        $accountForm->handleRequest($request);
        if ($accountForm->isSubmitted() && $accountForm->isValid()) {
            $this->userRepository->save(entity: $currentUser, flush: true);
            return $this->redirectToRoute('user_account');
        }

        return $this->render('trader/account.html.twig', [
            'accountForm' => $accountForm,
            'traderAccountForm' => $traderAccountForm,
        ]);
    }

    #[Route(path: '/trader/profile/{id}', name: 'trader_profile')]
    public function profile(User $user, Request $request): Response
    {
        return $this->render('trader/profile.html.twig', [
            'user' => $user,
        ]);
    }
}