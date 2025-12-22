<?php

declare(strict_types=1);

namespace App\EventSubscriber\Workflow;

use App\Entity\Engagement;
use App\Service\EmailService\EmailService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;

class WorkCompletedNotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EmailService $emailService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.engagement.completed.complete_work' => ['onWorkCompleted'],
        ];
    }

    public function onWorkCompleted(CompletedEvent $event): void
    {
        /** @var Engagement $engagement */
        $engagement = $event->getSubject();

        // Notify engagement owner that work is complete
        $this->emailService->sendWorkCompletedEmail(
            $engagement->getOwner(),
            $engagement
        );
    }
}
