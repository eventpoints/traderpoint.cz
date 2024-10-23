<?php

namespace App\DataFixtures;

use App\DataFixtures\Data\SkillData;
use App\Entity\Skill;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class UserSkillFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        for ($userCount = 0; $userCount < 50; $userCount++) {
            for ($userSkillCount = 0; $userSkillCount < random_int(1, 4); $userSkillCount++) {
                /**
                 * @var User $owner
                 */
                $owner = $this->getReference(name: "user_$userCount");
                $allSkills = array_merge(...array_values(SkillData::getSkills()));
                $skillTitle = $allSkills[array_rand($allSkills)];
                /**
                 * @var Skill $skill
                 */
                $skill = $this->getReference(name: $skillTitle);

                $owner->addSkill($skill);
                $manager->persist($owner);
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
