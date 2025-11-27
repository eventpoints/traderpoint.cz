<?php

namespace App\Message\Message;

use Symfony\Component\Uid\Uuid;

final readonly class EngagementTraderMatchNotification
{
    public function __construct(
        private Uuid $engagementId,
        private Uuid $traderProfileId
    )
    {
    }

    public function getEngagementId(): Uuid
    {
        return $this->engagementId;
    }

    public function getTraderProfileId(): Uuid
    {
        return $this->traderProfileId;
    }
}