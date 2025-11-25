<?php

declare(strict_types=1);

namespace App\Command;

use App\DataFixtures\Data\SkillData;
use App\Entity\Skill;
use App\Repository\SkillRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
// use Symfony\Component\Uid\Uuid; // if you use Uid for IDs

#[AsCommand(
    name: 'traderpoint:sync:skills',
    description: 'Sync SkillData into DB without deleting existing skills',
)]
final class SyncSkillsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SkillRepository $skillRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $existingByName = [];
        foreach ($this->skillRepository->findAll() as $skill) {
            $existingByName[$skill->getName()] = $skill;
        }

        foreach (SkillData::getSkills() as $groupName => $childNames) {
            $group = $existingByName[$groupName] ?? null;
            if (! $group) {
                $group = new Skill(name: $groupName);
                $group->setTrade(null);
                $this->em->persist($group);

                $existingByName[$groupName] = $group;
                $output->writeln(sprintf('Created group: %s', $groupName));
            }

            foreach ($childNames as $childName) {
                if (isset($existingByName[$childName])) {
                    continue;
                }

                $skill = new Skill(name: $childName);
                $skill->setTrade($group);
                $this->em->persist($skill);

                $existingByName[$childName] = $skill;
                $output->writeln(sprintf('Created skill: %s', $childName));
            }
        }

        $this->em->flush();

        $output->writeln('Skill sync complete.');

        return Command::SUCCESS;
    }
}
