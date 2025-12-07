<?php

namespace App\Entity;

use App\Repository\FingerPrintRepository;
use Carbon\CarbonImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: FingerPrintRepository::class)]
#[ORM\HasLifecycleCallbacks]
class FingerPrint implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'fingerprints')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private null|User $owner = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private CarbonImmutable $lastSeenAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private CarbonImmutable $createdAt;

    public function __construct(
        #[ORM\Column(length: 255, unique: true, nullable: false)]
        private ?string $fingerprint
    )
    {
        $now = new CarbonImmutable();
        $this->createdAt = $now;
        $this->lastSeenAt = $now;
    }

    #[ORM\PreUpdate]
    public function updateLastSeenAt(): void
    {
        $this->lastSeenAt = new CarbonImmutable();
    }

    // (Optional but recommended) if this entity might ever be created without constructor
    #[ORM\PrePersist]
    public function initializeTimestamps(): void
    {
        $now = new CarbonImmutable();

        $this->createdAt ??= $now;
        $this->lastSeenAt ??= $now;
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getFingerprint(): ?string
    {
        return $this->fingerprint;
    }

    public function setFingerprint(?string $fingerprint): void
    {
        $this->fingerprint = $fingerprint;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): void
    {
        $this->owner = $owner;
    }

    public function getLastSeenAt(): CarbonImmutable
    {
        return $this->lastSeenAt;
    }

    public function setLastSeenAt(CarbonImmutable $lastSeenAt): void
    {
        $this->lastSeenAt = $lastSeenAt;
    }

    public function getCreatedAt(): CarbonImmutable
    {
        return $this->createdAt;
    }

    public function __toString(): string
    {
        return $this->fingerprint ?? '';
    }
}
