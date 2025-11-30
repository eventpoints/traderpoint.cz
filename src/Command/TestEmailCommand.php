<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(name: 'traderpoint:test-email')]
class TestEmailCommand extends Command
{
    public function __construct(
        private readonly MailerInterface $mailer
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = (new Email())
            ->from('no-reply@traderpoint.cz')
            ->to('kerrialbeckettnewham@gmail.com')
            ->subject('Test email from TraderPoint PROD')
            ->text('If you see this, MAILER_DSN works on prod.');

        $this->mailer->send($email);
        $output->writeln('Sent test email.');
        return Command::SUCCESS;
    }
}
