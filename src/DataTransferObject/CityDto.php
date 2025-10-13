<?php

namespace App\DataTransferObject;

class CityDto
{
    public function __construct(
        public string $name,
        public float $latitude,
        public float $longitude,
    )
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

}