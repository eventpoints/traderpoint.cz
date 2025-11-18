<?php
declare(strict_types=1);

namespace App\Security\Accessor\Contract;

use App\Entity\User;

interface AccessorInterface
{
    /**
     * Stable identifier for this accessor, e.g. "trader.subscription".
     */
    public function getCode(): string;

    /**
     * Can the given user access this feature?
     *
     * $context lets you pass extra info later if needed (engagement, quote, etc.).
     */
    public function canAccess(User $user, mixed $context = null): bool;

    /**
     * Optional short explanation for why access is denied.
     * Return null if access is granted or you do not care.
     */
    public function getDenialReason(User $user, mixed $context = null): ?string;
}
