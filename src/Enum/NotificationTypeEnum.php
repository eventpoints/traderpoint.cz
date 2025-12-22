<?php

namespace App\Enum;

enum NotificationTypeEnum: string
{
    case CONFIRM_EMAIL_ADDRESS = 'confirm_email_address';
    case NEW_ENGAGEMENT = 'new_engagement';
    case MISSING_SERVICE_RADIUS = 'missing_service_radius';
    case PHONE_NUMBER_NOT_VERIFIED = 'phone_number_not_verified';
    case PASSWORD_RESET = 'password_reset';
    case QUOTE_RECEIVED = 'quote_received';
    case ENGAGEMENT_MESSAGE_RECEIVED = 'engagement_message_received';
    case ENGAGEMENT_MATCH = 'engagement_match';
    case VERIFICATION_CODE_VIA_EMAIL = 'verification_code_via_email';
    case ENGAGEMENT_ISSUE = 'engagement_issue';
    case TRADER_ENGAGEMENT_REVIEW = 'trader_engagement_review';

}
