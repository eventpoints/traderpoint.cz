<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Engagement;
use App\Entity\User;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class EngagementCard
{
    public Engagement $engagement;
    public string $viewType = 'trader'; // 'trader' or 'client'
    public string $viewMode = 'detailed'; // 'simple' or 'detailed'
    public ?User $currentUser = null; // For calculating distance if trader
    public ?float $distance = null; // Distance in km (for trader view)
    public bool $showImages = true;
    public bool $showDescription = true;
    public bool $showFooter = true;
    public bool $showReactions = false; // Show reactions instead of viewers
    public bool $showViewButton = false; // Show view/arrow button in footer
    public bool $showDateInHeader = false; // Show created date in header next to user name
    public bool $showQuoteCountInHeader = false; // Show quote count badge in header

    public function isSimpleView(): bool
    {
        return $this->viewMode === 'simple';
    }

    public function isDetailedView(): bool
    {
        return $this->viewMode === 'detailed';
    }

    public function isTraderView(): bool
    {
        return $this->viewType === 'trader';
    }

    public function isClientView(): bool
    {
        return $this->viewType === 'client';
    }

    /**
     * Get the person to display in the card header
     * - Trader view: shows client (engagement owner)
     * - Client view: could show trader if quote accepted
     */
    public function getDisplayUser(): User
    {
        if ($this->isTraderView()) {
            return $this->engagement->getOwner(); // Show client
        }

        // Client view - show trader if quote accepted
        if ($this->engagement->getQuote()) {
            return $this->engagement->getQuote()->getOwner();
        }

        return $this->engagement->getOwner(); // Fallback to owner
    }

    public function getProfileRoute(): string
    {
        if ($this->isTraderView()) {
            return 'client_profile';
        }

        return 'trader_profile';
    }

    public function getEngagementRoute(): string
    {
        if ($this->isTraderView()) {
            return 'trader_show_engagement';
        }

        return 'client_show_engagement';
    }
}
