<?php

namespace App\Enum;

enum FeedbackStatusEnum: string
{
    case PENDING = 'PENDING';
    case IN_REVIEW = 'IN_REVIEW';
    case RESOLVED = 'RESOLVED';
    case CLOSED = 'CLOSED';
}
