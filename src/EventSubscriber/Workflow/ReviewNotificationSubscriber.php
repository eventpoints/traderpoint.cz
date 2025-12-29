<?php

declare(strict_types=1);

namespace App\EventSubscriber\Workflow;

use App\Entity\Engagement;
use App\Service\EmailService\EmailService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Workflow\Event\CompletedEvent;

class ReviewNotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EmailService $emailService,
        private readonly RequestStack $requestStack,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.engagement.completed.submit_review' => ['onReviewSubmitted'],
        ];
    }

    public function onReviewSubmitted(CompletedEvent $event): void
    {
        /** @var Engagement $engagement */
        $engagement = $event->getSubject();
        $quote = $engagement->getQuote();

        if ($quote === null) {
            return;
        }

        $this->emailService->sendTraderReviewReceivedEmail(
            user: $quote->getOwner(),
            locale: $this->requestStack->getCurrentRequest()?->getLocale(),
            context: [
                'engagement' => $engagement,
            ]
        );
    }
}
