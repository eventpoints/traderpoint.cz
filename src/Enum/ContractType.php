<?php

namespace App\Enum;

enum ContractType: string
{
    case FIXED_PRICE = 'contract.type.fixed-price';

    case HOURLY = 'contract.type.hourly';
}