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

        // Use ONLY traders
        $traderIds    = $this->collectTraderIdsByPrefix('user_');
        $engagementIds = $this->collectIdsByPrefix('engagement_', Engagement::class);

        if ($traderIds === [] || $engagementIds === []) {
            return;
        }

        $versionMap = [];            // "E<engId>|U<userId>" -> version
        $acceptedChosenByEng = [];   // "<engId>" -> true once chosen
        $persisted  = 0;

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
                static fn($tid) => (string)$tid !== (string)$ownerId
            ));
            if ($eligibleTraderIds === []) {
                continue;
            }

            $engWillChoose = (bool) (random_int(0, 99) < 55);

            // Pick 2–5 distinct traders from eligible pool
            $traderCount = min(\count($eligibleTraderIds), $faker->numberBetween(2, 5));
            $pickedTraderIds = $faker->randomElements($eligibleTraderIds, $traderCount, false);

            foreach ($pickedTraderIds as $userId) {
                /** @var User $trader */
                $trader = $manager->getReference(User::class, $userId);

                // 1–2 versions per (engagement, trader)
                $versions = $faker->numberBetween(1, 2);

                for ($v = 1; $v <= $versions; $v++) {
                    $key = 'E' . (string)$engId . '|U' . (string)$userId;
                    $currentVersion = ($versionMap[$key] ?? 0) + 1;
                    $versionMap[$key] = $currentVersion;

                    $netCents = $faker->numberBetween(5_000, 250_000);
                    $vatBps   = $faker->randomElement([0, 1000, 1500, 2100]);

                    $quote = new Quote($engagement, $trader, $netCents, CurrencyCodeEnum::CZK);
                    $quote->setVersion($currentVersion);
                    $quote->setVatRateBps($vatBps);

                    $createdAt = CarbonImmutable::instance($faker->dateTimeBetween('-6 months', 'now'));
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

                    $engKey = (string)$engId;
                    $alreadyChosen = !empty($acceptedChosenByEng[$engKey]);

                    if ($drawn === QuoteStatusEnum::ACCEPTED && (!$engWillChoose || $alreadyChosen)) {
                        $drawn = QuoteStatusEnum::REJECTED;
                    }

                    match ($drawn) {
                        QuoteStatusEnum::ACCEPTED => (function () use ($engagement, $quote, $createdAt, $faker, $engKey, &$acceptedChosenByEng) {
                            // MUST be the chosen quote on the engagement
                            $engagement->accept($quote);
                            $quote->setDecidedAt($createdAt->addDays($faker->numberBetween(1, 14)));
                            $acceptedChosenByEng[$engKey] = true;
                        })(),

                        QuoteStatusEnum::REJECTED => (function () use ($quote, $createdAt, $faker) {
                            $quote->setStatus(QuoteStatusEnum::REJECTED);
                            $quote->setDecidedAt($createdAt->addDays($faker->numberBetween(1, 14)));
                        })(),

                        QuoteStatusEnum::WITHDRAWN => (function () use ($quote, $createdAt, $faker) {
                            $quote->setStatus(QuoteStatusEnum::WITHDRAWN);
                            $quote->setDecidedAt($createdAt->addDays($faker->numberBetween(1, 14)));
                        })(),

                        QuoteStatusEnum::EXPIRED => (function () use ($quote, $createdAt, $faker) {
                            $vu = $quote->getValidUntil() ?? $createdAt->addDays($faker->numberBetween(7, 30));
                            if ($vu->isFuture()) {
                                $vu = $createdAt->addDays(7);
                            }
                            $quote->setValidUntil($vu);
                            $quote->setStatus(QuoteStatusEnum::EXPIRED);
                            $quote->setDecidedAt($vu->addHours($faker->numberBetween(1, 72)));
                        })(),

                        default => (function () use ($quote) {
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

    /**
     * Keep native ID types for proxies.
     * @template T of object
     * @param class-string<T> $class
     * @return array<int, mixed>
     */
    private function collectIdsByPrefix(string $prefix, string $class): array
    {
        $ids = [];
        foreach ($this->referenceRepository->getReferences() as $key => $obj) {
            if (\str_starts_with((string)$key, $prefix) && $obj instanceof $class) {
                $ids[] = $obj->getId();
            }
        }
        return $ids;
    }

    /**
     * IDs of users who are TRADERS (has ROLE_TRADER / isTrader()).
     * @return array<int, mixed>
     */
    private function collectTraderIdsByPrefix(string $prefix): array
    {
        $ids = [];
        foreach ($this->referenceRepository->getReferences() as $key => $obj) {
            if (\str_starts_with((string)$key, $prefix) && $obj instanceof User) {
                // relies on your User::isTrader() (checks ROLE_TRADER)
                if ($obj->isTrader()) {
                    $ids[] = $obj->getId();
                }
            }
        }
        return $ids;
    }

    public function getDependencies(): array
    {
        return [
            EngagementFixtures::class,
            UserFixtures::class,
        ];
    }
}
