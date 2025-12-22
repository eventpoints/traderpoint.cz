<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Enum\EngagementStatusEnum;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class EngagementStatusBadge
{
    public EngagementStatusEnum $status;
    public string $size = 'sm';

    public function getVariant(): string
    {
        return match ($this->status) {
            EngagementStatusEnum::UNDER_ADMIN_REVIEW => 'warning',
            EngagementStatusEnum::RECEIVING_QUOTES => 'success',
            EngagementStatusEnum::QUOTE_ACCEPTED => 'info',
            EngagementStatusEnum::IN_PROGRESS => 'primary',
            EngagementStatusEnum::ISSUE_RESOLUTION => 'danger',
            EngagementStatusEnum::WORK_COMPLETED => 'success',
            EngagementStatusEnum::AWAITING_REVIEW => 'warning',
            EngagementStatusEnum::REVIEWED => 'success',
            EngagementStatusEnum::REJECTED => 'danger',
            EngagementStatusEnum::CANCELLED => 'secondary',
        };
    }

    public function getText(): string
    {
        return match ($this->status) {
            EngagementStatusEnum::UNDER_ADMIN_REVIEW => 'engagement.status.under-review',
            EngagementStatusEnum::RECEIVING_QUOTES => 'engagement.status.receiving-quotes',
            EngagementStatusEnum::QUOTE_ACCEPTED => 'engagement.status.quote-accepted',
            EngagementStatusEnum::IN_PROGRESS => 'engagement.status.in-progress',
            EngagementStatusEnum::ISSUE_RESOLUTION => 'engagement.status.issue-resolution',
            EngagementStatusEnum::WORK_COMPLETED => 'engagement.status.work-completed',
            EngagementStatusEnum::AWAITING_REVIEW => 'engagement.status.awaiting-review',
            EngagementStatusEnum::REVIEWED => 'engagement.status.reviewed',
            EngagementStatusEnum::REJECTED => 'engagement.status.rejected',
            EngagementStatusEnum::CANCELLED => 'engagement.status.cancelled',
        };
    }
}
