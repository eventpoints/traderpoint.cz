<?php

namespace App\Service\PercentResolver;

use App\Entity\Partner;
use App\Entity\Store;

interface PercentResolverInterface
{
    public function resolve(Partner $partner, Store $store): int;
}