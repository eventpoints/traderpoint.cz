<?php

namespace App\Message\Message;

use Symfony\Component\Uid\Uuid;

final readonly class EngagementPostedMessage
{
    public function __construct(
        public Uuid $engagementId
    )
    {
    }

    public function getEngagementId(): Uuid
    {
        return $this->engagementId;
    }
}