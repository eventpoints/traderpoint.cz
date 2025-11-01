<?php

namespace App\DataTransferObject;

use Carbon\CarbonImmutable;

final readonly class VerificationResultDto
{
    public function __construct(
        private string $destination,
        private CarbonImmutable $expiresAt,
    ){}

    public function getDestination(): string
    {
        return $this->destination;
    }

    public function getExpiresAt(): CarbonImmutable
    {
        return $this->expiresAt;
    }

    public function getRemainingSeconds(): int
    {
        $now = CarbonImmutable::now($this->getExpiresAt()->getTimezone());
        $seconds = $this->getExpiresAt()->diffInSeconds($now, false);
        return max(0, $seconds);
    }
}