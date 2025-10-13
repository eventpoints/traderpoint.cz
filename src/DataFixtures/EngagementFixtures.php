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
        // Czech locale for place names, addresses, etc.
        $faker = Factory::create('cs_CZ');

        ContractType::cases();
        $statusCases = EngagementStatusEnum::cases();

        // --- Collect ALL Skill references and re-attach them to the current EM ---
        /** @var Skill[] $availableSkills */
        $availableSkills = [];
        foreach ($this->referenceRepository->getReferences() as $ref) {
            if ($ref instanceof Skill) {
                // Re-attach to this ObjectManager to avoid "new entity through relationship" error
                $availableSkills[] = $manager->getReference(Skill::class, $ref->getId());
            }
        }

        // Users created by UserFixtures: user_0 .. user_49
        for ($userIdx = 0; $userIdx < 25; $userIdx++) {
            /** @var User $owner */
            $owner = $this->getReference("user_{$userIdx}");

            $numEngagements = $faker->numberBetween(10, 25);

            for ($i = 0; $i < $numEngagements; $i++) {
                $engagement = new Engagement();

                $timelinePreferenceEnumOptions = $faker->randomElement(TimelinePreferenceEnum::cases());
                $engagement->setTimelinePreferenceEnum($timelinePreferenceEnumOptions);

                // --- Basics ---
                $engagement->setTitle($faker->sentence(4));
                $engagement->setDescription($faker->text(150));

                // --- Skills: 1–3 unique from the pool (if any) ---
                if (! empty($availableSkills)) {
                    $skillsToAdd = $faker->randomElements(
                        $availableSkills,
                        $faker->numberBetween(1, min(3, \count($availableSkills))),
                        false
                    );
                    foreach ($skillsToAdd as $skill) {
                        $engagement->addSkill($skill);
                    }
                }

                // --- Location (CZ-ish bounds) ---
                $engagement->setLatitude($faker->latitude(48.5, 51.0));
                $engagement->setLongitude($faker->longitude(12.0, 18.0));

                // --- Enums (limit currency to CZK/EUR) ---
                $currency = $faker->randomElement([CurrencyCodeEnum::CZK]);
                $status = $faker->randomElement($statusCases);

                $engagement->setCurrencyCodeEnum($currency);
                $engagement->setStatus($status);

                $factor = 100;
                $labourMajor = $faker->numberBetween(1_000, 500_000);
                $engagement->setBudget($labourMajor * $factor);

                // --- Dates ---
                $createdAt = CarbonImmutable::instance($faker->dateTimeBetween('-6 months', 'now'));
                $dueAt = CarbonImmutable::instance(
                    $faker->dateTimeBetween($createdAt->addWeek(), $createdAt->addMonths(3))
                );

                // If createdAt is handled in the entity constructor, no need to set it here.
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

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            SkillFixtures::class,
        ];
    }
}
