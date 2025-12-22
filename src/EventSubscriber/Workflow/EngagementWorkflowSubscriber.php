<?php

declare(strict_types=1);

namespace App\EventSubscriber\Workflow;

use App\Entity\Engagement;
use Carbon\CarbonImmutable;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;

class EngagementWorkflowSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.engagement.completed' => ['onTransitionCompleted'],
        ];
    }

    public function onTransitionCompleted(CompletedEvent $event): void
    {
        /** @var Engagement $engagement */
        $engagement = $event->getSubject();
        $transitionName = $event->getTransition()->getName();

        // Update timestamps based on transition
        match ($transitionName) {
            'complete_work' => $this->handleWorkCompleted($engagement),
            'submit_review' => $this->handleReviewSubmitted($engagement),
            'cancel' => $this->handleCancellation($engagement),
            default => null,
        };
    }

    private function handleWorkCompleted(Engagement $engagement): void
    {
        // Set completion timestamp
        // Note: You may need to add a 'workCompletedAt' field to Engagement entity
        $engagement->setUpdatedAt(new CarbonImmutable());
    }

    private function handleReviewSubmitted(Engagement $engagement): void
    {
        // Set review timestamp
        // Note: You may need to add a 'reviewedAt' field to Engagement entity
        $engagement->setUpdatedAt(new CarbonImmutable());
    }

    private function handleCancellation(Engagement $engagement): void
    {
        // Set cancellation timestamp
        // Note: You may need to add a 'cancelledAt' field to Engagement entity
        $engagement->setUpdatedAt(new CarbonImmutable());
    }
}
