<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Quote;
use App\Entity\Engagement;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class QuoteCard
{
    public Quote $quote;
    public Engagement $engagement;
    public string $viewType = 'client'; // 'client' or 'trader'
    public bool $showActions = true; // Show accept/reject buttons (client only)
    public bool $showTraderInfo = true; // Show trader name and avatar
    public bool $showVersion = true; // Show version badge
    public bool $highlight = false; // Highlight the card (e.g., when linked from notification)
    public bool $showMessage = true; // Show quote message
    public bool $showAllDetails = true; // Show all quote details (duration, materials, warranty)
    public bool $showStatus = false; // Show status badge (trader view)
    public bool $showCreatedDate = false; // Show created date in header (trader view)

    public function isClientView(): bool
    {
        return $this->viewType === 'client';
    }

    public function isTraderView(): bool
    {
        return $this->viewType === 'trader';
    }

    public function canAcceptOrReject(): bool
    {
        return $this->isClientView() && $this->showActions && $this->quote->isOpen();
    }

    public function getBorderClass(): string
    {
        if ($this->highlight) {
            return 'shadow-lg border border-2 border-black';
        }

        if ($this->quote->getStatus()->value === 'accepted') {
            return 'border-success';
        }

        if ($this->quote->getStatus()->value === 'rejected') {
            return 'border-danger';
        }

        if ($this->quote->isExpired()) {
            return 'border-warning';
        }

        return 'border-0 shadow-sm';
    }

    public function getStatusBadgeVariant(): string
    {
        if ($this->quote->getStatus()->value === 'accepted') {
            return 'success';
        }

        if ($this->quote->getStatus()->value === 'rejected') {
            return 'danger';
        }

        if ($this->quote->isExpired()) {
            return 'warning';
        }

        return 'secondary';
    }
}
