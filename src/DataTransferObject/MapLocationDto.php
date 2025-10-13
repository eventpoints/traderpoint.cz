<?php

namespace App\DataTransferObject;

final class MapLocationDto
{
    private null|float $latitude = null;
    private null|float $longitude = null;
    private null|string $address = null;
    private null|int $radiusKm = null;

    /**
     * @param float|null $latitude
     * @param float|null $longitude
     * @param string|null $address
     * @param int|null $radiusKm
     */
    public function __construct(?float $latitude, ?float $longitude, ?string $address, ?int $radiusKm)
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->address = $address;
        $this->radiusKm = $radiusKm;
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

}