<?php

namespace App\Service\UserTokenVerifier;

use App\Entity\User;

final readonly class UserTokenVerificationResult
{
    public function __construct(
        private User $user,
        private ?string $jti = null
    )
    {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getJti(): ?string
    {
        return $this->jti;
    }
}