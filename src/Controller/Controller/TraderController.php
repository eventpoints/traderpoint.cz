<?php

namespace App\Controller\Controller;

use App\Entity\User;
use App\Enum\EngagementStatusEnum;
use App\Enum\EngagementStatusGroupEnum;
use App\Repository\EngagementRepository;
use App\Repository\QuoteRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route(path: '/trader')]
class TraderController extends AbstractController
{
    public function __construct(
        private readonly EngagementRepository $engagementRepository,
        private readonly PaginatorInterface   $paginator,
        private readonly QuoteRepository      $quoteRepository,
    )
    {
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
        if (!$currentUser->isTrader()) {
            return $this->redirectToRoute('client_dashboard');
        }

        // If tab parameter is not present, redirect to include it
        if (!$request->query->has('tab')) {
            return $this->redirectToRoute('trader_dashboard', [
                'tab' => EngagementStatusGroupEnum::DISCOVER->value,
            ]);
        }

        $tabParam = $request->query->getString('tab');
        $statusGroup = EngagementStatusGroupEnum::tryFrom($tabParam);
        if ($statusGroup === EngagementStatusGroupEnum::PENDING) {
            $engagementsQuery = $this->engagementRepository->findByPendingQuoteForTrader(
                user: $currentUser,
                isQuery: true
            );
        } else if ($statusGroup === EngagementStatusGroupEnum::HISTORICAL) {
            // Default to historical (includes COMPLETED, CANCELLED, etc.)
            $engagementStatusEnum = EngagementStatusEnum::tryFrom($tabParam);
            $engagementsQuery = $this->engagementRepository->findHistoricalForTrader(
                user: $currentUser,
                isQuery: true,
                engagementStatusEnum: $engagementStatusEnum
            );
        } else {
            $engagementsQuery = $this->engagementRepository->findUpcomingBySkillsAndLocation(
                user: $currentUser,
                isQuery: true
            );
        }

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

    #[Route(path: '/trader/profile/{id}', name: 'trader_profile')]
    public function profile(User $user, Request $request): Response
    {
        return $this->render('trader/profile.html.twig', [
            'user' => $user,
        ]);
    }
}