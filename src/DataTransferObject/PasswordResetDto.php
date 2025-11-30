<?php

namespace App\DataTransferObject;

final class PasswordResetDto
{
    public function __construct(
        private null|string $email = null
    )
    {
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }
}