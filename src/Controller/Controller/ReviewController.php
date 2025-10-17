<?php

namespace App\Controller\Controller;

use App\Entity\Quote;
use App\Entity\Review;
use App\Entity\User;
use App\Form\Form\TraderReviewFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ReviewController extends AbstractController
{
    #[Route(path: 'trader/{id}/review/create', name: 'trader_review_create')]
    #[isGranted('IS_AUTHENTICATED_FULLY')]
    public function createTraderReview(User $user, #[CurrentUser] User $currentUser): Response
    {
        $review = new Review(target: $user, owner: $currentUser);
        $reviewForm = $this->createForm(TraderReviewFormType::class, $review);

        return $this->render('review/trader/create.html.twig', [
            'user' => $user,
            'reviewForm' => $reviewForm
        ]);
    }

    #[Route(path: 'client/{id}/review/create', name: 'client_review_create')]
    #[isGranted('IS_AUTHENTICATED_FULLY')]
    public function createClientReview(User $user): Response
    {

        return $this->render('review/client/create.html.twig', []);
    }

}