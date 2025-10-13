<?php

namespace App\Enum;

enum QuoteStatusEnum: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case WITHDRAWN = 'withdrawn';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case SUPERSEDED = 'superseded';
    case EXPIRED = 'expired';
}
