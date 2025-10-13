<?php

declare(strict_types=1);

namespace App\Twig\Extension;

use App\Service\MajorCityDistance;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class DistanceFromExtension extends AbstractExtension
{
    public function __construct(
        private readonly MajorCityDistance $majorCityDistance
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('distance_from', $this->lookup(...)),
        ];
    }

    public function lookup(?float $latitude, ?float $longitude): string
    {
        if ($latitude === null || $longitude === null) {
            return '';
        }

        return $this->majorCityDistance->formatLabel($latitude, $longitude) ?? '';
    }
}
