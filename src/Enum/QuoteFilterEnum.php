<?php

declare(strict_types=1);

namespace App\Enum;

enum QuoteFilterEnum: string
{
    case ALL = 'all';
    case ACTIVE = 'active';
    case PAST = 'past';
}
