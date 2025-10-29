<?php

namespace App\Service\PercentResolver;

use App\Entity\Partner;
use App\Entity\Store;

final class SimplePercentResolver implements PercentResolverInterface
{
    public function __construct(private int $defaultPercent = 15) {}

    public function resolve(Partner $partner, Store $store): int
    {
        // Replace with your real logic:
        // - if you add Store.percentOverride or Partner.memberPercent, use them here.
        // - otherwise return a config default (e.g. 15).
        if (method_exists($store, 'getPercentOverride') && $store->getPercentOverride() !== null) {
            return (int)$store->getPercentOverride();
        }
        if (method_exists($partner, 'getMemberPercent')) {
            return (int)$partner->getMemberPercent();
        }
        return $this->defaultPercent;
    }
}
