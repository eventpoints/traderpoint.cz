<?php

declare(strict_types=1);

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class RatingStars
{
    public float $rating = 0;
    public string $size = 'fs-4'; // Default font size
    public string $color = 'text-warning'; // Default color
    public bool $showValue = false; // Show numeric value
    public ?int $reviewCount = null; // Optional review count

    public function getRoundedRating(): float
    {
        // Clamp rating between 0 and 5
        $clamped = max(0, min(5, $this->rating));

        // Round to nearest 0.5
        return round($clamped * 2) / 2;
    }

    public function getFormattedRating(): string
    {
        return sprintf('%.1f', $this->rating);
    }

    public function shouldShowStar(int $starNumber, string $type): bool
    {
        $r = $this->getRoundedRating();

        return match ($type) {
            'full' => $r >= $starNumber,
            'half' => $r >= $starNumber - 0.5 && $r < $starNumber,
            'empty' => $r < $starNumber - 0.5,
            default => false,
        };
    }
}
