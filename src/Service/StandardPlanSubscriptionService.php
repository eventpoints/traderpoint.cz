<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\StripeProfile;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Exception\ApiErrorException;
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
    )
    {
    }

    public function startStandardPlanTrial(User $user): Subscription
    {
        $billing = $this->getOrCreateBillingProfile($user);

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
                    'name' => in_array($user->getFullName(), ['', '0'], true) ? $user->getFullName() : $user->getUserIdentifier(),
                    'metadata' => [
                        'user_id' => (string) $user->getId(),
                        'locale' => $this->requestStack->getCurrentRequest()->getLocale(),
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

    private function getOrCreateBillingProfile(User $user): StripeProfile
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
}
