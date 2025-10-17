<?php

namespace App\Entity;

use App\Enum\CurrencyCodeEnum;
use App\Enum\QuoteStatusEnum;
use App\Repository\QuoteRepository;
use Carbon\CarbonImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use DomainException;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuoteRepository::class)]
#[ORM\Table(name: 'quote')]
#[ORM\UniqueConstraint(name: 'uq_quote_engagement_trader_version', columns: ['engagement_id', 'owner_id', 'version'])]
#[ORM\Index(name: 'ix_quote_engagement', columns: ['engagement_id'])]
#[ORM\Index(name: 'ix_quote_trader', columns: ['owner_id'])]
#[ORM\Index(name: 'ix_quote_status', columns: ['status'])]
#[ORM\Index(name: 'ix_quote_decided_at', columns: ['decided_at'])]
#[ORM\Index(name: 'ix_quote_owner_engagement', columns: ['owner_id', 'engagement_id'])]
#[ORM\Index(
    name: 'ix_quote_owner_engagement_submitted',
    columns: ['owner_id', 'engagement_id'],
    options: [
        'where' => "status = 'SUBMITTED'",
    ]
)]
#[ORM\HasLifecycleCallbacks]
class Quote implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Engagement::class, inversedBy: 'quotes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Engagement $engagement;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $owner;

    #[ORM\Column(type: Types::INTEGER, options: [
        'default' => 1,
    ])]
    #[Assert\Positive]
    private int $version = 1;

    #[ORM\Column(length: 20, enumType: QuoteStatusEnum::class)]
    private QuoteStatusEnum $status = QuoteStatusEnum::SUBMITTED;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\GreaterThanOrEqual(0)]
    private int $priceNetCents;

    #[ORM\Column(length: 3, enumType: CurrencyCodeEnum::class)]
    private CurrencyCodeEnum $currency = CurrencyCodeEnum::CZK;

    #[ORM\Column(type: Types::INTEGER, options: [
        'unsigned' => true,
    ])]
    #[Assert\Range(min: 0, max: 10000)]
    private int $vatRateBps = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $priceVatCents = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $priceGrossCents = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private CarbonImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?CarbonImmutable $decidedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?CarbonImmutable $validUntil = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?CarbonImmutable $startAt = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Positive]
    private ?int $expectedDurationHours = null;

    #[ORM\Column(type: Types::BOOLEAN, options: [
        'default' => false,
    ])]
    private bool $includesMaterials = false;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Range(min: 0, max: 10000)]
    private ?int $depositPercentBps = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\Range(min: 0, max: 120)]
    private ?int $warrantyMonths = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    public function __construct(Engagement $engagement, User $trader, int $priceNetCents = 0, CurrencyCodeEnum $currency = CurrencyCodeEnum::CZK)
    {
        $now = CarbonImmutable::now();
        $this->setEngagement($engagement);
        $this->setOwner($trader);
        $this->setPriceNetCents($priceNetCents);
        $this->setCurrency($currency);
        $this->setCreatedAt($now);
        $this->setValidUntil($now->addDays(30)->endOfDay()); ;
        $this->setStatus(QuoteStatusEnum::SUBMITTED);
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->setCreatedAt($this->getCreatedAt());
        $this->setCurrency($this->getCurrency());
        $this->recalculateTotals();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->setCurrency($this->getCurrency()); // normalize uppercase
        $this->recalculateTotals();
    }

    private function recalculateTotals(): void
    {
        $vat = (int) round($this->priceNetCents * ($this->vatRateBps / 10000));
        $this->priceVatCents = $vat;
        $this->priceGrossCents = $this->priceNetCents + $vat;
    }

    public function submit(?CarbonImmutable $validUntil = null): void
    {
        if ($this->getStatus() !== QuoteStatusEnum::DRAFT && $this->getStatus() !== QuoteStatusEnum::SUBMITTED) {
            return;
        }
        $this->setStatus(QuoteStatusEnum::SUBMITTED);
        $this->setCreatedAt($this->getCreatedAt());
        $this->setValidUntil($validUntil);
    }

    public function withdraw(): void
    {
        if ($this->getStatus() !== QuoteStatusEnum::SUBMITTED) {
            return;
        }
        $this->setStatus(QuoteStatusEnum::WITHDRAWN);
        $this->setDecidedAt(CarbonImmutable::now());
    }

    public function reject(): void
    {
        $this->setStatus(QuoteStatusEnum::REJECTED);
        $this->setDecidedAt(CarbonImmutable::now());
    }

    // Used when another quote is chosen and this one becomes obsolete
    public function supersede(): void
    {
        if (in_array($this->getStatus(), [QuoteStatusEnum::SUBMITTED, QuoteStatusEnum::ACCEPTED], true)) {
            $this->setStatus(QuoteStatusEnum::SUPERSEDED);
            $this->setDecidedAt(CarbonImmutable::now());
        }
    }

    public function markExpiredIfNeeded(): void
    {
        if ($this->getStatus() === QuoteStatusEnum::SUBMITTED && $this->getValidUntil() && $this->getValidUntil()->isPast()) {
            $this->setStatus(QuoteStatusEnum::EXPIRED);
            $this->setDecidedAt(CarbonImmutable::now());
        }
    }

    public function revise(int $newVersion): void
    {
        $this->setVersion($newVersion);
        $this->setStatus(QuoteStatusEnum::SUBMITTED);
        $this->setCreatedAt(CarbonImmutable::now());
        $this->setDecidedAt(null);
    }

    public function isOpen(): bool
    {
        return $this->getStatus() === QuoteStatusEnum::SUBMITTED && ! $this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->getValidUntil()?->isPast() ?? false;
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

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): void
    {
        if (! $owner->isTrader()) {
            throw new DomainException('Only traders can submit quotes');
        }
        $this->owner = $owner;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): void
    {
        $this->version = max(1, $version);
    }

    public function getStatus(): QuoteStatusEnum
    {
        return $this->status;
    }

    public function setStatus(QuoteStatusEnum $status): void
    {
        $this->status = $status;
    }

    public function getPriceNetCents(): int
    {
        return $this->priceNetCents;
    }

    public function setPriceNetCents(int $cents): void
    {
        $this->priceNetCents = max(0, $cents);
        $this->recalculateTotals();
    }

    public function getVatRateBps(): int
    {
        return $this->vatRateBps;
    }

    public function setVatRateBps(int $bps): void
    {
        $this->vatRateBps = max(0, min(10000, $bps));
        $this->recalculateTotals();
    }

    public function getPriceVatCents(): int
    {
        return $this->priceVatCents;
    }

    public function getPriceGrossCents(): int
    {
        return $this->priceGrossCents;
    }

    public function getCreatedAt(): CarbonImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(CarbonImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getDecidedAt(): ?CarbonImmutable
    {
        return $this->decidedAt;
    }

    public function setDecidedAt(?CarbonImmutable $decidedAt): void
    {
        $this->decidedAt = $decidedAt;
    }

    public function getValidUntil(): ?CarbonImmutable
    {
        return $this->validUntil;
    }

    public function setValidUntil(?CarbonImmutable $validUntil): void
    {
        $this->validUntil = $validUntil;
    }

    public function getStartAt(): ?CarbonImmutable
    {
        return $this->startAt;
    }

    public function setStartAt(?CarbonImmutable $startAt): void
    {
        $this->startAt = $startAt;
    }

    public function getExpectedDurationHours(): ?int
    {
        return $this->expectedDurationHours;
    }

    public function setExpectedDurationHours(?int $expectedDurationHours): void
    {
        $this->expectedDurationHours = $expectedDurationHours;
    }

    public function isIncludesMaterials(): bool
    {
        return $this->includesMaterials;
    }

    public function setIncludesMaterials(bool $includesMaterials): void
    {
        $this->includesMaterials = $includesMaterials;
    }

    public function getDepositPercentBps(): ?int
    {
        return $this->depositPercentBps;
    }

    public function setDepositPercentBps(?int $depositPercentBps): void
    {
        $this->depositPercentBps = $depositPercentBps;
    }

    public function getWarrantyMonths(): ?int
    {
        return $this->warrantyMonths;
    }

    public function setWarrantyMonths(?int $warrantyMonths): void
    {
        $this->warrantyMonths = $warrantyMonths;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): void
    {
        $this->message = $message;
    }

    public function getCurrency(): CurrencyCodeEnum
    {
        return $this->currency;
    }

    public function setCurrency(CurrencyCodeEnum $currency): void
    {
        $this->currency = $currency;
    }

    public function isRjectedExpiredOrwithdrawn(): bool
    {
        return in_array($this->status, [QuoteStatusEnum::REJECTED, QuoteStatusEnum::WITHDRAWN, QuoteStatusEnum::EXPIRED]);
    }

    public function isUnanswered(): bool
    {
        return $this->status == QuoteStatusEnum::SUBMITTED;
    }

    public function getPrice(): int
    {
        return $this->getPriceNetCents() / 100;
    }

    public function __toString(): string
    {
        return $this->getId() . '-' . $this->getPrice();
    }

    public function isAfterEstimateDuration(): bool
    {
        $decidedAt = $this->getDecidedAt();
        $hours = $this->getExpectedDurationHours();

        if (! $decidedAt || $hours === null) {
            return false;
        }

        $deadline = $decidedAt->addMinutes((int) round($hours * 60));

        // Use the same timezone as $decidedAt and include equality if desired
        return CarbonImmutable::now($decidedAt->getTimezone())
            ->greaterThanOrEqualTo($deadline);
    }

    public function estimateDuration(): CarbonImmutable
    {
        return $this->getDecidedAt()->addMinutes((int) round($this->getExpectedDurationHours() * 60));
    }
}
