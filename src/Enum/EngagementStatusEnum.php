<?php

namespace App\Enum;

enum EngagementStatusEnum: string
{
    case UNDER_ADMIN_REVIEW = 'UNDER_ADMIN_REVIEW';
    case REJECTED = 'REJECTED';
    case RECEIVING_QUOTES = 'RECEIVING_QUOTES';
    case QUOTE_ACCEPTED = 'QUOTE_ACCEPTED';
    case IN_PROGRESS = 'IN_PROGRESS';
    case ISSUE_RESOLUTION = 'ISSUE_RESOLUTION';
    case WORK_COMPLETED = 'WORK_COMPLETED';
    case AWAITING_REVIEW = 'AWAITING_REVIEW';
    case REVIEWED = 'REVIEWED';
    case CANCELLED = 'CANCELLED';

    public static function getActiveStatusesForTrader() : array
    {
        return [EngagementStatusEnum::RECEIVING_QUOTES, EngagementStatusEnum::QUOTE_ACCEPTED, EngagementStatusEnum::IN_PROGRESS, EngagementStatusEnum::ISSUE_RESOLUTION];
    }

    public static function getHistoricalStatusesForTrader() : array
    {
        return [EngagementStatusEnum::REJECTED, EngagementStatusEnum::WORK_COMPLETED, EngagementStatusEnum::REVIEWED, EngagementStatusEnum::CANCELLED];
    }

    public static function getActiveStatusesForClient() : array
    {
        return [EngagementStatusEnum::UNDER_ADMIN_REVIEW, EngagementStatusEnum::RECEIVING_QUOTES, EngagementStatusEnum::QUOTE_ACCEPTED, EngagementStatusEnum::IN_PROGRESS, EngagementStatusEnum::ISSUE_RESOLUTION, EngagementStatusEnum::WORK_COMPLETED];
    }

    public static function getHistoricalStatusesForClient() : array
    {
        return [EngagementStatusEnum::REJECTED, EngagementStatusEnum::REVIEWED, EngagementStatusEnum::CANCELLED];
    }
}
