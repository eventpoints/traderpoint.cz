<?php

namespace App\Entity;

use App\Repository\ReviewRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    private null|Uuid $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 2, scale: 1)]
    private ?string $overallRating = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 2, scale: 1)]
    private ?string $responseRating = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 2, scale: 1)]
    private ?string $customerServicesRating = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 2, scale: 1)]
    private ?string $workQualityRating = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 2, scale: 1)]
    private ?string $valueForMoneyRating = null;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    private ?User $owner = null;

    /**
     * @param string|null $title
     * @param string|null $content
     * @param string|null $responseRating
     * @param string|null $customerServicesRating
     * @param string|null $workQualityRating
     * @param string|null $valueForMoneyRating
     * @param User|null $owner
     */
    public function __construct(?string $title, ?string $content, ?string $responseRating, ?string $customerServicesRating, ?string $workQualityRating, ?string $valueForMoneyRating, ?User $owner)
    {
        $this->title = $title;
        $this->content = $content;
        $this->responseRating = $responseRating;
        $this->customerServicesRating = $customerServicesRating;
        $this->workQualityRating = $workQualityRating;
        $this->valueForMoneyRating = $valueForMoneyRating;
        $this->owner = $owner;
        $this->createdAt = new DateTimeImmutable();
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

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }
}
