<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\UserNotificationSettings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:backfill-notification-settings')]
final class BackfillNotificationSettingsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userRepo = $this->em->getRepository(User::class);

        /** @var User[] $users */
        $users = $userRepo->createQueryBuilder('u')
            ->leftJoin('u.notificationSettings', 'ns')
            ->andWhere('ns.id IS NULL')
            ->getQuery()
            ->getResult();

        foreach ($users as $user) {
            $settings = new UserNotificationSettings(user: $user);
            $user->setNotificationSettings($settings);
            $this->em->persist($settings);
        }

        $this->em->flush();

        $output->writeln(sprintf('Created settings for %d users.', \count($users)));

        return Command::SUCCESS;
    }
}
