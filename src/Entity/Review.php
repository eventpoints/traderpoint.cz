<?php

namespace App\Entity;

use App\Repository\ReviewRepository;
use Carbon\CarbonImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    private null|Uuid $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 2, scale: 1)]
    private ?string $overallRating = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private CarbonImmutable|null $createdAt;

    public function __construct(
        #[ORM\Column(length: 255)]
        #[Assert\NotBlank]
        private ?string $title = null,
        #[ORM\Column(length: 800)]
        #[Assert\Length(min: 50, max: 800)]
        private ?string $content = null,
        #[ORM\Column(type: Types::DECIMAL, precision: 2, scale: 1)]
        #[Assert\Range(notInRangeMessage: 'rating.ensure_limits', min: 0, max: 5)]
        private ?string $responseRating = null,
        #[ORM\Column(type: Types::DECIMAL, precision: 2, scale: 1)]
        #[Assert\Range(notInRangeMessage: 'rating.ensure_limits', min: 0, max: 5)]
        private ?string $customerServicesRating = null,
        #[ORM\Column(type: Types::DECIMAL, precision: 2, scale: 1)]
        #[Assert\Range(notInRangeMessage: 'rating.ensure_limits', min: 0, max: 5)]
        private ?string $workQualityRating = null,
        #[ORM\Column(type: Types::DECIMAL, precision: 2, scale: 1)]
        #[Assert\Range(notInRangeMessage: 'rating.ensure_limits', min: 0, max: 5)]
        private ?string $valueForMoneyRating = null,
        #[ORM\ManyToOne(targetEntity: TraderProfile::class, inversedBy: 'reviews')]
        private ?TraderProfile $target = null,
        #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'reviews')]
        private ?User $owner = null,
    )
    {
        $this->createdAt = new CarbonImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getOverallRating(): ?string
    {
        return $this->overallRating;
    }

    public function setOverallRating(string $overallRating): static
    {
        $this->overallRating = $overallRating;

        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function computeOverallIfMissing(): void
    {
        if ($this->overallRating !== null) {
            return;
        }
        $parts = array_filter([
            $this->responseRating,
            $this->customerServicesRating,
            $this->workQualityRating,
            $this->valueForMoneyRating,
        ], static fn($v): bool => $v !== null);

        if ($parts !== []) {
            $avg = array_sum(array_map('floatval', $parts)) / count($parts);
            $this->overallRating = number_format($avg, 1, '.', '');
        }
    }

    public function getResponseRating(): ?string
    {
        return $this->responseRating;
    }

    public function setResponseRating(string $responseRating): static
    {
        $this->responseRating = $responseRating;

        return $this;
    }

    public function getCustomerServicesRating(): ?string
    {
        return $this->customerServicesRating;
    }

    public function setCustomerServicesRating(string $customerServicesRating): static
    {
        $this->customerServicesRating = $customerServicesRating;

        return $this;
    }

    public function getWorkQualityRating(): ?string
    {
        return $this->workQualityRating;
    }

    public function setWorkQualityRating(string $workQualityRating): static
    {
        $this->workQualityRating = $workQualityRating;

        return $this;
    }

    public function getValueForMoneyRating(): ?string
    {
        return $this->valueForMoneyRating;
    }

    public function setValueForMoneyRating(string $valueForMoneyRating): static
    {
        $this->valueForMoneyRating = $valueForMoneyRating;

        return $this;
    }

    public function getCreatedAt(): ?CarbonImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?CarbonImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): void
    {
        $this->owner = $owner;
    }

    public function getTarget(): ?TraderProfile
    {
        return $this->target;
    }

    public function setTarget(?TraderProfile $target): void
    {
        $this->target = $target;
    }
}
