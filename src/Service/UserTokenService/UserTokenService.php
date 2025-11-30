<?php

declare(strict_types=1);

namespace App\Service\UserTokenService;

use App\Entity\User;
use App\Entity\UserToken;
use App\Enum\UserTokenPurposeEnum;
use App\Repository\UserTokenRepository;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class UserTokenService implements UserTokenServiceInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserTokenRepository $repo,
    )
    {
    }

    public function issueToken(
        User $user,
        UserTokenPurposeEnum $purpose,
        int $ttlMinutes = 1440
    ): UserToken
    {
        $expiresAt = CarbonImmutable::now()->addMinutes($ttlMinutes);

        $token = new UserToken($user, $purpose, $expiresAt);
        $this->em->persist($token);
        $this->em->flush();

        return $token;
    }

    public function findActiveByValueAndPurpose(
        Uuid $value,
        UserTokenPurposeEnum $purpose
    ): ?UserToken
    {
        /** @var UserToken|null $token */
        $token = $this->repo->findOneBy([
            'value' => $value,
            'purpose' => $purpose,
        ]);

        if (! $token instanceof UserToken || ! $token->isActive()) {
            return null;
        }

        return $token;
    }

    public function consume(
        UserToken $token,
        bool $flush = true
    ): void
    {
        $token->consume();
        if ($flush) {
            $this->em->flush();
        }
    }

    public function consumeAllForUserAndPurpose(
        User $user,
        UserTokenPurposeEnum $purpose
    ): void
    {
        $tokens = $this->repo->findBy([
            'user' => $user,
            'purpose' => $purpose,
        ]);
        foreach ($tokens as $t) {
            $t->consume();
        }
        $this->em->flush();
    }

    public function findLatestActiveByUserAndPurpose(
        User $user,
        UserTokenPurposeEnum $purpose
    ): ?UserToken
    {
        $tokens = $this->repo->findBy(
            [
                'user' => $user,
                'purpose' => $purpose,
            ],
            [
                'createdAt' => 'DESC',
            ]
        );

        foreach ($tokens as $token) {
            if ($token->isActive()) {
                return $token;
            }
        }

        return null;
    }
}
