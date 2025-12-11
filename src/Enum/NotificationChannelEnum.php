<?php

namespace App\Enum;

enum NotificationChannelEnum: string
{
    case EMAIL = 'email';
    case SMS = 'sms';
    case PUSH = 'push';
    case IN_APP = 'in_app';
    case WEBHOOK = 'webhook';
}
