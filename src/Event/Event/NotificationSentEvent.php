<?php

declare(strict_types=1);

namespace App\Event\Event;

use App\Entity\User;
use App\Enum\NotificationChannelEnum;
use App\Enum\NotificationTypeEnum;

final readonly class NotificationSentEvent
{
    /**
     * @param array<string, string> $context
     * @param array<string, string> $deliveryPayload
     */
    public function __construct(
        public User $user,
        public NotificationTypeEnum $type,
        public NotificationChannelEnum $channel,
        public string $locale,
        public ?string $dedupeKey = null,
        public ?string $template = null,
        public array $context = [],
        public array $deliveryPayload = [],
        public bool $success = true,
        public ?string $providerMessageId = null,
        public ?string $errorMessage = null,
    )
    {
    }
}
