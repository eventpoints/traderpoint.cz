<?php

namespace App\Controller\Controller;

use App\Entity\Review;
use App\Entity\User;
use App\Enum\FlashEnum;
use App\Form\Form\ReviewFormType;
use App\Repository\ReviewRepository;
use App\Service\Rating\OverallRatingCalculator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/reviews')]
class ReviewController extends AbstractController
{
    public function __construct(
        private readonly ReviewRepository $reviewRepository,
        private readonly TranslatorInterface $translator,
        private readonly OverallRatingCalculator $overallRatingCalculator
    )
    {
    }

    #[Route(path: '/create/{id}', name: 'create_review')]
    public function create(User $reviewee, #[CurrentUser] User $currentUser, Request $request): Response
    {
        $review = new Review(reviewer: $currentUser, reviewee: $reviewee);
        $reviewForm = $this->createForm(type: ReviewFormType::class, data: $review);
        $reviewForm->handleRequest($request);

        if ($reviewForm->isSubmitted() && $reviewForm->isValid()) {
            $overallRating = $this->overallRatingCalculator->calculate(responseRating: (float) $review->getResponseRating(), customerServicesRating: (float) $review->getCustomerServicesRating(), workQualityRating: (float) $review->getWorkQualityRating(), valueForMoneyRating: (float) $review->getValueForMoneyRating());
            $review->setOverallRating($overallRating);
            $this->reviewRepository->save(entity: $review, flush: true);
            $this->addFlash(type: FlashEnum::SUCCESS->value, message: $this->translator->trans('flash.review-successful'));
            return $this->redirectToRoute(route: 'show_service', parameters: [
                'id' => $reviewee->getId(),
            ]);
        }

        return $this->render('review/create.html.twig', [
            'reviewForm' => $reviewForm,
            'review' => $review,
        ]);

    }
}