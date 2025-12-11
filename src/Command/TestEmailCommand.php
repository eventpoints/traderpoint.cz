<?php

declare(strict_types=1);

namespace App\Command;

use App\Enum\UserTokenPurposeEnum;
use App\Repository\UserRepository;
use App\Service\EmailService\EmailService;
use App\Service\UserTokenService\UserTokenService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'traderpoint:test-email')]
class TestEmailCommand extends Command
{
    public function __construct(
        private readonly EmailService $emailService,
        private readonly UserRepository $userRepository,
        private readonly UserTokenService $userTokenService
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = $this->userRepository->findOneBy([
            'email' => 'bog.sob@gm.com',
        ]);
        $token = $this->userTokenService->issueToken(user: $user, purpose: UserTokenPurposeEnum::EMAIL_VERIFICATION);

        $this->emailService->sendTraderWelcomeEmail(user: $user, context: [
            'user' => $user,
            'token' => $token,
        ]);

        $output->writeln('Sent test email.');
        return Command::SUCCESS;
    }
}
