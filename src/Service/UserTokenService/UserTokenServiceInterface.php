<?php

namespace App\Service\UserTokenService;

use App\Entity\User;
use App\Entity\UserToken;
use App\Enum\UserTokenPurposeEnum;
use Symfony\Component\Uid\Uuid;

interface UserTokenServiceInterface
{
    public function issueToken(
        User $user,
        UserTokenPurposeEnum $purpose,
        int $ttlMinutes = 60
    ): UserToken;

    public function findActiveByValueAndPurpose(
        Uuid $value,
        UserTokenPurposeEnum $purpose
    ): ?UserToken;

    public function consume(UserToken $token, bool $flush = true): void;

    public function consumeAllForUserAndPurpose(User $user, UserTokenPurposeEnum $purpose): void;

    public function findLatestActiveByUserAndPurpose(
        User $user,
        UserTokenPurposeEnum $purpose
    ): ?UserToken;
}