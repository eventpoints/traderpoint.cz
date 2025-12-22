<?php

namespace App\Entity;

use App\DataTransferObject\MapLocationDto;
use App\Enum\CurrencyCodeEnum;
use App\Enum\EngagementStatusEnum;
use App\Enum\PaymentTypeEnum;
use App\Enum\QuoteStatusEnum;
use App\Enum\TimelinePreferenceEnum;
use App\Repository\EngagementRepository;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use DomainException;
use Jsor\Doctrine\PostGIS\Types\PostGISType;
use Stringable;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: EngagementRepository::class)]
#[ORM\Index(
    fields: ['point'],
    flags: ['spatial'],
)]
#[ORM\HasLifecycleCallbacks]
class Engagement implements Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isDeleted = false;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column]
    private ?float $latitude = null;

    #[ORM\Column]
    private ?float $longitude = null;

    #[ORM\Column(
        type: PostGISType::GEOMETRY,
        nullable: true,
        options: [
            'geometry_type' => 'POINT',
            'srid' => 4326,
        ],
    )]
    public null|string $point = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(enumType: CurrencyCodeEnum::class)]
    private null|CurrencyCodeEnum $currencyCodeEnum = CurrencyCodeEnum::CZK;

    #[ORM\Column(enumType: TimelinePreferenceEnum::class)]
    private null|TimelinePreferenceEnum $timelinePreferenceEnum = TimelinePreferenceEnum::WITHIN_TWO_WEEK;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $budget = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private null|CarbonImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private null|CarbonImmutable|DateTimeInterface $dueAt = null;

    #[ORM\Column(enumType: EngagementStatusEnum::class)]
    private null|EngagementStatusEnum $status = EngagementStatusEnum::UNDER_ADMIN_REVIEW;

    /**
     * @var Collection<int, Skill>
     */
    #[ORM\ManyToMany(targetEntity: Skill::class, inversedBy: 'engagements')]
    private Collection $skills;

    /**
     * @var Collection<int, Quote>&Selectable<int, Quote>
     */
    #[ORM\OneToMany(
        targetEntity: Quote::class,
        mappedBy: 'engagement',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $quotes;

    #[ORM\ManyToOne(targetEntity: Quote::class)]
    #[ORM\JoinColumn(
        name: 'chosen_quote_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?Quote $quote = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $distanceFromNearestCity = null;

    /**
     * @var Collection<int, Image>
     */
    #[ORM\OneToMany(
        targetEntity: Image::class,
        mappedBy: 'engagement',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $images;

    /**
     * @var Collection<int, Payment>
     */
    #[ORM\OneToMany(targetEntity: Payment::class, mappedBy: 'engagement', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $payments;

    /**
     * @var Collection<int, EngagementIssue>
     */
    #[ORM\OneToMany(targetEntity: EngagementIssue::class, mappedBy: 'engagement', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $issues;

    /**
     * @var ArrayCollection<int, EngagementReaction>
     */
    #[ORM\OneToMany(
        targetEntity: EngagementReaction::class,
        mappedBy: 'engagement',
        cascade: ['remove'],
        orphanRemoval: true
    )]
    private Collection $reactions;

    #[ORM\OneToOne(
        targetEntity: Conversation::class,
        mappedBy: 'engagement',
        cascade: ['persist', 'remove']
    )]
    private ?Conversation $conversation = null;

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'engagements')]
        private ?User $owner = null
    )
    {
        $this->reactions = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->issues = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->skills = new ArrayCollection();
        $this->quotes = new ArrayCollection();
        $this->updatedAt = new CarbonImmutable();
        $this->createdAt = new CarbonImmutable();
    }

    public function getId(): ?Uuid
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): static
    {
        $this->longitude = $longitude;

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

    public function getStatus(): ?EngagementStatusEnum
    {
        return $this->status;
    }

    public function setStatus(?EngagementStatusEnum $status): void
    {
        $this->status = $status;
    }

    public function getCurrencyCodeEnum(): ?CurrencyCodeEnum
    {
        return $this->currencyCodeEnum;
    }

    public function setCurrencyCodeEnum(?CurrencyCodeEnum $currencyCodeEnum): void
    {
        $this->currencyCodeEnum = $currencyCodeEnum;
    }

    public function getBudget(): ?int
    {
        return $this->budget;
    }

    public function setBudget(?int $budget): void
    {
        $this->budget = $budget;
    }

    public function getCreatedAt(): ?CarbonImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?CarbonImmutable
    {
        return $this->updatedAt instanceof \DateTimeImmutable ? CarbonImmutable::instance($this->updatedAt) : null;
    }

    public function setUpdatedAt(null|CarbonImmutable|DateTimeInterface $updatedAt): void
    {
        if ($updatedAt instanceof DateTimeInterface) {
            $updatedAt = CarbonImmutable::createFromInterface($updatedAt);
        }

        $this->updatedAt = $updatedAt;
    }

    public function getDueAt(): null|CarbonImmutable|DateTimeInterface
    {
        return $this->dueAt;
    }

    public function setDueAt(null|CarbonImmutable|DateTimeInterface $dueAt): void
    {
        if ($dueAt instanceof DateTimeInterface) {
            $dueAt = CarbonImmutable::createFromInterface($dueAt);
        }

        $this->dueAt = $dueAt;
    }

    /**
     * @return Collection<int, Skill>
     */
    public function getSkills(): Collection
    {
        return $this->skills;
    }

    public function addSkill(Skill $skill): static
    {
        if (! $this->skills->contains($skill)) {
            $this->skills->add($skill);
        }

        return $this;
    }

    public function removeSkill(Skill $skill): static
    {
        $this->skills->removeElement($skill);

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): void
    {
        $this->address = $address;
    }

    public function getQuote(): ?Quote
    {
        return $this->quote;
    }

    public function setQuote(?Quote $quote): void
    {
        $this->quote = $quote;
    }

    /**
     * @return ArrayCollection<int, Quote>
     */
    public function getQuotes(): Collection
    {
        return $this->quotes;
    }

    public function addQuote(Quote $quote): void
    {
        if (! $this->quotes->contains($quote)) {
            $this->quotes->add($quote);
        }
    }

    /**
     * @return ArrayCollection<int, Quote>
     */
    public function getPendingQuotes(): ArrayCollection
    {
        return $this->quotes->filter(fn(Quote $quote): bool => $quote->getStatus() === QuoteStatusEnum::SUBMITTED);
    }

    public function getDistanceFromNearestCity(): ?string
    {
        return $this->distanceFromNearestCity;
    }

    public function setDistanceFromNearestCity(?string $distanceFromNearestCity): void
    {
        $this->distanceFromNearestCity = $distanceFromNearestCity;
    }

    public function hasAlreadySubmittedQuote(User $user): bool
    {
        return $this->quotes->exists(fn(int $key, Quote $quote): bool => $quote->getOwner()->getId() === $user->getId());
    }

    public function getMostRecentQuoteFor(User $user): ?Quote
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('owner', $user))
            ->orderBy([
                'version' => Criteria::DESC,
            ])
            ->setMaxResults(1);

        $match = $this->quotes->matching($criteria);
        return $match->isEmpty() ? null : $match->first();
    }

    /**
     * @return Collection<int, Image>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(Image $image): self
    {
        if (! $this->images->contains($image)) {
            $this->images->add($image);
            // keep owning side in sync (Image is owning side with many-to-one)
            $image->setEngagement($this);
        }
        return $this;
    }

    public function removeImage(Image $image): self
    {
        if ($this->images->removeElement($image) && $image->getEngagement() === $this) {
            $image->setEngagement(null);
        }
        return $this;
    }

    public function accept(Quote $quote): void
    {
        if ($quote->getEngagement() !== $this) {
            throw new DomainException('Quote does not belong to this engagement.');
        }

        $this->setQuote($quote);
        $quote->setStatus(QuoteStatusEnum::ACCEPTED);
        $quote->setDecidedAt(CarbonImmutable::now());

        foreach ($this->getQuotes() as $q) {
            if ($q === $quote) {
                continue;
            }

            $q->reject();
        }
    }

    public function getTimelinePreferenceEnum(): ?TimelinePreferenceEnum
    {
        return $this->timelinePreferenceEnum;
    }

    public function setTimelinePreferenceEnum(?TimelinePreferenceEnum $timelinePreferenceEnum): void
    {
        $this->timelinePreferenceEnum = $timelinePreferenceEnum;
    }

    /**
     * @return Collection<int, Payment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): void
    {
        if (! $this->payments->contains($payment)) {
            $this->payments->add($payment);
        }
    }

    public function removePayment(Payment $payment): void
    {
        if($this->payments->contains($payment)) {
            $this->payments->removeElement($payment);
        }
    }

    public function getPostingPayment(): null|Payment
    {
       return $this->payments->findFirst(fn(int $key, Payment $payment): bool => $payment->getType() === PaymentTypeEnum::POSTING_FEE);
    }

    public function isBoosted(): bool
    {
        return $this->payments->exists(fn(int $key, Payment $payment): bool => $payment->getType() === PaymentTypeEnum::FEATURED);
    }

    public function expiresInDaysHuman(): string
    {
        $cutoff = $this->createdAt->addDays(30)->endOfDay();
        $now = CarbonImmutable::now();

        $hoursLeft = $now->diffInHours($cutoff, false); // signed
        if ($hoursLeft <= 0) {
            $daysAgo = (int) ceil(abs($hoursLeft) / 24);
            return sprintf('Expired %d day%s ago', $daysAgo, $daysAgo === 1 ? '' : 's');
        }

        $daysLeft = (int) ceil($hoursLeft / 24);
        return sprintf('%d day%s left', $daysLeft, $daysLeft === 1 ? '' : 's');
    }

    public function getPoint(): ?string
    {
        return $this->point;
    }

    public function setPoint(?string $point): void
    {
        $this->point = $point;
    }

    public function __toString(): string
    {
        return (string) $this->getTitle();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function syncPointFromLatLng(): void
    {
        if (! empty($this->latitude) && ! empty($this->longitude)) {
            $point = sprintf('SRID=4326;POINT(%F %F)', $this->longitude, $this->latitude);
            $this->setPoint($point);
        }else{
            $this->setPoint(null);
        }
    }

    /**
     * @return Collection<int, EngagementReaction>
     */
    public function getReactions(): Collection
    {
        return $this->reactions;
    }

    public function addReaction(EngagementReaction $reaction): self
    {
        if (! $this->reactions->contains($reaction)) {
            $this->reactions->add($reaction);
            $reaction->setEngagement($this);
        }
        return $this;
    }

    public function removeReaction(EngagementReaction $reaction): self
    {
        $this->reactions->removeElement($reaction);
        return $this;
    }

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(?Conversation $conversation): void
    {
        $this->conversation = $conversation;
    }

    public function getIsDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): void
    {
        $this->isDeleted = $isDeleted;
    }

    public function getMapLocation(): MapLocationDto
    {
        return new MapLocationDto(
            (float) ($this->getLatitude() ?? 0),
            (float) ($this->getLongitude() ?? 0),
            $this->getAddress() ?? '',
            null
        );
    }

    public function setMapLocation(?MapLocationDto $dto): void
    {
        if (! $dto instanceof MapLocationDto) {
            return;
        }

        $this->latitude = $dto->getLatitude();
        $this->longitude = $dto->getLongitude();
        $this->address = $dto->getAddress();
    }

    // Workflow helper methods

    public function hasActiveIssue(): bool
    {
        // Check if there's an active EngagementIssue
        // This would require a relationship to EngagementIssue or a query
        // For now, we check if status is ISSUE_RESOLUTION
        return $this->status === EngagementStatusEnum::ISSUE_RESOLUTION;
    }

    public function canReceiveQuotes(): bool
    {
        return $this->status === EngagementStatusEnum::RECEIVING_QUOTES;
    }

    public function isWorkInProgress(): bool
    {
        return $this->status === EngagementStatusEnum::IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return match ($this->status) {
            EngagementStatusEnum::WORK_COMPLETED,
            EngagementStatusEnum::AWAITING_REVIEW,
            EngagementStatusEnum::REVIEWED => true,
            default => false,
        };
    }

    public function isAwaitingAdminReview(): bool
    {
        return $this->status === EngagementStatusEnum::UNDER_ADMIN_REVIEW;
    }

    public function isReceivingQuotes(): bool
    {
        return $this->status === EngagementStatusEnum::RECEIVING_QUOTES;
    }

    public function isCancelled(): bool
    {
        return $this->status === EngagementStatusEnum::CANCELLED;
    }

    public function isRejected(): bool
    {
        return $this->status === EngagementStatusEnum::REJECTED;
    }

    /**
     * @return Collection<int, EngagementIssue>
     */
    public function getIssues(): Collection
    {
        return $this->issues;
    }

    public function addIssue(EngagementIssue $issue): void
    {
        if (!$this->issues->contains($issue)) {
            $this->issues->add($issue);
        }
    }

    public function removeIssue(EngagementIssue $issue): void
    {
        if ($this->issues->contains($issue)) {
            $this->issues->removeElement($issue);
        }
    }
}
