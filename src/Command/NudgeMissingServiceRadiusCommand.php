<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\TraderProfile;
use App\Repository\TraderProfileRepository;
use App\Service\EmailService\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsCommand(
    name: 'traderpoint:trader:nudge-missing-service-radius',
    description: 'Send emails to traders who have not set their service radius.'
)]
final class NudgeMissingServiceRadiusCommand extends Command
{
    public function __construct(
        private readonly TraderProfileRepository $profiles,
        private readonly EmailService            $emailService,
        private readonly UrlGeneratorInterface   $urlGenerator,
        private readonly EntityManagerInterface  $em,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var TraderProfile[] $profiles */
        $profiles = $this->profiles->findWithMissingServiceRadius();

        if ($profiles === []) {
            $io->info('No traders with missing service radius found.');
            return Command::SUCCESS;
        }

        $sent = 0;

        foreach ($profiles as $profile) {
            $user = $profile->getOwner();
            $locale = $user->getPreferredLanguage() ?? 'cs';

            $settingsUrl = $this->urlGenerator->generate(
                'user_account',
                ['tab' => 'trader'],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $context = [
                'user' => $user,
                'profile' => $profile,
                'settingsUrl' => $settingsUrl,
            ];

            $this->emailService->sendTraderServiceRadiusMissingEmail(
                $user,
                $locale,
                $context
            );

            ++$sent;
        }

        $this->em->clear();

        $io->success(sprintf('Sent %d service radius nudges', $sent));

        return Command::SUCCESS;
    }
}
