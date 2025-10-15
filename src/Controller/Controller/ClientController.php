<?php

namespace App\Controller\Controller;

use App\Entity\User;
use App\Form\Form\AccountFormType;
use App\Repository\EngagementRepository;
use App\Repository\UserRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class ClientController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EngagementRepository $engagementRepository,
        private readonly PaginatorInterface $paginator
    )
    {
    }

    #[Route(path: '/client/dashboard', name: 'client_dashboard')]
    public function clientDashboard(#[CurrentUser] User $currentUser, Request $request): Response
    {
        $engagementsQuery = $this->engagementRepository->findByOwner($currentUser, true);

        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 5);
        $pagination = $this->paginator->paginate(target: $engagementsQuery, page: $page, limit: $limit);

        return $this->render('client/dashboard.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route(path: '/client/profile/{id}', name: 'client_profile')]
    public function profile(User $user, Request $request): Response
    {
        return $this->render('client/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route(path: '/account', name: 'user_account', methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function account(Request $request, #[CurrentUser] User $currentUser): Response
    {
        $accountForm = $this->createForm(AccountFormType::class, $currentUser);

        $accountForm->handleRequest($request);
        if ($accountForm->isSubmitted() && $accountForm->isValid()) {
            $this->userRepository->save(entity: $currentUser, flush: true);
            return $this->redirectToRoute('user_account');
        }

        return $this->render('client/account.html.twig', [
            'accountForm' => $accountForm,
        ]);
    }
}