<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Engagement;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class EngagementWorkflowProgress
{
    public Engagement $engagement;

    public string $viewType = 'client'; // 'client' or 'trader'

    public bool $hasSubmittedQuote = false; // For trader view - whether they've submitted a quote

    public bool $isQuoteRejected = false; // For trader view - whether their quote was rejected

    public ?int $quoteVersion = null; // For trader view - version of their quote

    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    /**
     * @return array<array{place: string, label: string}>
     */
    public function getSteps(): array
    {
        $versionSuffix = $this->quoteVersion ? ' (v' . $this->quoteVersion . ')' : '';

        if ($this->viewType === 'trader') {
            // If quote was rejected, show different journey
            if ($this->isQuoteRejected) {
                return [
                    [
                        'place' => 'QUOTE_SUBMITTED',
                        'label' => $this->translator->trans('workflow.quote-submitted') . $versionSuffix,
                    ],
                    [
                        'place' => 'QUOTE_REJECTED',
                        'label' => $this->translator->trans('workflow.quote-rejected') . $versionSuffix,
                    ],
                ];
            }

            // Trader perspective: their journey after submitting a quote
            return [
                [
                    'place' => 'QUOTE_SUBMITTED',
                    'label' => $this->translator->trans('workflow.quote-submitted') . $versionSuffix,
                ],
                [
                    'place' => 'QUOTE_ACCEPTED',
                    'label' => $this->translator->trans('workflow.quote-accepted') . $versionSuffix,
                ],
                [
                    'place' => 'IN_PROGRESS',
                    'label' => $this->isInIssue() ? $this->translator->trans('workflow.issue-resolution') : $this->translator->trans('workflow.in-progress'),
                ],
                [
                    'place' => 'WORK_COMPLETED',
                    'label' => $this->translator->trans('workflow.work-completed'),
                ],
            ];
        }

        // Client view
        return [
            [
                'place' => 'UNDER_ADMIN_REVIEW',
                'label' => $this->translator->trans('workflow.under-review'),
            ],
            [
                'place' => 'RECEIVING_QUOTES',
                'label' => $this->translator->trans('workflow.open-for-quotes'),
            ],
            [
                'place' => 'QUOTE_ACCEPTED',
                'label' => $this->translator->trans('workflow.quote-accepted'),
            ],
            [
                'place' => 'IN_PROGRESS',
                'label' => $this->isInIssue() ? $this->translator->trans('workflow.issue-resolution') : $this->translator->trans('workflow.in-progress'),
            ],
            [
                'place' => 'WORK_COMPLETED',
                'label' => $this->translator->trans('workflow.work-completed'),
            ],
        ];
    }

    public function getCurrentPlace(): string
    {
        return $this->engagement->getStatus()->value;
    }

    public function isTerminal(): bool
    {
        return in_array($this->getCurrentPlace(), ['REJECTED', 'CANCELLED'], true);
    }

    public function isInIssue(): bool
    {
        return $this->getCurrentPlace() === 'ISSUE_RESOLUTION';
    }

    public function isPostCompletion(): bool
    {
        return in_array($this->getCurrentPlace(), ['AWAITING_REVIEW', 'REVIEWED'], true);
    }

    public function shouldShowProgress(): bool
    {
        // Client always sees progress
        if ($this->viewType === 'client') {
            return true;
        }

        // Trader only sees progress if they've submitted a quote
        return $this->hasSubmittedQuote;
    }

    public function getCurrentIndex(): int
    {
        $steps = $this->getSteps();
        $currentPlace = $this->getCurrentPlace();

        // Trader view has different mapping (only shown if they submitted a quote)
        if ($this->viewType === 'trader' && $this->hasSubmittedQuote) {
            // If quote was rejected, always show as step 1 (Quote Rejected)
            if ($this->isQuoteRejected) {
                return 1; // Quote Rejected is the last/second step
            }

            return match ($currentPlace) {
                'UNDER_ADMIN_REVIEW', 'RECEIVING_QUOTES' => 0, // Quote Submitted (awaiting response)
                'QUOTE_ACCEPTED' => 1,
                'IN_PROGRESS' => 2,
                'WORK_COMPLETED', 'AWAITING_REVIEW', 'REVIEWED' => 3,
                'ISSUE_RESOLUTION' => 2, // Issue during work
                default => 0,
            };
        }

        // Client view - direct mapping
        foreach ($steps as $index => $step) {
            if ($step['place'] === $currentPlace) {
                return $index;
            }
        }

        // Issue resolution sits around IN_PROGRESS
        if ($this->isInIssue()) {
            // Find IN_PROGRESS index dynamically
            foreach ($steps as $index => $step) {
                if ($step['place'] === 'IN_PROGRESS') {
                    return $index;
                }
            }
        }

        // Post-completion shows as last step complete
        if ($this->isPostCompletion()) {
            return count($steps) - 1;
        }

        return 0;
    }

    public function getProgress(): float
    {
        if ($this->isTerminal()) {
            return 0.0;
        }

        $steps = $this->getSteps();
        $currentIndex = $this->getCurrentIndex();
        $progressIndex = $currentIndex + ($this->isInIssue() ? 0.5 : 0);

        return ($progressIndex / (count($steps) - 1)) * 100;
    }

    public function getProgressBarColor(): string
    {
        // Trader quote rejected
        if ($this->viewType === 'trader' && $this->isQuoteRejected) {
            return 'bg-danger';
        }

        if ($this->isTerminal()) {
            return 'bg-danger';
        }

        if ($this->isInIssue()) {
            return 'bg-warning';
        }

        if ($this->isPostCompletion()) {
            return 'bg-success';
        }

        return 'bg-primary';
    }

    public function getCurrentStatusLabel(): string
    {
        $currentPlace = $this->getCurrentPlace();
        $versionSuffix = $this->quoteVersion ? ' (v' . $this->quoteVersion . ')' : '';

        // Trader-specific labels
        if ($this->viewType === 'trader') {
            // If quote was rejected, show that regardless of actual workflow state
            if ($this->isQuoteRejected) {
                return $this->translator->trans('workflow.quote-rejected') . $versionSuffix;
            }

            return match ($currentPlace) {
                'UNDER_ADMIN_REVIEW' => $this->translator->trans('workflow.under-review'),
                'RECEIVING_QUOTES' => $this->translator->trans('workflow.quote-submitted') . $versionSuffix,
                'QUOTE_ACCEPTED' => $this->translator->trans('workflow.quote-accepted') . $versionSuffix,
                'IN_PROGRESS' => $this->translator->trans('workflow.in-progress'),
                'ISSUE_RESOLUTION' => $this->translator->trans('workflow.issue-resolution'),
                'WORK_COMPLETED' => $this->translator->trans('workflow.work-completed'),
                'AWAITING_REVIEW' => $this->translator->trans('workflow.awaiting-review'),
                'REVIEWED' => $this->translator->trans('workflow.reviewed'),
                'REJECTED' => $this->translator->trans('workflow.rejected'),
                'CANCELLED' => $this->translator->trans('workflow.cancelled'),
                default => $currentPlace,
            };
        }

        // Client labels
        return match ($currentPlace) {
            'UNDER_ADMIN_REVIEW' => $this->translator->trans('workflow.under-review'),
            'RECEIVING_QUOTES' => $this->translator->trans('workflow.open-for-quotes'),
            'QUOTE_ACCEPTED' => $this->translator->trans('workflow.quote-accepted'),
            'IN_PROGRESS' => $this->translator->trans('workflow.in-progress'),
            'ISSUE_RESOLUTION' => $this->translator->trans('workflow.issue-resolution'),
            'WORK_COMPLETED' => $this->translator->trans('workflow.work-completed'),
            'AWAITING_REVIEW' => $this->translator->trans('workflow.awaiting-review'),
            'REVIEWED' => $this->translator->trans('workflow.reviewed'),
            'REJECTED' => $this->translator->trans('workflow.rejected'),
            'CANCELLED' => $this->translator->trans('workflow.cancelled'),
            default => $currentPlace,
        };
    }

    public function getCurrentStatusVariant(): string
    {
        // Trader quote rejected
        if ($this->viewType === 'trader' && $this->isQuoteRejected) {
            return 'danger';
        }

        if ($this->isTerminal()) {
            return 'danger';
        }

        if ($this->isInIssue()) {
            return 'warning';
        }

        if ($this->isPostCompletion()) {
            return 'success';
        }

        return 'primary';
    }
}
