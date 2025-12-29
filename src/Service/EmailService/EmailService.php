<?php

declare(strict_types=1);

namespace App\Service\EmailService;

use App\Entity\Engagement;
use App\Entity\User;
use App\Enum\NotificationChannelEnum;
use App\Enum\NotificationTypeEnum;
use App\Event\Event\NotificationSentEvent;
use Carbon\CarbonImmutable;
use Exception;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class EmailService
{
    private const SENDER_EMAIL_ADDRESS = 'notifications@traderpoint.cz';

    public function __construct(
        private MailerInterface $mailer,
        private TranslatorInterface $translator,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    /**
     * @param array<string|int|object> $context
     */
    public function sendTraderWelcomeEmail(User $user, string $locale = 'en', array $context = []): void
    {
        $this->send(
            subject: $this->translator->trans(
                id: 'email.trader.subject.welcome',
                parameters: [
                    'firstName' => $user->getFirstName(),
                ],
                domain: 'email'
            ),
            template: '/email/trader/welcome.html.twig',
            user: $user,
            context: $context,
            locale: $locale,
            notificationType: NotificationTypeEnum::CONFIRM_EMAIL_ADDRESS,
            dedupeKey: null,
        );
    }

    /**
     * @param array<string|int|object> $context
     */
    public function sendClientWelcomeEmail(User $user, string $locale = 'en', array $context = []): void
    {
        $this->send(
            subject: $this->translator->trans(
                id: 'email.client.subject.welcome',
                parameters: [
                    'firstName' => $user->getFirstName(),
                ],
                domain: 'email'
            ),
            template: '/email/client/welcome.html.twig',
            user: $user,
            context: $context,
            locale: $locale,
            notificationType: NotificationTypeEnum::CONFIRM_EMAIL_ADDRESS,
            dedupeKey: null,
        );
    }

    /**
     * @param array<mixed> $context
     */
    public function sendVerificationCodeEmail(User $user, string $locale = 'en', array $context = []): void
    {
        $this->send(
            subject: $this->translator->trans(
                id: 'email.client.subject.verify-email-address',
                parameters: [
                    'firstName' => $user->getFirstName(),
                ],
                domain: 'email'
            ),
            template: '/email/verification/email-verification.html.twig',
            user: $user,
            context: $context,
            locale: $locale,
            notificationType: NotificationTypeEnum::VERIFICATION_CODE_VIA_EMAIL,
            dedupeKey: null,
        );
    }

    /**
     * @param array<mixed> $context
     */
    public function sendEngagementMatchEmail(User $user, string $locale = 'en', array $context = []): void
    {
        $this->send(
            subject: $this->translator->trans(
                id: 'email.trader.engagement.match',
                parameters: [
                    'firstName' => $user->getFirstName(),
                ],
                domain: 'email'
            ),
            template: '/email/trader/lead.html.twig',
            user: $user,
            context: $context,
            locale: $locale,
            notificationType: NotificationTypeEnum::ENGAGEMENT_MATCH,
            dedupeKey: null,
        );
    }

    /**
     * @param array<mixed> $context
     */
    public function sendEngagementMessageEmail(User $user, string $locale = 'en', array $context = []): void
    {
        $this->send(
            subject: $this->translator->trans(
                id: 'email.subject.client.engagement.message',
                parameters: [
                    'title' => $context['engagement']->getTitle(),
                ],
                domain: 'email'
            ),
            template: '/email/client/message-received.html.twig',
            user: $user,
            context: $context,
            locale: $locale,
            notificationType: NotificationTypeEnum::ENGAGEMENT_MESSAGE_RECEIVED,
            dedupeKey: null,
        );
    }

    /**
     * @param array<mixed> $context
     */
    public function sendQuoteMadeEmail(User $user, string $locale = 'en', array $context = []): void
    {
        $this->send(
            subject: $this->translator->trans(
                id: 'quote-received',
                parameters: [
                    'firstName' => $user->getFirstName(),
                ],
                domain: 'email'
            ),
            template: '/email/client/quote.html.twig',
            user: $user,
            context: $context,
            locale: $locale,
            notificationType: NotificationTypeEnum::QUOTE_RECEIVED,
            dedupeKey: null,
        );
    }

    /**
     * @param array<mixed> $context
     */
    public function sendTraderServiceRadiusMissingEmail(User $user, string $locale = 'en', array $context = []): void
    {
        $this->send(
            subject: $this->translator->trans(
                id: 'email.trader.subject.service-radius-missing',
                parameters: [
                    'firstName' => $user->getFirstName(),
                ],
                domain: 'email'
            ),
            template: '/email/trader/service-radius-missing.html.twig',
            user: $user,
            context: $context,
            locale: $locale,
            notificationType: NotificationTypeEnum::MISSING_SERVICE_RADIUS,
            dedupeKey: 'v1:' . CarbonImmutable::now()->format('Y-m'),
        );
    }

    /**
     * @param array<mixed> $context
     */
    public function sendPasswordResetEmail(User $user, string $locale = 'en', array $context = []): void
    {
        $this->send(
            subject: $this->translator->trans(
                id: 'password-reset',
                parameters: [
                    'firstName' => $user->getFirstName(),
                ],
                domain: 'email'
            ),
            template: '/email/security/password-reset.html.twig',
            user: $user,
            context: $context,
            locale: $locale,
            notificationType: NotificationTypeEnum::PASSWORD_RESET,
            dedupeKey: null,
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    public function sendIssueRaisedEmail(?User $user, User $trader, Engagement $engagement, string $locale, array $context = []): void
    {
        $this->send(
            subject: $this->translator->trans(
                id: 'issue-raised',
                domain: 'email'
            ),
            template: '/email/engagement/issue-raised.html.twig',
            user: $user,
            context: $context,
            locale: $locale,
            notificationType: NotificationTypeEnum::ENGAGEMENT_ISSUE,
            dedupeKey: null,
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    public function sendTraderReviewReceivedEmail(User $user, string $locale = 'cs', array $context = []): void
    {
        $this->send(
            subject: $this->translator->trans(
                id: 'review-received',
                domain: 'email'
            ),
            template: '/email/engagement/review-received.html.twig',
            user: $user,
            context: $context,
            locale: $locale,
            notificationType: NotificationTypeEnum::TRADER_ENGAGEMENT_REVIEW,
            dedupeKey: null,
        );
    }

    /**
     * @param array<string|int|object> $context
     */
    private function compose(
        string $subject,
        string $template,
        User $user,
        array $context,
        string $locale = 'en'
    ): TemplatedEmail {
        $templatedEmail = new TemplatedEmail();
        $templatedEmail->locale($locale);
        $templatedEmail->from(addresses: self::SENDER_EMAIL_ADDRESS);
        $templatedEmail->to(address: new Address($user->getEmail(), $user->getFullName()));
        $templatedEmail->subject(subject: $subject);
        $templatedEmail->htmlTemplate(template: $template);
        $templatedEmail->context(context: $context);

        return $templatedEmail;
    }

    /**
     * @param array<string|int|object> $context
     * @param array<mixed>             $notificationContext
     */
    private function send(
        string $subject,
        string $template,
        User $user,
        array $context,
        string $locale,
        NotificationTypeEnum $notificationType,
        ?string $dedupeKey = null,
        array $notificationContext = [],
    ): void {
        $envelope = $this->compose(
            subject: $subject,
            template: $template,
            user: $user,
            context: $context,
            locale: $locale
        );

        $isSuccessful = true;
        $error = null;

        try {
            $this->mailer->send($envelope);
        } catch (Exception $exception) {
            $isSuccessful = false;
            $error = $exception->getMessage();
        }

        $this->dispatcher->dispatch(new NotificationSentEvent(
            user: $user,
            type: $notificationType,
            channel: NotificationChannelEnum::EMAIL,
            locale: $locale,
            dedupeKey: $dedupeKey,
            template: $template,
            context: $notificationContext,
            success: $isSuccessful,
            providerMessageId: null,
            errorMessage: $error,
        ));
    }
}
