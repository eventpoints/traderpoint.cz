<?php

namespace App\DataFixtures;

use App\DataFixtures\Data\SkillData;
use App\Entity\Skill;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SkillFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        foreach (SkillData::getSkills() as $trade => $skills) {
            $tradeEntity = new Skill(name: $trade);
            foreach ($skills as $skill) {
                $skillEntity = new Skill(name: $skill);
                $skillEntity->setTrade($tradeEntity);
                $tradeEntity->addSkill($skillEntity);
                $this->addReference($skill, $skillEntity);
            }

            $this->addReference(name: $trade, object: $tradeEntity);
            $manager->persist($tradeEntity);
        }
        $manager->flush();
    }
}
