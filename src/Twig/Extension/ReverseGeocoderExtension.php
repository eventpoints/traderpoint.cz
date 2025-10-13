<?php
declare(strict_types=1);

namespace App\Twig\Extension;

use App\Service\ReverseGeocoder;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ReverseGeocoderExtension extends AbstractExtension
{
    public function __construct(private ReverseGeocoder $geocoder) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('reverse_geocode', [$this, 'lookup']),
        ];
    }

    public function lookup(?float $latitude, ?float $longitude): string
    {
        if ($latitude === null || $longitude === null) {
            return '';
        }

        return $this->geocoder->lookup($latitude, $longitude) ?? '';
    }
}
