<?php

namespace App\Service\MailerFacade;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class MailerFacade
{
    public const APPLICATION_NO_REPLY_EMAIL_ADDRESS = 'no-reply@traderpoint.cz';

    public function __construct(
        private MailerInterface $mailer,
        private TranslatorInterface $translator,
        private LoggerInterface $logger
    )
    {
    }

    /**
     * @param array<string, mixed> $context
     * @throws TransportExceptionInterface
     */
    public function sendTemplatedEmail(
        string $from,
        string $to,
        string $subject,
        string $template,
        array $context = [],
    ): void
    {
        $email = (new TemplatedEmail())
            ->from($from)
            ->to($to)
            ->subject($subject)
            ->htmlTemplate($template)
            ->context($context);

        $this->send($email);
    }

    public function sendPasswordResetEmail(
        User $user
    ): void
    {
        $this->sendTemplatedEmail(
            from: self::APPLICATION_NO_REPLY_EMAIL_ADDRESS,
            to: $user->getEmail(),
            subject: $this->translator->trans('email.subject.reset-password'),
            template: 'emails/password-reset.html.twig',
            context: [
                'name' => $user->getFullName(),
                'token' => $user->getToken()->toRfc4122(),
            ]
        );
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendWelcomeEmail(
        User $user
    ): void
    {
        $this->sendTemplatedEmail(
            from: self::APPLICATION_NO_REPLY_EMAIL_ADDRESS,
            to: $user->getEmail(),
            subject: $this->translator->trans('email.subject.welcome'),
            template: 'emails/welcome.html.twig',
            context: [
                'firstName' => $user->getFirstName(),
                'token' => $user->getToken(),
            ]
        );
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function send(TemplatedEmail $email): void
    {
        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $exception) {
            $this->logger->warning($exception->getTraceAsString());
        }
    }
}
