<?php

namespace App\Enum;

enum TrackedLinkSourceEnum: string
{
    case EMAIL = 'email';
    case SMS = 'sms';
    case QR = 'qr';
    case REFERRAL = 'referral';
    case SOCIAL = 'social';
    case DIRECT = 'direct';
    case OTHER = 'other';
}
