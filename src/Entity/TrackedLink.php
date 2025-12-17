<?php

namespace App\Entity;

use App\Enum\TrackedLinkSourceEnum;
use App\Repository\TrackedLinkRepository;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: TrackedLinkRepository::class)]
#[ORM\Index(columns: ['code'])]
class TrackedLink implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $code = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $originalUrl = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?AdvertisingCampaign $advertisingCampaign = null;

    #[ORM\Column(enumType: TrackedLinkSourceEnum::class, nullable: true)]
    private ?TrackedLinkSourceEnum $source = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $clickCount = 0;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isActive = true;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?CarbonImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $lastClickedAt = null;

    public function __construct()
    {
        $this->createdAt = new CarbonImmutable();
        $this->updatedAt = new CarbonImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getOriginalUrl(): ?string
    {
        return $this->originalUrl;
    }

    public function setOriginalUrl(string $originalUrl): static
    {
        $this->originalUrl = $originalUrl;

        return $this;
    }

    public function getAdvertisingCampaign(): ?AdvertisingCampaign
    {
        return $this->advertisingCampaign;
    }

    public function setAdvertisingCampaign(?AdvertisingCampaign $advertisingCampaign): static
    {
        $this->advertisingCampaign = $advertisingCampaign;

        return $this;
    }

    public function getSource(): ?TrackedLinkSourceEnum
    {
        return $this->source;
    }

    public function setSource(?TrackedLinkSourceEnum $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getClickCount(): int
    {
        return $this->clickCount;
    }

    public function setClickCount(int $clickCount): static
    {
        $this->clickCount = $clickCount;

        return $this;
    }

    public function incrementClickCount(): static
    {
        $this->clickCount++;
        $this->lastClickedAt = new DateTimeImmutable();

        return $this;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getCreatedAt(): ?CarbonImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?CarbonImmutable
    {
        return $this->updatedAt instanceof DateTimeImmutable ? CarbonImmutable::instance($this->updatedAt) : null;
    }

    public function setUpdatedAt(?DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getLastClickedAt(): ?DateTimeImmutable
    {
        return $this->lastClickedAt;
    }

    public function setLastClickedAt(?DateTimeImmutable $lastClickedAt): static
    {
        $this->lastClickedAt = $lastClickedAt;

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->code;
    }
}