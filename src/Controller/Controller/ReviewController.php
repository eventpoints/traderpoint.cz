<?php

namespace App\Controller\Controller;

use App\Entity\Review;
use App\Entity\User;
use App\Enum\FlashEnum;
use App\Form\Form\TraderReviewFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

class ReviewController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    )
    {
    }

    #[Route(path: 'trader/{id}/review/create', name: 'trader_review_create')]
    #[isGranted('IS_AUTHENTICATED_FULLY')]
    public function createTraderReview(User $user, #[CurrentUser] User $currentUser): Response
    {
        if(! $user->getTraderProfile() instanceof \App\Entity\TraderProfile){
            $this->addFlash(FlashEnum::WARNING->value, $this->translator->trans('can-not-review-that-profile'));
            return $this->redirectToRoute('client_dashboard');
        }

        $review = new Review(target: $user->getTraderProfile(), owner: $currentUser);
        $reviewForm = $this->createForm(TraderReviewFormType::class, $review);

        return $this->render('review/trader/create.html.twig', [
            'user' => $user,
            'reviewForm' => $reviewForm,
        ]);
    }

    #[Route(path: 'client/{id}/review/create', name: 'client_review_create')]
    #[isGranted('IS_AUTHENTICATED_FULLY')]
    public function createClientReview(User $user): Response
    {

        return $this->render('review/client/create.html.twig', []);
    }
}