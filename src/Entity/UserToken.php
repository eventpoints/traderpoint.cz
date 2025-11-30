<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\UserTokenPurposeEnum;
use App\Repository\UserTokenRepository;
use Carbon\CarbonImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserTokenRepository::class)]
class UserToken
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $value;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private CarbonImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?CarbonImmutable $consumedAt = null;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'tokens')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private User $user,
        #[ORM\Column(enumType: UserTokenPurposeEnum::class)]
        private UserTokenPurposeEnum $purpose,
        #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
        private CarbonImmutable $expiresAt,
    )
    {
        $this->value = Uuid::v7();
        $this->createdAt = CarbonImmutable::now();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getPurpose(): UserTokenPurposeEnum
    {
        return $this->purpose;
    }

    public function getValue(): Uuid
    {
        return $this->value;
    }

    public function getExpiresAt(): CarbonImmutable
    {
        return $this->expiresAt;
    }

    public function getConsumedAt(): ?CarbonImmutable
    {
        return $this->consumedAt;
    }

    public function isExpired(): bool
    {
        return CarbonImmutable::now() >= $this->expiresAt;
    }

    public function isConsumed(): bool
    {
        return $this->consumedAt instanceof \Carbon\CarbonImmutable;
    }

    public function isActive(): bool
    {
        return ! $this->isExpired() && ! $this->isConsumed();
    }

    public function consume(): void
    {
        $this->consumedAt = CarbonImmutable::now();
    }

    public function getCreatedAt(): CarbonImmutable
    {
        return $this->createdAt;
    }

    public function getHoursUntilExpiry(): int
    {
        $now = CarbonImmutable::now(timezone: 'UTC');
        return (int) round($this->expiresAt->diffInHours($now, true), 0);
    }
}
