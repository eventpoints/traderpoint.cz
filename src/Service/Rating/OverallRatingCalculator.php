<?php

namespace App\Service\Rating;

final readonly class OverallRatingCalculator
{
    public function calculate(
        float $responseRating,
        float $customerServicesRating,
        float $workQualityRating,
        float $valueForMoneyRating,
    ): string
    {
        $totalRating = $responseRating + $customerServicesRating + $workQualityRating + $valueForMoneyRating;
        $overallRating = $totalRating / 4;
        return (string)round($overallRating, 2);
    }
}