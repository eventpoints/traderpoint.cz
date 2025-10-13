<?php

declare(strict_types=1);

namespace App\Service;

use App\Data\MajorCzechCities;
use App\DataTransferObject\CityDto;
use App\DataTransferObject\NearestCityDto;
use NumberFormatter;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class MajorCityDistance
{
    /**
     * @var array<string, CityDto>
     */
    private array $cities;

    public function __construct(
        private TranslatorInterface $translator
    )
    {
        $this->cities = MajorCzechCities::get();
    }

    /**
     * Nearest major city (raw km).
     */
    public function nearestCity(float $lat, float $lng): NearestCityDto
    {
        $best = new NearestCityDto(cityName: '', distance: INF);

        foreach ($this->cities as $city) {
            $d = $this->haversineKm($lat, $lng, $city->getLatitude(), $city->getLongitude());
            if ($d < $best->getDistance()) {
                $best = new NearestCityDto($city->getName(), $d);
            }
        }

        return $best;
    }

    /**
     * Final translated label (km only), privacy-rounded up.
     * Example: "0.3 km from Prague" / "0,3 km od Prahy".
     */
    public function formatLabel(
        float $lat,
        float $lng,
        float $minKm = 0.2,
        float $roundToKm = 0.1
    ): string
    {
        $dto = $this->nearestCity($lat, $lng);

        // Round UP to avoid precision leaks
        $kmRounded = max($minKm, ceil($dto->getDistance() / $roundToKm) * $roundToKm);

        // Locale-aware number formatting
        $locale = method_exists($this->translator, 'getLocale') ? $this->translator->getLocale() : 'en';
        $fmt = new NumberFormatter($locale, NumberFormatter::DECIMAL);
        if ($kmRounded < 10) {
            $fmt->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 1);
            $fmt->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 1);
        } else {
            $fmt->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 0);
            $fmt->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 0);
        }
        $distanceStr = $fmt->format($kmRounded);

        return $this->translator->trans('distance.from', [
            '%distance%' => $distanceStr,
            '%place%' => $dto->getCityName(),
        ]);
    }

    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R = 6371.0088;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return $R * 2 * asin(min(1, sqrt($a)));
    }
}
