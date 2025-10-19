<?php
declare(strict_types=1);

namespace App\Event\Listener;

use App\Entity\Engagement;
use App\Entity\TraderProfile;
use App\Message\Message\EngagementPostedMessage;
use App\Message\Message\EngagementTraderMatchAlert;
use App\Repository\EngagementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEventListener(event: EngagementPostedMessage::class, method: '__invoke')]
final readonly class EngagementPostedListener
{
    public function __construct(
        private EntityManagerInterface $em,
        private MessageBusInterface    $bus,
        private EngagementRepository   $engagements,
    )
    {
    }

    public function __invoke(EngagementPostedMessage $event): void
    {
        /** @var Engagement $engagement */
        $engagement = $this->engagements->find($event->engagementId);
        if (!$engagement) {
            return;
        }

        // stream matching traders (ANY-skill within per-trader radius)
        foreach ($this->em->getRepository(TraderProfile::class)
                     ->iterateTradersForEngagement($engagement,false) as $profile) {

            $this->bus->dispatch(new EngagementTraderMatchAlert(
                engagementId: $engagement->getId(),
                traderProfileId: $profile->getId()
            ));
        }
    }

}
