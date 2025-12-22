<?php

declare(strict_types=1);

namespace App\EventSubscriber\Workflow;

use App\Entity\Engagement;
use App\Entity\User;
use App\Service\EmailService\EmailService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Workflow\Event\CompletedEvent;

class IssueNotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EmailService $emailService,
        private readonly Security     $security,
        private readonly RequestStack $requestStack,
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.engagement.completed.raise_issue' => ['onIssueRaised'],
        ];
    }

    public function onIssueRaised(CompletedEvent $event): void
    {
        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            return;
        }

        /** @var Engagement $engagement */
        $engagement = $event->getSubject();
        $quote = $engagement->getQuote();

        if ($quote === null) {
            return;
        }

        $this->emailService->sendIssueRaisedEmail(
            user: $engagement->getOwner(),
            trader: $quote->getOwner(),
            engagement: $engagement,
            locale: $this->requestStack->getCurrentRequest()?->getLocale()
        );
    }
}
