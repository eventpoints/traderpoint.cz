<?php

declare(strict_types=1);

namespace App\Workflow;

use App\Entity\Engagement;
use App\Entity\User;
use App\Enum\QuoteStatusEnum;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\GuardEvent;

class EngagementWorkflowGuard implements EventSubscriberInterface
{
    public function __construct(
        private readonly Security $security,
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.engagement.guard.accept_quote' => ['guardAcceptQuote'],
            'workflow.engagement.guard.start_work' => ['guardStartWork'],
            'workflow.engagement.guard.complete_work' => ['guardCompleteWork'],
            'workflow.engagement.guard.raise_issue' => ['guardRaiseIssue'],
            'workflow.engagement.guard.submit_review' => ['guardSubmitReview'],
            'workflow.engagement.guard.cancel' => ['guardCancel'],
        ];
    }

    public function guardAcceptQuote(GuardEvent $event): void
    {
        /** @var Engagement $engagement */
        $engagement = $event->getSubject();
        $user = $this->security->getUser();

        // Only the engagement owner can accept a quote
        if (! $user instanceof User || $engagement->getOwner()->getId() !== $user->getId()) {
            $event->setBlocked(true, 'Only the engagement owner can accept a quote.');
            return;
        }

        // Must have at least one submitted quote
        $hasSubmittedQuote = false;
        foreach ($engagement->getQuotes() as $quote) {
            if ($quote->isOpen()) {
                $hasSubmittedQuote = true;
                break;
            }
        }

        if (! $hasSubmittedQuote) {
            $event->setBlocked(true, 'No valid quotes available to accept.');
        }
    }

    public function guardStartWork(GuardEvent $event): void
    {
        /** @var Engagement $engagement */
        $engagement = $event->getSubject();
        $user = $this->security->getUser();

        // Must have an accepted quote
        if ($engagement->getQuote() === null) {
            $event->setBlocked(true, 'No quote has been accepted for this engagement.');
            return;
        }

        // Only the tradesman who owns the accepted quote can start work
        if (! $user instanceof User || $engagement->getQuote()->getOwner()->getId() !== $user->getId()) {
            $event->setBlocked(true, 'Only the tradesman who owns the accepted quote can start work.');
            return;
        }

        // Ensure quote is in accepted status
        if ($engagement->getQuote()->getStatus() !== QuoteStatusEnum::ACCEPTED) {
            $event->setBlocked(true, 'The quote must be in accepted status to start work.');
        }
    }

    public function guardCompleteWork(GuardEvent $event): void
    {
        /** @var Engagement $engagement */
        $engagement = $event->getSubject();
        $user = $this->security->getUser();

        // Must have an accepted quote
        if ($engagement->getQuote() === null) {
            $event->setBlocked(true, 'No quote has been accepted for this engagement.');
            return;
        }

        // Only the tradesman can mark work as complete
        if (! $user instanceof User || $engagement->getQuote()->getOwner()->getId() !== $user->getId()) {
            $event->setBlocked(true, 'Only the tradesman can mark work as complete.');
        }
    }

    public function guardRaiseIssue(GuardEvent $event): void
    {
        /** @var Engagement $engagement */
        $engagement = $event->getSubject();
        $user = $this->security->getUser();

        if (! $user instanceof User) {
            $event->setBlocked(true, 'You must be logged in to raise an issue.');
            return;
        }

        // Must have an accepted quote
        if ($engagement->getQuote() === null) {
            $event->setBlocked(true, 'Cannot raise an issue without an accepted quote.');
            return;
        }

        // Only engagement owner or the tradesman can raise an issue
        $isOwner = $engagement->getOwner()->getId() === $user->getId();
        $isTradesman = $engagement->getQuote()->getOwner()->getId() === $user->getId();

        if (! $isOwner && ! $isTradesman) {
            $event->setBlocked(true, 'Only the engagement owner or tradesman can raise an issue.');
        }
    }

    public function guardSubmitReview(GuardEvent $event): void
    {
        /** @var Engagement $engagement */
        $engagement = $event->getSubject();
        $user = $this->security->getUser();

        // Only the engagement owner can submit a review
        if (! $user instanceof User || $engagement->getOwner()->getId() !== $user->getId()) {
            $event->setBlocked(true, 'Only the engagement owner can submit a review.');
        }
    }

    public function guardCancel(GuardEvent $event): void
    {
        /** @var Engagement $engagement */
        $engagement = $event->getSubject();
        $user = $this->security->getUser();

        if (! $user instanceof User) {
            $event->setBlocked(true, 'You must be logged in to cancel an engagement.');
            return;
        }

        // Only engagement owner or admin can cancel
        $isOwner = $engagement->getOwner()->getId() === $user->getId();
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');

        if (! $isOwner && ! $isAdmin) {
            $event->setBlocked(true, 'Only the engagement owner or an admin can cancel an engagement.');
        }
    }
}
