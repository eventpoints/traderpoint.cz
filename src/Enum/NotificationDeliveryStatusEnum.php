<?php

namespace App\Enum;

enum NotificationDeliveryStatusEnum: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case FAILED = 'failed';
    case SKIPPED = 'skipped';
}
