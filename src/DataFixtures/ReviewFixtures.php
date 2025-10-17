<?php

namespace App\DataFixtures;

use App\Entity\Review;
use App\Entity\TraderProfile;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

final class ReviewFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('cs_CZ');

        // Collect users created by UserFixtures
        /** @var User[] $allUsers */
        $allUsers = [];
        for ($i = 0; $i < 25; $i++) {
            /** @var User $u */
            $u = $this->getReference("user_{$i}");
            $allUsers[] = $u;
        }

        // Split into traders (with TraderProfile) and clients (without)
        $traders = array_values(array_filter($allUsers, static fn (User $u) => $u->getTraderProfile() instanceof TraderProfile));
        $clients = array_values(array_filter($allUsers, static fn (User $u) => !($u->getTraderProfile() instanceof TraderProfile)));

        if (!$traders) {
            return;
        }

        foreach ($traders as $tIndex => $traderUser) {

            $profile = $traderUser->getTraderProfile();
            if (!$profile) {
                continue;
            }

            // 2–10 reviews per trader
            $count = $faker->numberBetween(2, 10);

            for ($j = 0; $j < $count; $j++) {
                $owner = $this->pickAuthor($clients, $allUsers, $traderUser);

                // Generate 4 sub-ratings (biased towards 3.5–5.0), strings for DECIMAL columns
                $response   = $this->rating($faker);
                $service    = $this->rating($faker);
                $quality    = $this->rating($faker);
                $value      = $this->rating($faker);

                $title   = ucfirst($faker->words($faker->numberBetween(2, 5), true));
                $content = $faker->paragraphs($faker->numberBetween(2, 4), true); // ~120–400 chars

                $review = new Review(
                    title: $title,
                    content: $content,
                    responseRating: $response,
                    customerServicesRating: $service,
                    workQualityRating: $quality,
                    valueForMoneyRating: $value,
                );

                // Date within last ~180 days
                $createdAt = (new \Carbon\CarbonImmutable())
                    ->subDays($faker->numberBetween(0, 180))
                    ->subMinutes($faker->numberBetween(0, 1440));
                $review->setCreatedAt($createdAt);

                // Link relations
                $review->setTarget($profile);   // Trader being reviewed
                $review->setOwner($owner);      // Client author

                $manager->persist($review);
                // Optional reference if you want
                // $this->addReference("review_{$tIndex}_{$j}", $review);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }

    /**
     * Generate a 1-decimal rating string between 0 and 5, skewed to 3.5–5.0.
     */
    private function rating(\Faker\Generator $faker): string
    {
        // Normal-ish distribution centered around ~4.2
        $raw = max(0, min(5, $faker->randomFloat(2, 3.2, 5) + $faker->randomFloat(2, -0.6, 0.6)));
        return number_format(round($raw, 1), 1, '.', '');
    }

    /**
     * Prefer a client as author; if none exist, pick any non-trader or fallback to any different user.
     */
    private function pickAuthor(array $clients, array $allUsers, User $traderUser): User
    {
        if ($clients) {
            return $clients[array_rand($clients)];
        }

        // Fallback: any user that isn't the trader
        do {
            /** @var User $candidate */
            $candidate = $allUsers[array_rand($allUsers)];
        } while ($candidate === $traderUser);

        return $candidate;
    }
}
