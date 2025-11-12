<?php

namespace App\DataFixtures;

use App\Entity\Engagement;
use App\Entity\Skill;
use App\Entity\User;
use App\Enum\ContractType;
use App\Enum\CurrencyCodeEnum;
use App\Enum\EngagementStatusEnum;
use App\Enum\TimelinePreferenceEnum;
use Carbon\CarbonImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class EngagementFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * Incrementing counter so we can add engagement_* references
     */
    private int $engCounter = 0;

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('cs_CZ');

        // touch enums so autoloaders don’t trim them (optional)
        ContractType::cases();
        $statusCases = EngagementStatusEnum::cases();

        // ---- Skills: pull from DB (no internal reference repo usage) ----
        /** @var list<Skill> $availableSkills */
        $availableSkills = $manager->getRepository(Skill::class)->findAll();

        // ---- Owners: try references user_0.., else fallback to DB ----
        /** @var list<User> $owners */
        $owners = [];
        for ($i = 0; $this->hasReference("user_{$i}", User::class); $i++) {
            /** @var User $u */
            $u = $this->getReference("user_{$i}", User::class);
            // reattach by id to current EM to be safe
            $owners[] = $manager->getReference(User::class, $u->getId());
        }
        if ($owners === []) {
            // fallback: take up to 25 users from DB
            $owners = $manager->getRepository(User::class)->findBy([], null, 25);
        }

        // ---- Filter out traders so they don't get any engagements ----
        $owners = array_values(array_filter(
            $owners,
            fn (User $u): bool => ! $this->isTrader($u)
        ));

        if ($owners === []) {
            // nothing to do if no client owners available
            return;
        }

        // For each (client) owner, create a bunch of engagements
        foreach ($owners as $owner) {
            $numEngagements = $faker->numberBetween(10, 25);

            for ($i = 0; $i < $numEngagements; $i++) {
                $engagement = new Engagement();

                // --- Timeline preference ---
                $engagement->setTimelinePreferenceEnum(
                    $faker->randomElement(TimelinePreferenceEnum::cases())
                );

                // --- Basics ---
                $engagement->setTitle($faker->sentence(4));
                $engagement->setDescription($faker->text(150));

                // --- Skills: 1–3 unique from the pool (if any) ---
                if ($availableSkills !== []) {
                    $count = $faker->numberBetween(1, min(3, \count($availableSkills)));
                    /** @var list<Skill> $skillsToAdd */
                    $skillsToAdd = $faker->randomElements($availableSkills, $count, false);
                    foreach ($skillsToAdd as $skill) {
                        // ensure it’s attached to this EM (getReference by id)
                        $attached = $manager->contains($skill)
                            ? $skill
                            : $manager->getReference(Skill::class, $skill->getId());
                        $engagement->addSkill($attached);
                    }
                }

                // --- Location (CZ-ish bounds) ---
                $engagement->setLatitude($faker->latitude(48.5, 51.0));
                $engagement->setLongitude($faker->longitude(12.0, 18.0));

                // --- Enums (limit currency to CZK) & status ---
                $engagement->setCurrencyCodeEnum(CurrencyCodeEnum::CZK);
                $engagement->setStatus($faker->randomElement($statusCases));

                // --- Budget (store in minor units if that’s your convention) ---
                $factor = 100;
                $labourMajor = $faker->numberBetween(1_000, 500_000);
                $engagement->setBudget($labourMajor * $factor);

                // --- Dates ---
                $createdAt = CarbonImmutable::instance($faker->dateTimeBetween('-6 months', 'now'));
                $dueAt = CarbonImmutable::instance(
                    $faker->dateTimeBetween($createdAt->addWeek(), $createdAt->addMonths(3))
                );

                // If your entity manages createdAt itself, just set what’s needed
                $engagement->setUpdatedAt(null);
                $engagement->setDueAt($dueAt);

                // --- Relationships ---
                $engagement->setOwner($owner);

                $manager->persist($engagement);
                $this->addReference('engagement_' . $this->engCounter++, $engagement);
            }
        }

        $manager->flush();
    }

    /**
     * Determines if a user is a trader, using whatever your User model exposes.
     */
    private function isTrader(User $u): bool
    {
        // 1) Dedicated method
        if (method_exists($u, 'isTrader')) {
            return $u->isTrader();
        }

        // 2) Role checks
        if (method_exists($u, 'hasRole') && $u->hasRole('ROLE_TRADER')) {
            return true;
        }
        if (method_exists($u, 'getRoles')) {
            $roles = $u->getRoles();
            if (\in_array('ROLE_TRADER', $roles, true)) {
                return true;
            }
        }
        // 3) Trader profile relation
        return method_exists($u, 'getTraderProfile') && $u->getTraderProfile() instanceof \App\Entity\TraderProfile;
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            SkillFixtures::class,
        ];
    }
}
