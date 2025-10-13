<?php

namespace App\Enum;

enum PaymentTypeEnum: string
{
    case POSTING_FEE = 'posting_fee';
    case FEATURED = 'featured';
    case EXTEND = 'extend';
}
