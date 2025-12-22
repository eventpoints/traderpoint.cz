<?php

namespace App\Enum;

enum EngagementStatusGroupEnum : string
{

    case ACTIVE = 'active';
    case DISCOVER = 'discover';
    case PENDING = 'pending';
    case HISTORICAL = 'historical';

}
