<?php

namespace App\DataFixtures;

use App\Entity\Engagement;
use App\Entity\Quote;
use App\Entity\User;
use App\Enum\CurrencyCodeEnum;
use App\Enum\QuoteStatusEnum;
use Carbon\CarbonImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

final class QuoteFixtures extends Fixture implements DependentFixtureInterface
{
    private const BATCH_SIZE = 400;

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('cs_CZ');

        // Use ONLY traders (by reference if present, else DB fallback)
        $traderIds     = $this->collectTraderIdsByPrefix($manager, 'user_', User::class);
        $engagementIds = $this->collectIdsByPrefix($manager, 'engagement_', Engagement::class);

        if ($traderIds === [] || $engagementIds === []) {
            return;
        }

        $versionMap = [];          // "E<engId>|U<userId>" -> version
        $acceptedByEng = [];       // "<engId>" -> true once chosen
        $persisted = 0;

        foreach ($engagementIds as $engId) {
            // Skip some engagements entirely
            if ($faker->boolean(70)) {
                continue;
            }

            /** @var Engagement $engagement */
            $engagement = $manager->getReference(Engagement::class, $engId);

            // Don’t let owner quote their own engagement
            $ownerId = $engagement->getOwner()?->getId();
            $eligibleTraderIds = array_values(array_filter(
                $traderIds,
                static fn($tid): bool => (string) $tid !== (string) $ownerId
            ));
            if ($eligibleTraderIds === []) {
                continue;
            }

            $engWillChoose = $faker->boolean(55);

            // Pick 2–5 distinct traders from eligible pool
            $traderCount     = min(\count($eligibleTraderIds), $faker->numberBetween(2, 5));
            $pickedTraderIds = $faker->randomElements($eligibleTraderIds, $traderCount, false);

            foreach ($pickedTraderIds as $userId) {
                /** @var User $trader */
                $trader = $manager->getReference(User::class, $userId);

                // 1–2 versions per (engagement, trader)
                $versions = $faker->numberBetween(1, 2);

                for ($v = 1; $v <= $versions; $v++) {
                    $key = 'E' . $engId . '|U' . $userId;
                    $currentVersion = ($versionMap[$key] ?? 0) + 1;
                    $versionMap[$key] = $currentVersion;

                    $netCents = $faker->numberBetween(5_000, 250_000);
                    $vatBps   = $faker->randomElement([0, 1000, 1500, 2100]);

                    $quote = new Quote($engagement, $trader, $netCents, CurrencyCodeEnum::CZK);
                    $quote->setVersion($currentVersion);
                    $quote->setVatRateBps($vatBps);

                    $createdAt = CarbonImmutable::instance($faker->dateTimeBetween('-30 days', 'now'));
                    $quote->setCreatedAt($createdAt);

                    if ($faker->boolean(70)) {
                        $quote->setValidUntil($createdAt->addDays($faker->numberBetween(7, 45)));
                    }
                    if ($faker->boolean(60)) {
                        $quote->setStartAt($createdAt->addDays($faker->numberBetween(3, 21)));
                        $quote->setExpectedDurationHours($faker->numberBetween(4, 80));
                    }
                    $quote->setIncludesMaterials($faker->boolean(40));
                    $quote->setDepositPercentBps($faker->boolean(30) ? $faker->numberBetween(500, 5000) : null);
                    $quote->setWarrantyMonths($faker->boolean(25) ? $faker->numberBetween(3, 24) : null);
                    $quote->setMessage($faker->boolean(70) ? $faker->sentence(12) : null);

                    $drawn = $faker->randomElement([
                        QuoteStatusEnum::SUBMITTED,
                        QuoteStatusEnum::ACCEPTED,
                        QuoteStatusEnum::REJECTED,
                        QuoteStatusEnum::WITHDRAWN,
                        QuoteStatusEnum::EXPIRED,
                    ]);

                    $engKey = (string) $engId;
                    $alreadyChosen = !empty($acceptedByEng[$engKey]);

                    // Ensure only one ACCEPTED per engagement (if at all)
                    if ($drawn === QuoteStatusEnum::ACCEPTED && (!$engWillChoose || $alreadyChosen)) {
                        $drawn = QuoteStatusEnum::REJECTED;
                    }

                    match ($drawn) {
                        QuoteStatusEnum::ACCEPTED => (function () use ($engagement, $quote, $createdAt, $faker, $engKey, &$acceptedByEng): void {
                            // MUST be the chosen quote on the engagement
                            $engagement->accept($quote);
                            $quote->setDecidedAt($createdAt->addDays($faker->numberBetween(1, 14)));
                            $acceptedByEng[$engKey] = true;
                        })(),

                        QuoteStatusEnum::REJECTED => (function () use ($quote, $createdAt, $faker): void {
                            $quote->setStatus(QuoteStatusEnum::REJECTED);
                            $quote->setDecidedAt($createdAt->addDays($faker->numberBetween(1, 14)));
                        })(),

                        QuoteStatusEnum::WITHDRAWN => (function () use ($quote, $createdAt, $faker): void {
                            $quote->setStatus(QuoteStatusEnum::WITHDRAWN);
                            $quote->setDecidedAt($createdAt->addDays($faker->numberBetween(1, 14)));
                        })(),

                        QuoteStatusEnum::EXPIRED => (function () use ($quote, $createdAt, $faker): void {
                            $vu = $quote->getValidUntil() ?? $createdAt->addDays($faker->numberBetween(7, 30));
                            if ($vu->isFuture()) {
                                $vu = $createdAt->addDays(7);
                            }
                            $quote->setValidUntil($vu);
                            $quote->setStatus(QuoteStatusEnum::EXPIRED);
                            $quote->setDecidedAt($vu->addHours($faker->numberBetween(1, 72)));
                        })(),

                        default => (function () use ($quote): void {
                            $quote->setStatus(QuoteStatusEnum::SUBMITTED);
                            $quote->setDecidedAt(null);
                        })(),
                    };

                    $manager->persist($quote);
                    if ((++$persisted % self::BATCH_SIZE) === 0) {
                        $manager->flush();
                    }
                }
            }
        }

        $manager->flush();
        $manager->clear(Quote::class);
    }

    public function getDependencies(): array
    {
        return [
            EngagementFixtures::class,
            UserFixtures::class,
        ];
    }

    /**
     * Enumerate refs user_0..N and return IDs of traders.
     * Falls back to DB query (all users filtered by isTrader()) if no refs found.
     *
     * @return array<int, mixed>
     */
    private function collectTraderIdsByPrefix(ObjectManager $manager, string $prefix, string $fqcn): array
    {
        $ids = [];
        for ($i = 0; $this->hasReference("{$prefix}{$i}", $fqcn); $i++) {
            $ref = $this->getReference("{$prefix}{$i}", $fqcn);
            if ($ref instanceof User && $ref->isTrader()) {
                $ids[] = $ref->getId();
            }
        }
        if ($ids === []) {
            // Fallback: DB fetch & filter
            $users = $manager->getRepository(User::class)->findAll();
            foreach ($users as $u) {
                if ($u instanceof User && $u->isTrader()) {
                    $ids[] = $u->getId();
                }
            }
        }
        return $ids;
    }

    /**
     * Enumerate refs engagement_0..N to collect IDs for the given class.
     * Falls back to DB when no references are present.
     *
     * @template T of object
     * @param ObjectManager $manager
     * @param string $prefix
     * @param class-string<T> $class
     * @return array<int, mixed>
     */
    private function collectIdsByPrefix(ObjectManager $manager, string $prefix, string $class): array
    {
        $ids = [];
        for ($i = 0; $this->hasReference("{$prefix}{$i}", $class); $i++) {
            $ref = $this->getReference("{$prefix}{$i}", $class);
            if ($ref instanceof $class) {
                $ids[] = $ref->getId();
            }
        }
        if ($ids === []) {
            // Fallback: DB fetch (IDs only)
            $entities = $manager->getRepository($class)->findAll();
            foreach ($entities as $e) {
                if ($e instanceof $class) {
                    $ids[] = $e->getId();
                }
            }
        }
        return $ids;
    }
}
