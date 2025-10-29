<?php

namespace App\Service\PercentResolver;

use App\Entity\Partner;
use App\Entity\Store;

final readonly class SimplePercentResolver implements PercentResolverInterface
{
    public function __construct(
        private int $defaultPercent = 15
    ) {}

    public function resolve(Partner $partner, Store $store): int
    {
        if (method_exists($store, 'getPercentOverride') && $store->getPercentOverride() !== null) {
            return $store->getPercentOverride();
        }
        if (method_exists($partner, 'getMemberPercent')) {
            return $partner->getMemberPercent();
        }
        return $this->defaultPercent;
    }
}
