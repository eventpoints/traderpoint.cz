<?php

declare(strict_types=1);

namespace App\Service\EmailService;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class EmailService
{
    private const SENDER_EMAIL_ADDRESS = 'notifications@traderpoint.cz';

    public function __construct(
        private MailerInterface $mailer,
        private TranslatorInterface $translator
    )
    {
    }

    /**
     * @param array<string|int|object> $context
     */
    public function sendTraderWelcomeEmail(User $user, string $locale = 'en', array $context = []): void
    {
        $this->send(
            subject: $this->translator->trans(id: 'email.trader.subject.welcome', parameters: [
                'firstName' => $user->getFirstName(),
            ], domain: 'email'),
            template: '/email/trader/welcome.html.twig',
            emailAddress: $user->getEmail(),
            context: $context,
            locale: $locale
        );
    }

    /**
     * @param array<string|int|object> $context
     */
    public function sendClientWelcomeEmail(User $user, string $locale = 'en', array $context = []): void
    {
        $this->send(
            subject: $this->translator->trans(id: 'email.client.subject.welcome', parameters: [
                'firstName' => $user->getFirstName(),
            ], domain: 'email'),
            template: '/email/client/welcome.html.twig',
            emailAddress: $user->getEmail(),
            context: $context,
            locale: $locale
        );
    }

    /**
     * @param array<mixed> $context
     */
    public function sendVerificationCodeEmail(User $user, string $locale = 'en', array $context = []): void
    {
        $this->send(
            subject: $this->translator->trans(id: 'email.client.subject.verify-email-address', parameters: [
                'firstName' => $user->getFirstName(),
            ], domain: 'email'),
            template: '/email/verification/email-verification.html.twig',
            emailAddress: $user->getEmail(),
            context: $context,
            locale: $locale
        );
    }

    /**
     * @param array<mixed> $context
     */
    public function sendEngagementMatchEmail(User $user, string $locale = 'en', array $context = []): void
    {
        $this->send(
            subject: $this->translator->trans(id: 'email.trader.engagement.match', parameters: [
                'firstName' => $user->getFirstName(),
            ], domain: 'email'),
            template: '/email/trader/lead.html.twig',
            emailAddress: $user->getEmail(),
            context: $context,
            locale: $locale
        );
    }

    /**
     * @param array<mixed> $context
     */
    public function sendEngagementMessageEmail(User $user, string $locale = 'en', array $context = []): void
    {
        $this->send(
            subject: $this->translator->trans(id: 'email.subject.client.engagement.message', parameters: [
                'title' => $context['engagement']->getTitle(),
            ], domain: 'email'),
            template: '/email/client/message-received.html.twig',
            emailAddress: $user->getEmail(),
            context: $context,
            locale: $locale
        );
    }

    /**
     * @param array<mixed> $context
     */
    public function sendQuoteMadeEmail(User $user, string $locale = 'en', array $context = []): void
    {
        $this->send(
            subject: $this->translator->trans(id: 'quote-received', parameters: [
                'firstName' => $user->getFirstName(),
            ], domain: 'email'),
            template: '/email/client/quote.html.twig',
            emailAddress: $user->getEmail(),
            context: $context,
            locale: $locale
        );
    }

    /**
     * @param array<mixed> $context
     */
    public function sendPasswordResetEmail(User $user, string $locale = 'en', array $context = []): void
    {
        $this->send(
            subject: $this->translator->trans(id: 'password-reset', parameters: [
                'firstName' => $user->getFirstName(),
            ], domain: 'email'),
            template: '/email/security/password-reset.html.twig',
            emailAddress: $user->getEmail(),
            context: $context,
            locale: $locale
        );
    }

    /**
     * @param array<string|int|object> $context
     */
    private function compose(
        string $subject,
        string $template,
        string $emailAddress,
        array $context,
        string $locale = 'en'
    ): TemplatedEmail
    {
        $templatedEmail = new TemplatedEmail();
        $templatedEmail->locale($locale);
        $templatedEmail->from(addresses: self::SENDER_EMAIL_ADDRESS);
        $templatedEmail->to(address: new Address($emailAddress));
        $templatedEmail->subject(subject: $subject);
        $templatedEmail->htmlTemplate(template: $template);
        $templatedEmail->context(context: $context);
        return $templatedEmail;
    }

    /**
     * @param array<string|int|object> $context
     */
    private function send(
        string $subject,
        string $template,
        string $emailAddress,
        array $context,
        string $locale,
    ): void
    {
        $envelope = $this->compose(
            subject: $subject,
            template: $template,
            emailAddress: $emailAddress,
            context: $context,
            locale: $locale
        );

        $this->mailer->send($envelope);
    }
}
