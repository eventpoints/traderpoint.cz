<?php

declare(strict_types=1);

namespace App\EventSubscriber\Workflow;

use App\Entity\Engagement;
use App\Service\EmailService\EmailService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;

class QuoteAcceptedNotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EmailService $emailService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.engagement.completed.accept_quote' => ['onQuoteAccepted'],
        ];
    }

    public function onQuoteAccepted(CompletedEvent $event): void
    {
        /** @var Engagement $engagement */
        $engagement = $event->getSubject();
        $acceptedQuote = $engagement->getQuote();

        if ($acceptedQuote === null) {
            return;
        }

        // Notify tradesman their quote was accepted
        $this->emailService->sendQuoteAcceptedEmail(
            $acceptedQuote->getTrader()->getUser(),
            $engagement
        );

        // Notify other tradesmen their quotes were not selected
        foreach ($engagement->getQuotes() as $quote) {
            if ($quote->getId() !== $acceptedQuote->getId() && $quote->isOpen()) {
                $this->emailService->sendQuoteRejectedEmail(
                    $quote->getTrader()->getUser(),
                    $engagement
                );
            }
        }
    }
}
