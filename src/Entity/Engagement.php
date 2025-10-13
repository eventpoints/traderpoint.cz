<?php

namespace App\Entity;

use App\Enum\CurrencyCodeEnum;
use App\Enum\EngagementStatusEnum;
use App\Enum\PaymentTypeEnum;
use App\Enum\QuoteStatusEnum;
use App\Enum\TimelinePreferenceEnum;
use App\Repository\EngagementRepository;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use DomainException;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: EngagementRepository::class)]
class Engagement implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column]
    private ?float $latitude = null;

    #[ORM\Column]
    private ?float $longitude = null;

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
    private null|CarbonImmutable $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private null|CarbonImmutable|DateTimeInterface $dueAt = null;

    #[ORM\Column(enumType: EngagementStatusEnum::class)]
    private null|EngagementStatusEnum $status = EngagementStatusEnum::PENDING;

    /**
     * @var Collection<int, Skill>
     */
    #[ORM\ManyToMany(targetEntity: Skill::class, inversedBy: 'engagements')]
    private Collection $skills;

    /**
     * @var Collection<int, Quote>
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

    public function __construct(#[ORM\ManyToOne(inversedBy: 'engagements')]
    private ?User $owner = null)
    {
        $this->payments = new ArrayCollection();
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
        return $this->updatedAt;
    }

    public function setUpdatedAt(?CarbonImmutable $updatedAt): void
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

    public function __toString(): string
    {
        return (string) $this->getTitle();
    }
}
