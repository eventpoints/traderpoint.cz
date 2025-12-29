<?php

declare(strict_types=1);

namespace App\EventSubscriber\Workflow;

use App\Entity\Engagement;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;

class WorkCompletedNotificationSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.engagement.completed.complete_work' => ['onWorkCompleted'],
        ];
    }

    public function onWorkCompleted(CompletedEvent $event): void
    {
        $event->getSubject();

        // TODO: Implement sendWorkCompletedEmail method in EmailService
        // Notify engagement owner that work is complete
        // $this->emailService->sendWorkCompletedEmail(
        //     $engagement->getOwner(),
        //     $engagement
        // );
    }
}
