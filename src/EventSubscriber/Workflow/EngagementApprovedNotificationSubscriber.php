<?php

declare(strict_types=1);

namespace App\EventSubscriber\Workflow;

use App\Entity\Engagement;
use App\Message\Message\EngagementPostedMessage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;

class EngagementApprovedNotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.engagement.completed.approve' => ['onEngagementApproved'],
        ];
    }

    public function onEngagementApproved(CompletedEvent $event): void
    {
        /** @var Engagement $engagement */
        $engagement = $event->getSubject();

        // Dispatch message to notify matching traders about new engagement
        $this->messageBus->dispatch(new EngagementPostedMessage($engagement->getId()));
    }
}
