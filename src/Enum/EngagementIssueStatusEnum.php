<?php

namespace App\Enum;

enum EngagementIssueStatusEnum: string
{
    case ACTIVE = 'active';
    case AWAITING_OTHER_PARTY = 'awaiting_other_party';
    case AWAITING_SUPPORT = 'awaiting_support';
    case RESOLVED = 'resolved';
    case DISMISSED = 'dismissed';
}
