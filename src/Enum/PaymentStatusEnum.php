<?php

namespace App\Enum;

enum PaymentStatusEnum: string
{
    case PENDING = 'payment.pending';
    case PAID = 'payment.paid';
    case CANCELED = 'payment.canceled';
    case EXPIRED = 'payment.expired';
    case FAILED = 'payment.failed';
}
