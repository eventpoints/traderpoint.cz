<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Great-circle distance calculator using the Haversine formula.
 * Returns distances in kilometers (km).
 */
final class GeoDistanceService
{
    private const EARTH_RADIUS_KM = 6371.0088; // IUGG mean Earth radius

    /**
     * Distance between two WGS84 coordinates (lat/lng in degrees).
     *
     * @param float $lat1 Latitude of point A (degrees, -90..90)
     * @param float $lng1 Longitude of point A (degrees, -180..180)
     * @param float $lat2 Latitude of point B (degrees, -90..90)
     * @param float $lng2 Longitude of point B (degrees, -180..180)
     * @param int   $precision Number of decimals to round to (default 3)
     */
    public function distanceKm(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2,
        int $precision = 2
    ): float {
        // short-circuit identical points
        if ($lat1 === $lat2 && $lng1 === $lng2) {
            return 0.0;
        }

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos($lat1Rad) * cos($lat2Rad) * sin($dLng / 2) ** 2;

        // guard against tiny floating errors > 1.0
        $c = 2 * asin(min(1.0, sqrt($a)));

        $km = self::EARTH_RADIUS_KM * $c;

        return round($km, $precision);
    }

    /**
     * Convenience: distance in meters.
     */
    public function distanceMeters(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2,
        int $precision = 1
    ): float {
        return round($this->distanceKm($lat1, $lng1, $lat2, $lng2, 6) * 1000, $precision);
    }
}
