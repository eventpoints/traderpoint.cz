<?php

namespace App\DataFixtures\Data;

class CityData
{
    /**
     * @return array<array<string, mixed>>
     */
    public static function getCzechCities(): array
    {
        return [
            [
                'name' => 'Prague',
                'latitude' => 50.0755,
                'longitude' => 14.4378,
                'country_code' => 'CZ',
                'capital' => true,
            ],
            [
                'name' => 'Brno',
                'latitude' => 49.1951,
                'longitude' => 16.6068,
                'country_code' => 'CZ',
                'capital' => false,
            ],
            [
                'name' => 'Ostrava',
                'latitude' => 49.8209,
                'longitude' => 18.2625,
                'country_code' => 'CZ',
                'capital' => false,
            ],
            [
                'name' => 'Plzeň',
                'latitude' => 49.7384,
                'longitude' => 13.3736,
                'country_code' => 'CZ',
                'capital' => false,
            ],
            [
                'name' => 'Liberec',
                'latitude' => 50.7671,
                'longitude' => 15.0565,
                'country_code' => 'CZ',
                'capital' => false,
            ],
            [
                'name' => 'Olomouc',
                'latitude' => 49.5938,
                'longitude' => 17.2508,
                'country_code' => 'CZ',
                'capital' => false,
            ],
            [
                'name' => 'České Budějovice',
                'latitude' => 48.9745,
                'longitude' => 14.4744,
                'country_code' => 'CZ',
                'capital' => false,
            ],
            [
                'name' => 'Hradec Králové',
                'latitude' => 50.2093,
                'longitude' => 15.8323,
                'country_code' => 'CZ',
                'capital' => false,
            ],
            [
                'name' => 'Ústí nad Labem',
                'latitude' => 50.6607,
                'longitude' => 14.0328,
                'country_code' => 'CZ',
                'capital' => false,
            ],
            [
                'name' => 'Pardubice',
                'latitude' => 50.0379,
                'longitude' => 15.7815,
                'country_code' => 'CZ',
                'capital' => false,
            ],
            [
                'name' => 'Karlovy Vary',
                'latitude' => 50.2310,
                'longitude' => 12.8713,
                'country_code' => 'CZ',
                'capital' => false,
            ],
            [
                'name' => 'Český Krumlov',
                'latitude' => 48.8116,
                'longitude' => 14.3140,
                'country_code' => 'CZ',
                'capital' => false,
            ],
            [
                'name' => 'Zlín',
                'latitude' => 49.2264,
                'longitude' => 17.6677,
                'country_code' => 'CZ',
                'capital' => false,
            ],
            [
                'name' => 'Česká Lípa',
                'latitude' => 50.6855,
                'longitude' => 14.5390,
                'country_code' => 'CZ',
                'capital' => false,
            ],
        ];
    }
}
