<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Engagement;
use App\Entity\Payment;
use App\Entity\User;
use App\Enum\CurrencyCodeEnum;
use App\Enum\PaymentStatusEnum;
use App\Enum\PaymentTypeEnum;
use Carbon\CarbonImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

final class PaymentFixtures extends Fixture implements DependentFixtureInterface
{
    private int $counter = 0;

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('cs_CZ');

        /** @var Engagement[] $engagementRefs */
        $engagementRefs = [];
        foreach ($this->referenceRepository->getReferences() as $key => $obj) {
            if (\str_starts_with((string) $key, 'engagement_') && $obj instanceof Engagement) {
                // re-attach to this EM
                $engagementRefs[] = $manager->getReference(Engagement::class, $obj->getId());
            }
        }

        if ($engagementRefs === []) {
            return;
        }

        foreach ($engagementRefs as $eng) {
            // payer is the engagement owner (client)
            $owner = $eng->getOwner();
            if (!$owner instanceof User) {
                // if lazy, reattach via id
                $owner = $manager->getReference(User::class, $eng->getOwner()?->getId());
            }

            // --- POSTING_FEE (always create exactly one per engagement) ---
            $posting = new Payment($owner, $eng, 9_900, CurrencyCodeEnum::CZK, PaymentTypeEnum::POSTING_FEE, PaymentStatusEnum::PENDING);


            // Status distribution: 65% paid, 20% pending, 10% failed, 5% expired
            $roll = $faker->numberBetween(1, 100);
            if ($roll <= 65) {
                $posting->setStatus(PaymentStatusEnum::PAID);
                $posting->setStripeCheckoutSessionId($this->fakeCheckoutId());
                $posting->setStripePaymentIntentId($this->fakePaymentIntentId());
            } elseif ($roll <= 85) {
                $posting->setStatus(PaymentStatusEnum::PENDING);
                $posting->setStripeCheckoutSessionId($this->fakeCheckoutId());
            } elseif ($roll <= 95) {
                $posting->setStatus(PaymentStatusEnum::FAILED);
                $posting->setStripeCheckoutSessionId($this->fakeCheckoutId());
            } else {
                $posting->setStatus(PaymentStatusEnum::EXPIRED);
                $posting->setStripeCheckoutSessionId($this->fakeCheckoutId());
            }

            // back-date created/updated a bit for variety (if your entity allows it)
            $this->dateBump($posting, $faker->numberBetween(0, 30));

            $manager->persist($posting);
            $this->addReference('payment_' . $this->counter++ . '_posting', $posting);

            // --- Optional: BOOST for some already-paid engagements (no duplicate active) ---
            if ($posting->getStatus() === PaymentStatusEnum::PAID && $faker->boolean(25)) {
                $boost = new Payment($owner, $eng, 49_900, CurrencyCodeEnum::CZK, PaymentTypeEnum::FEATURED, PaymentStatusEnum::PENDING);

                $boost->setStatus(PaymentStatusEnum::PAID);
                $boost->setStripeCheckoutSessionId($this->fakeCheckoutId());
                $boost->setStripePaymentIntentId($this->fakePaymentIntentId());
                $this->dateBump($boost, $faker->numberBetween(0, 20));
                $manager->persist($boost);
                $this->addReference('payment_' . $this->counter++ . '_boost', $boost);
            }

            // --- Optional history: add a FAILED attempt for ~20% (doesn't violate "one active") ---
            if ($faker->boolean(20)) {
                $failed = new Payment($owner, $eng, 9_900, CurrencyCodeEnum::CZK, PaymentTypeEnum::POSTING_FEE, PaymentStatusEnum::PENDING);
                $failed->setStatus(PaymentStatusEnum::FAILED);
                $failed->setStripeCheckoutSessionId($this->fakeCheckoutId());
                $this->dateBump($failed, $faker->numberBetween(10, 60));
                $manager->persist($failed);
                $this->addReference('payment_' . $this->counter++ . '_posting_failed_hist', $failed);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            EngagementFixtures::class,
            UserFixtures::class,
        ];
    }

    private function fakeCheckoutId(): string
    {
        return 'cs_test_' . bin2hex(random_bytes(12));
        // looks like: cs_test_a1J7... (good enough for dev)
    }

    private function fakePaymentIntentId(): string
    {
        return 'pi_test_' . bin2hex(random_bytes(12));
    }

    /**
     * If your Payment sets createdAt/updatedAt in constructor but allows updating updatedAt,
     * bump updatedAt a bit so data looks more real. If you have setters for createdAt, use them.
     */
    private function dateBump(Payment $p, int $days): void
    {
        if (method_exists($p, 'setUpdatedAt')) {
            $p->setUpdatedAt(CarbonImmutable::now()->subDays($days));
        }
        if (method_exists($p, 'setCreatedAt')) {
            $p->setCreatedAt(CarbonImmutable::now()->subDays($days + 1));
        }
    }
}
