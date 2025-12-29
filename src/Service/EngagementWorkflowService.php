<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Engagement;
use App\Entity\EngagementIssue;
use App\Entity\Quote;
use App\Entity\Review;
use App\Enum\QuoteStatusEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class EngagementWorkflowService
{
    public function __construct(
        private readonly WorkflowInterface $engagementStateMachine,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function approve(Engagement $engagement): void
    {
        $this->apply($engagement, 'approve');
    }

    public function reject(Engagement $engagement, ?string $reason = null): void
    {
        if ($reason !== null) {
            // Store rejection reason in engagement metadata or add a new field
            // For now, we'll just apply the transition
        }

        $this->apply($engagement, 'reject');
    }

    public function acceptQuote(Engagement $engagement, Quote $quote): void
    {
        // Temporarily store the quote to accept
        $quoteToAccept = $quote;

        // Apply workflow transition first
        $this->apply($engagement, 'accept_quote');

        // Apply domain logic from Engagement::accept() method AFTER workflow transition
        // Set the chosen quote
        $engagement->setQuote($quoteToAccept);
        $quoteToAccept->setStatus(QuoteStatusEnum::ACCEPTED);

        // Reject or supersede other quotes
        foreach ($engagement->getQuotes() as $otherQuote) {
            if ($otherQuote->getId() !== $quoteToAccept->getId() && $otherQuote->getStatus() === QuoteStatusEnum::SUBMITTED) {
                $otherQuote->supersede();
            }
        }

        // Flush changes
        $this->entityManager->flush();
    }

    public function startWork(Engagement $engagement): void
    {
        $this->apply($engagement, 'start_work');
    }

    public function completeWork(Engagement $engagement): void
    {
        $this->apply($engagement, 'complete_work');
    }

    public function raiseIssue(Engagement $engagement, EngagementIssue $issue): void
    {
        // Persist the issue first
        $this->entityManager->persist($issue);

        // Apply workflow transition
        $this->apply($engagement, 'raise_issue');
    }

    public function resolveIssue(Engagement $engagement, bool $continueWork): void
    {
        if ($continueWork) {
            // Issue resolved, continue with current work
            $this->apply($engagement, 'resolve_issue_continue');
        } else {
            // Issue resolved, but restart engagement (clear quote, back to accepting quotes)
            // Clear the chosen quote
            $currentQuote = $engagement->getQuote();
            if ($currentQuote instanceof \App\Entity\Quote) {
                $currentQuote->supersede();
                $engagement->setQuote(null);
            }

            $this->apply($engagement, 'resolve_issue_restart');
        }
    }

    public function requestReview(Engagement $engagement): void
    {
        $this->apply($engagement, 'request_review');
    }

    public function toReviewed(Engagement $engagement, Review $review): void
    {
        $this->apply($engagement, 'submit_review');
    }

    public function cancel(Engagement $engagement, ?string $reason = null): void
    {
        // Store cancellation metadata
        if ($reason !== null) {
            // Store cancellation reason (could add a field to Engagement)
        }

        $this->apply($engagement, 'cancel');
    }

    public function can(Engagement $engagement, string $transitionName): bool
    {
        return $this->engagementStateMachine->can($engagement, $transitionName);
    }

    /**
     * @return array<string>
     */
    public function getAvailableTransitions(Engagement $engagement): array
    {
        $enabledTransitions = $this->engagementStateMachine->getEnabledTransitions($engagement);

        return array_map(
            static fn($transition): string => $transition->getName(),
            $enabledTransitions
        );
    }

    private function apply(Engagement $engagement, string $transitionName): void
    {
        if (! $this->engagementStateMachine->can($engagement, $transitionName)) {
            throw new \LogicException(
                sprintf(
                    'Cannot apply transition "%s" to engagement %s in state "%s"',
                    $transitionName,
                    $engagement->getId(),
                    $engagement->getStatus()?->value ?? 'null'
                )
            );
        }

        $this->engagementStateMachine->apply($engagement, $transitionName);
        $this->entityManager->flush();
    }
}
