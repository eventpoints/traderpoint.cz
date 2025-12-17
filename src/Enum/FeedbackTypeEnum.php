<?php

namespace App\Enum;

enum FeedbackTypeEnum: string
{
    case BUG_REPORT = 'BUG_REPORT';
    case FEATURE_REQUEST = 'FEATURE_REQUEST';
    case GENERAL = 'GENERAL';
    case COMPLAINT = 'COMPLAINT';
    case PRAISE = 'PRAISE';
    case OTHER = 'OTHER';
}
