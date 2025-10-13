<?php

namespace App\Data;

use App\DataTransferObject\CityDto;

class MajorCzechCities
{
    public static function get(): array
    {
        return [
            'Prague' => new CityDto('Prague', 50.0755, 14.4378),
            'Brno' => new CityDto('Brno', 49.1951, 16.6068),
            'Ostrava' => new CityDto('Ostrava', 49.8209, 18.2625),
            'Plzeň' => new CityDto('Plzeň', 49.7384, 13.3736),
            'Liberec' => new CityDto('Liberec', 50.7671, 15.0562),
            'Olomouc' => new CityDto('Olomouc', 49.5938, 17.2509),
            'České Budějovice' => new CityDto('České Budějovice', 48.9757, 14.4800),
            'Hradec Králové' => new CityDto('Hradec Králové', 50.2092, 15.8328),
            'Ústí nad Labem' => new CityDto('Ústí nad Labem', 50.6605, 14.0322),
            'Pardubice' => new CityDto('Pardubice', 50.0343, 15.7812),
            'Zlín' => new CityDto('Zlín', 49.2244, 17.6670),
            'Jihlava' => new CityDto('Jihlava', 49.3960, 15.5912),
        ];
    }
}