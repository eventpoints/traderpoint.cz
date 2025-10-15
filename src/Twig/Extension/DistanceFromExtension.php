<?php

declare(strict_types=1);

namespace App\Twig\Extension;

use App\Service\GeoDistanceService;
use App\Service\MajorCityDistance;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class DistanceFromExtension extends AbstractExtension
{
    public function __construct(private GeoDistanceService $geo)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('distance_km', [$this->geo, 'distanceKm']),
            new TwigFunction('distance_m', [$this->geo, 'distanceMeters']),
        ];
    }
}
