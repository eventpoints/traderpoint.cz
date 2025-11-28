<?php

namespace App\Controller\Controller;

use App\Entity\User;
use App\Enum\FlashEnum;
use App\Repository\EngagementRepository;
use App\Repository\SkillRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class ClientController extends AbstractController
{
    public function __construct(
        private readonly EngagementRepository $engagementRepository,
        private readonly PaginatorInterface $paginator,
        private readonly SkillRepository $skillRepository
    )
    {
    }

    #[Route(path: '/client/dashboard', name: 'client_dashboard', methods: ['GET', 'POST'])]
    public function clientDashboard(
        #[CurrentUser]
        User $currentUser,
        Request $request
    ): Response {
        $engagementsQuery = $this->engagementRepository->findByOwner($currentUser, true);
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 5);
        $pagination = $this->paginator->paginate($engagementsQuery, $page, $limit);
        $skills = $this->skillRepository->findPrimarySkillsForLandingPage();


        return $this->render('client/dashboard.html.twig', [
            'pagination' => $pagination,
            'skills' => $skills
        ]);
    }

    #[Route(path: '/client/profile/{id}', name: 'client_profile')]
    public function profile(User $user, Request $request): Response
    {
        return $this->render('client/profile.html.twig', [
            'user' => $user,
        ]);
    }
}