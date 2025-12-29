<?php

namespace App\Entity;

use App\Enum\EngagementIssueStatusEnum;
use App\Enum\EngagementIssueTypeEnum;
use App\Repository\EngagementIssueRepository;
use Carbon\CarbonImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: EngagementIssueRepository::class)]
class EngagementIssue
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\Column(enumType: EngagementIssueTypeEnum::class)]
    private EngagementIssueTypeEnum $type;

    #[ORM\Column(enumType: EngagementIssueStatusEnum::class)]
    private EngagementIssueStatusEnum $status = EngagementIssueStatusEnum::AWAITING_SUPPORT;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $clientEvidence = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $traderEvidence = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?CarbonImmutable $clientSubmittedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?CarbonImmutable $traderSubmittedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $aiMediation = null;

    /**
     * @var null|string[]
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private CarbonImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private CarbonImmutable $updatedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private null|CarbonImmutable $resolvedAt = null;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Engagement::class)]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private Engagement $engagement,
        #[ORM\ManyToOne(targetEntity: User::class)]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private User $owner,
        #[ORM\ManyToOne(targetEntity: User::class)]
        #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
        private ?User $target,
        #[ORM\ManyToOne(targetEntity: Quote::class)]
        #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
        private ?Quote $quote = null
    )
    {
        $this->updatedAt = new CarbonImmutable();
        $this->createdAt = new CarbonImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getEngagement(): Engagement
    {
        return $this->engagement;
    }

    public function setEngagement(Engagement $engagement): void
    {
        $this->engagement = $engagement;
    }

    public function getQuote(): ?Quote
    {
        return $this->quote;
    }

    public function setQuote(?Quote $quote): void
    {
        $this->quote = $quote;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): void
    {
        $this->owner = $owner;
    }

    public function getTarget(): ?User
    {
        return $this->target;
    }

    public function setTarget(?User $target): void
    {
        $this->target = $target;
    }

    public function getType(): EngagementIssueTypeEnum
    {
        return $this->type;
    }

    public function setType(EngagementIssueTypeEnum $type): void
    {
        $this->type = $type;
    }

    public function getStatus(): EngagementIssueStatusEnum
    {
        return $this->status;
    }

    public function setStatus(EngagementIssueStatusEnum $status): void
    {
        $this->status = $status;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return string[]|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * @param string[] $metadata
     */
    public function setMetadata(?array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function getCreatedAt(): CarbonImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): CarbonImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(CarbonImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getResolvedAt(): ?CarbonImmutable
    {
        return $this->resolvedAt;
    }

    public function setResolvedAt(?CarbonImmutable $resolvedAt): void
    {
        $this->resolvedAt = $resolvedAt;
    }

    public function getClientEvidence(): ?string
    {
        return $this->clientEvidence;
    }

    public function setClientEvidence(?string $clientEvidence): void
    {
        $this->clientEvidence = $clientEvidence;
    }

    public function getTraderEvidence(): ?string
    {
        return $this->traderEvidence;
    }

    public function setTraderEvidence(?string $traderEvidence): void
    {
        $this->traderEvidence = $traderEvidence;
    }

    public function getClientSubmittedAt(): ?CarbonImmutable
    {
        return $this->clientSubmittedAt;
    }

    public function setClientSubmittedAt(?CarbonImmutable $clientSubmittedAt): void
    {
        $this->clientSubmittedAt = $clientSubmittedAt;
    }

    public function getTraderSubmittedAt(): ?CarbonImmutable
    {
        return $this->traderSubmittedAt;
    }

    public function setTraderSubmittedAt(?CarbonImmutable $traderSubmittedAt): void
    {
        $this->traderSubmittedAt = $traderSubmittedAt;
    }

    public function getAiMediation(): ?string
    {
        return $this->aiMediation;
    }

    public function setAiMediation(?string $aiMediation): void
    {
        $this->aiMediation = $aiMediation;
    }

    public function hasClientSubmitted(): bool
    {
        return $this->clientSubmittedAt instanceof \Carbon\CarbonImmutable;
    }

    public function hasTraderSubmitted(): bool
    {
        return $this->traderSubmittedAt instanceof \Carbon\CarbonImmutable;
    }

    public function bothPartiesSubmitted(): bool
    {
        return $this->hasClientSubmitted() && $this->hasTraderSubmitted();
    }
}