<?php

declare(strict_types=1);

namespace App\Event\Listener;

use App\Entity\NotificationDelivery;
use App\Event\Event\NotificationSentEvent;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(
    event: NotificationSentEvent::class,
    method: 'onNotificationSent',
    priority: 0
)]
final readonly class NotificationTrackingListener
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private EntityManagerInterface $em,
    ) {}

    public function onNotificationSent(NotificationSentEvent $event): void
    {
        // 1) Logical notification (grouping)
        $notification = $this->notificationRepository
            ->findOrCreateForUserTypeAndDedupeKey(
                $event->user,
                $event->type,
                $event->dedupeKey,
                $event->locale,
                $event->context
            );

        if ($notification->getId() === null) {
            $this->em->persist($notification);
        }

        // 2) Delivery entry (email / sms / etc.)
        $delivery = new NotificationDelivery(
            $notification,
            $event->channel,
            $event->template,
            $event->deliveryPayload
        );
        $this->em->persist($delivery);

        if ($event->success) {
            $delivery->markSent($event->providerMessageId);
        } else {
            $delivery->markFailed($event->errorMessage ?? 'Unknown error');
        }

        $this->em->flush();
    }
}
