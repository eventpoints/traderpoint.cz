<?php

declare(strict_types=1);

namespace App\Controller\Controller;

use App\Entity\Engagement;
use App\Entity\User;
use App\Enum\FlashEnum;
use App\Service\EngagementWorkflowService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/work-progress')]
class WorkProgressController extends AbstractController
{
    public function __construct(
        private readonly EngagementWorkflowService $workflowService,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/{id}/start', name: 'work_progress_start', methods: ['POST'])]
    #[IsGranted('START_WORK', 'engagement')]
    public function start(
        Engagement $engagement,
        #[CurrentUser]
        User $currentUser
    ): Response {
        try {
            $this->workflowService->startWork($engagement);

            $this->addFlash(
                FlashEnum::SUCCESS->value,
                $this->translator->trans('work.started_successfully')
            );
        } catch (\LogicException $e) {
            $this->addFlash(
                FlashEnum::ERROR->value,
                $this->translator->trans('work.cannot_start', [
                    'error' => $e->getMessage(),
                ])
            );
        }

        return $this->redirectToRoute('trader_show_engagement', [
            'id' => $engagement->getId(),
        ]);
    }

    #[Route('/{id}/complete', name: 'work_progress_complete', methods: ['POST'])]
    #[IsGranted('COMPLETE_WORK', 'engagement')]
    public function complete(
        Engagement $engagement,
        #[CurrentUser]
        User $currentUser
    ): Response {
        try {
            $this->workflowService->completeWork($engagement);

            // Automatically move to awaiting review
            if ($this->workflowService->can($engagement, 'request_review')) {
                $this->workflowService->requestReview($engagement);
            }

            $this->addFlash(
                FlashEnum::SUCCESS->value,
                $this->translator->trans('work.completed_successfully')
            );
        } catch (\LogicException $e) {
            $this->addFlash(
                FlashEnum::ERROR->value,
                $this->translator->trans('work.cannot_complete', [
                    'error' => $e->getMessage(),
                ])
            );
        }

        return $this->redirectToRoute('trader_show_engagement', [
            'id' => $engagement->getId(),
        ]);
    }
}
