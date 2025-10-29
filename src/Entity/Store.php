<?php

declare(strict_types=1);

namespace App\Entity;

use Carbon\CarbonImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[UniqueEntity('code')]
#[ORM\HasLifecycleCallbacks]
class Store
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?Uuid $id = null;

    /**
     * Optional override of the partner default % for this store.
     */
    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $percentOverride = null; // null => inherit from partner

    #[ORM\Column(type: 'boolean', options: [
        'default' => true,
    ])]
    private bool $active = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private CarbonImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private CarbonImmutable $updatedAt;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Partner::class, inversedBy: 'stores')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private Partner $partner,
        #[ORM\Column(length: 160)]
        private string $name,
        #[ORM\Column(length: 160)]
        private string $slug,
        #[ORM\Column(length: 16, unique: true)]
        private string $code
    )
    {
        $this->createdAt = CarbonImmutable::now();
        $this->updatedAt = CarbonImmutable::now();
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = CarbonImmutable::now();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getPartner(): Partner
    {
        return $this->partner;
    }

    public function setPartner(Partner $p): self
    {
        $this->partner = $p;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    /**
     * Effective % for this store (override or partner default).
     */
    public function getEffectivePercent(): int
    {
        return $this->percentOverride ?? $this->partner->getMemberPercent();
    }

    public function getPercentOverride(): ?int
    {
        return $this->percentOverride;
    }

    public function getCreatedAt(): CarbonImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): CarbonImmutable
    {
        return $this->updatedAt;
    }

    public function setPercentOverride(?int $percentOverride): void
    {
        $this->percentOverride = $percentOverride;
    }

    public function setUpdatedAt(CarbonImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
