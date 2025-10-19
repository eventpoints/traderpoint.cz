<?php

declare(strict_types=1);

namespace App\Event\Listener;

use App\Entity\Engagement;
use App\Message\Message\EngagementPostedMessage;
use App\Message\Message\EngagementTraderMatchAlert;
use App\Repository\EngagementRepository;
use App\Repository\TraderProfileRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEventListener(event: EngagementPostedMessage::class, method: '__invoke')]
final readonly class EngagementPostedListener
{
    public function __construct(
        private MessageBusInterface $bus,
        private EngagementRepository $engagements,
        private TraderProfileRepository $traderProfileRepository,
    )
    {
    }

    public function __invoke(EngagementPostedMessage $event): void
    {
        /** @var Engagement $engagement */
        $engagement = $this->engagements->find($event->engagementId);
        if (! $engagement) {
            return;
        }

        foreach ($this->traderProfileRepository->iterateTradersForEngagement($engagement, false) as $profile) {
            $this->bus->dispatch(new EngagementTraderMatchAlert(
                engagementId: $engagement->getId(),
                traderProfileId: $profile->getId()
            ));
        }
    }
}
