<?php

namespace App\Controller\Controller;

use App\Entity\User;
use App\Form\Filter\UserFilterType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{

    public function __construct(
        private readonly UserRepository $userRepository
    )
    {
    }

    #[Route(path: '/', name: 'services')]
    public function index(Request $request): Response
    {
        $userFilterForm = $this->createForm(UserFilterType::class);
        $userFilterForm->handleRequest($request);

        if ($userFilterForm->isSubmitted() && $userFilterForm->isValid()) {
            $users = $this->userRepository->findByUserFilterDto(userFilterDto: $userFilterForm->getData());


            return $this->render('app/index.html.twig', [
                'userFilterForm' => $userFilterForm,
                'users' => $users
            ]);
        }

        return $this->render('app/index.html.twig', [
            'userFilterForm' => $userFilterForm,
            'users' => []
        ]);
    }

    #[Route(path: '/show/{id}', name: 'show_service')]
    public function show(User $user, Request $request): Response
    {
        $averageRating = $this->userRepository->findOneAverageRatingByUser($user);

        return $this->render('user/shows.html.twig', [
            'user' => $user,
            'averageRating' => round($averageRating, 1)
        ]);
    }


}