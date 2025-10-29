<?php

namespace App\Service\UserTokenVerifier\Contract;

use App\Service\UserTokenVerifier\UserTokenVerificationResult;
use RuntimeException;

interface UserTokenVerifierInterface
{
    /**
     * @throws RuntimeException on invalid token
     */
    public function verify(string $token): UserTokenVerificationResult;
}

