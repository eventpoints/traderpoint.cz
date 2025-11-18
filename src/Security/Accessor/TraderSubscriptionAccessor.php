<?php
declare(strict_types=1);

namespace App\Security\Accessor;

use App\Entity\StripeProfile;
use App\Entity\User;
use App\Security\Accessor\Contract\AccessorInterface;
use Carbon\CarbonImmutable;

final class TraderSubscriptionAccessor implements AccessorInterface
{
    public function getCode(): string
    {
        return 'trader.subscription';
    }

    public function canAccess(User $user, mixed $context = null): bool
    {
        $profile = $user->getStripeProfile();
        if (! $profile instanceof StripeProfile) {
            return false;
        }

        $status = $profile->getSubscriptionStatus();

        // Fully paid, subscription active
        if ($status === 'active') {
            return true;
        }

        // Trialing – check trial end
        if ($status === 'trialing') {
            $trialEndsAt = $profile->getTrialEndsAt();

            if (empty($trialEndsAt)) {
                // defensive: if Stripe didn't send trial_end for some reason, treat as valid
                return true;
            }

            return CarbonImmutable::now()->lt(CarbonImmutable::instance($trialEndsAt));
        }

        // canceled, past_due, incomplete, etc.
        return false;
    }

    public function getDenialReason(User $user, mixed $context = null): ?string
    {
        $profile = $user->getStripeProfile();
        if (! $profile instanceof StripeProfile) {
            return 'no_subscription_started';
        }

        $status = $profile->getSubscriptionStatus();

        return match ($status) {
            'canceled'   => 'subscription_canceled',
            'past_due'   => 'subscription_past_due',
            'incomplete' => 'subscription_incomplete',
            'trialing'   => 'trial_expired',
            default      => 'subscription_inactive',
        };
    }
}
