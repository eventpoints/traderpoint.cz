<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\StripeProfile;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Exception\ApiErrorException;
use Stripe\SetupIntent;
use Stripe\StripeClient;
use Stripe\Subscription;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class StandardPlanSubscriptionService
{
    public function __construct(
        private StripeClient $stripe,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        #[Autowire('%env(STRIPE_STANDARD_PLAN_PRICE_ID)%')]
        private string $standardPlanPriceId,
        #[Autowire('%env(int:TRADERPOINT_TRIAL_DAYS)%')]
        private int $trialDays,
        private RequestStack $requestStack,
    ) {
    }

    public function startStandardPlanTrial(User $user): Subscription
    {
        $billing = $this->getOrCreateStripeProfile($user);

        if ($this->hasActiveOrTrialStandardSubscription($billing)) {
            $this->logger->info('User already has active/trial Standard Plan', [
                'userId' => $user->getId(),
                'subscriptionId' => $billing->getStripeSubscriptionId(),
            ]);

            return $this->stripe->subscriptions->retrieve(
                $billing->getStripeSubscriptionId(),
                []
            );
        }

        try {
            // Ensure Stripe Customer
            $customerId = $billing->getStripeCustomerId();
            if (! $customerId) {
                $customer = $this->stripe->customers->create([
                    'email' => $user->getEmail(),
                    'name' => in_array($user->getFullName(), ['', '0'], true)
                        ? $user->getUserIdentifier()
                        : $user->getFullName(),
                    'metadata' => [
                        'user_id' => (string) $user->getId(),
                        'locale' => $this->requestStack->getCurrentRequest()?->getLocale(),
                    ],
                ]);

                $customerId = $customer->id;
                $billing->setStripeCustomerId($customerId);
            }

            $subscription = $this->stripe->subscriptions->create([
                'customer' => $customerId,
                'items' => [[
                    'price' => $this->standardPlanPriceId,
                ]],
                'trial_period_days' => $this->trialDays,
                'trial_settings' => [
                    'end_behavior' => [
                        'missing_payment_method' => 'cancel',
                    ],
                ],
                'metadata' => [
                    'plan' => 'standard',
                    'user_id' => (string) $user->getId(),
                ],
            ]);

            $trialEndsAt = null;
            if ($subscription->trial_end) {
                $trialEndsAt = (new \DateTimeImmutable())->setTimestamp($subscription->trial_end);
            }

            $billing
                ->setStripeSubscriptionId($subscription->id)
                ->setCurrentPlan('standard')
                ->setSubscriptionStatus($subscription->status)
                ->setTrialEndsAt($trialEndsAt);

            $this->em->persist($billing);
            $this->em->flush();

            return $subscription;
        } catch (ApiErrorException $e) {
            $this->logger->error('Stripe error while starting Standard Plan trial', [
                'userId' => $user->getId(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * NEW – used by the paywall GET to prepare Stripe Elements.
     */
    public function createSetupIntentFor(User $user): SetupIntent
    {
        $profile = $this->getOrCreateStripeProfile($user);

        // Ensure Stripe Customer exists
        $customerId = $profile->getStripeCustomerId();
        if (! $customerId) {
            $customer = $this->stripe->customers->create([
                'email' => $user->getEmail(),
                'name' => $user->getFullName() ?: $user->getUserIdentifier(),
                'metadata' => [
                    'user_id' => (string) $user->getId(),
                ],
            ]);

            $customerId = $customer->id;
            $profile->setStripeCustomerId($customerId);

            // persist the customer id update
            $this->em->persist($profile);
            $this->em->flush();
        }

        // This is where we pass context to Stripe as metadata (purely for your own bookkeeping)
        return $this->stripe->setupIntents->create([
            'customer' => $customerId,
            'usage' => 'off_session',
            'metadata' => [
                'user_id' => (string) $user->getId(),
                'stripe_profile_id' => (string) $profile->getId(),
                'current_plan' => $profile->getCurrentPlan() ?? 'standard',
                'current_subscription_id' => $profile->getStripeSubscriptionId() ?? '',
            ],
        ]);
    }

    private function getOrCreateStripeProfile(User $user): StripeProfile
    {
        $stripeProfile = $user->getStripeProfile();
        if ($stripeProfile instanceof StripeProfile) {
            return $stripeProfile;
        }

        $stripeProfile = new StripeProfile($user);
        $user->setStripeProfile($stripeProfile);

        $this->em->persist($stripeProfile);

        return $stripeProfile;
    }

    private function hasActiveOrTrialStandardSubscription(StripeProfile $stripeProfile): bool
    {
        $subscriptionId = $stripeProfile->getStripeSubscriptionId();
        if (! $subscriptionId) {
            return false;
        }

        try {
            $subscription = $this->stripe->subscriptions->retrieve($subscriptionId, []);
            $item = $subscription->items->data[0] ?? null;
            $priceId = $item?->price?->id;

            return in_array($subscription->status, ['trialing', 'active'], true)
                && $priceId === $this->standardPlanPriceId;
        } catch (ApiErrorException $e) {
            $this->logger->warning('Unable to verify existing subscription', [
                'subscriptionId' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function activateStandardPlanFromPaymentMethod(User $user, string $paymentMethodId): Subscription
    {
        $billing = $this->getOrCreateStripeProfile($user);

        // Ensure Stripe Customer exists (same pattern as startStandardPlanTrial)
        $customerId = $billing->getStripeCustomerId();
        if (! $customerId) {
            $customer = $this->stripe->customers->create([
                'email' => $user->getEmail(),
                'name' => in_array($user->getFullName(), ['', '0'], true)
                    ? $user->getUserIdentifier()
                    : $user->getFullName(),
                'metadata' => [
                    'user_id' => (string) $user->getId(),
                    'locale' => $this->requestStack->getCurrentRequest()?->getLocale(),
                ],
            ]);

            $customerId = $customer->id;
            $billing->setStripeCustomerId($customerId);
        }

        try {
            // 1) Attach the payment method to this customer
            $this->stripe->paymentMethods->attach($paymentMethodId, [
                'customer' => $customerId,
            ]);

            // 2) Make it the default payment method for invoices
            $this->stripe->customers->update($customerId, [
                'invoice_settings' => [
                    'default_payment_method' => $paymentMethodId,
                ],
            ]);

            // 3) Create or reuse subscription
            $subscriptionId = $billing->getStripeSubscriptionId();
            $subscription = null;

            if ($subscriptionId) {
                $subscription = $this->stripe->subscriptions->retrieve($subscriptionId, []);
            }

            if (! $subscription || in_array($subscription->status, ['canceled', 'incomplete_expired'], true)) {
                // New subscription, no trial – trial already used or expired
                $subscription = $this->stripe->subscriptions->create([
                    'customer' => $customerId,
                    'items' => [[
                        'price' => $this->standardPlanPriceId,
                    ]],
                    'metadata' => [
                        'plan' => 'standard',
                        'user_id' => (string) $user->getId(),
                    ],
                ]);
            } else {
                // There is an existing subscription (trialing/incomplete etc.)
                // Just ensuring default payment method is enough; no need to recreate the sub.
            }

            $trialEndsAt = null;
            if ($subscription->trial_end) {
                $trialEndsAt = (new \DateTimeImmutable())->setTimestamp($subscription->trial_end);
            }

            $billing
                ->setStripeSubscriptionId($subscription->id)
                ->setCurrentPlan('standard')
                ->setSubscriptionStatus($subscription->status)
                ->setTrialEndsAt($trialEndsAt);

            $this->em->persist($billing);
            $this->em->flush();

            return $subscription;
        } catch (ApiErrorException $e) {
            $this->logger->error('Stripe error while activating Standard Plan from payment method', [
                'userId' => $user->getId(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
