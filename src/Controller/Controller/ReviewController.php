<?php

declare(strict_types=1);

namespace App\Controller\Controller;

use App\Entity\Engagement;
use App\Entity\Review;
use App\Entity\User;
use App\Enum\FlashEnum;
use App\Form\Form\TraderReviewFormType;
use App\Service\EngagementWorkflowService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/review')]
class ReviewController extends AbstractController
{
    public function __construct(
        private readonly EngagementWorkflowService $workflowService,
        private readonly EntityManagerInterface    $entityManager,
        private readonly TranslatorInterface       $translator,
    )
    {
    }

    #[Route('/engagement/{id}/create', name: 'review_create', methods: ['GET', 'POST'])]
    #[IsGranted('REVIEW', 'engagement')]
    public function create(
        Engagement          $engagement,
        Request             $request,
        #[CurrentUser] User $currentUser
    ): Response
    {
        // Verify engagement is in AWAITING_REVIEW state
        if (!$this->workflowService->can($engagement, 'submit_review')) {
            $this->addFlash(
                FlashEnum::ERROR->value,
                $this->translator->trans('review.cannot_submit')
            );

            return $this->redirectToRoute('engagement_show', ['id' => $engagement->getId()]);
        }

        $quote = $engagement->getQuote();
        if ($quote === null) {
            throw $this->createNotFoundException('No accepted quote found for this engagement');
        }

        $review = new Review(
            target: $quote->getOwner()->getTraderProfile(),
            owner: $currentUser,
            engagement: $engagement
        );

        $reviewForm = $this->createForm(TraderReviewFormType::class, $review);

        $reviewForm->handleRequest($request);
        if ($reviewForm->isSubmitted() && $reviewForm->isValid()) {
            try {
                $this->entityManager->persist($review);
                $this->entityManager->flush();
                $this->workflowService->toReviewed($engagement, $review);

                $this->addFlash(
                    FlashEnum::SUCCESS->value,
                    $this->translator->trans('review.submitted_successfully')
                );

                return $this->redirectToRoute('client_show_engagement', ['id' => $engagement->getId()]);
            } catch (\LogicException $e) {
                $this->addFlash(
                    FlashEnum::ERROR->value,
                    $this->translator->trans('review.submission_failed', ['error' => $e->getMessage()])
                );
            }
        }

        return $this->render('review/create.html.twig', [
            'engagement' => $engagement,
            'quote' => $quote,
            'tradesman' => $quote->getOwner(),
            'reviewForm' => $reviewForm,
        ]);
    }
}
