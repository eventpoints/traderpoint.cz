<?php

namespace App\DataTransferObject;

class NearestCityDto
{

    public function __construct(
        public string $cityName,
        public float $distance,
    )
    {
    }

    public function getCityName(): string
    {
        return $this->cityName;
    }

    public function getDistance(): float
    {
        return $this->distance;
    }


}