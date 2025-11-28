<?php

namespace App\DataTransferObject;

use App\Entity\Engagement;

final class MapLocationDto
{
    public function __construct(
        private null|float  $latitude,
        private null|float  $longitude,
        private null|string $address,
        private null|int    $radiusKm
    )
    {
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): void
    {
        $this->latitude = $latitude;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): void
    {
        $this->longitude = $longitude;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): void
    {
        $this->address = $address;
    }

    public function getRadiusKm(): ?int
    {
        return $this->radiusKm;
    }

    public function setRadiusKm(?int $radiusKm): void
    {
        $this->radiusKm = $radiusKm;
    }


    public static function getFromEngagement(Engagement $engagement) : null|MapLocationDto
    {
        if (empty($engagement->getLatitude()) ||
            empty($engagement->getLongitude())
        ) {
            return null;
        } else {
            return new MapLocationDto(
                (float)$engagement->getLatitude(),
                (float)$engagement->getLongitude(),
                (string)($engagement->getAddress() ?? ''),
                null
            );
        }
    }
}