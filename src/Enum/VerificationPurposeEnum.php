<?php

namespace App\Enum;

enum VerificationPurposeEnum: string
{
    case ENGAGEMENT_POSTING = 'engagement-posting';
    case VERIFY_EMAIL_ADDRESS = 'verify-email-address';
}
