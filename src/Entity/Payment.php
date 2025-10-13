<?php
declare(strict_types=1);

namespace App\Entity;

use App\Enum\CurrencyCodeEnum;
use App\Enum\PaymentStatusEnum;
use App\Enum\PaymentTypeEnum;
use App\Repository\PaymentRepository;
use Carbon\CarbonImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[ORM\Index(columns: ['stripe_checkout_session_id'])]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $owner;

    #[ORM\ManyToOne(targetEntity: Engagement::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Engagement $engagement;

    // minor units (haléře)
    #[ORM\Column(type: Types::INTEGER)]
    private int $amountMinor;

    #[ORM\Column(enumType: CurrencyCodeEnum::class)]
    private CurrencyCodeEnum $currency;

    #[ORM\Column(enumType: PaymentStatusEnum::class)]
    private PaymentStatusEnum $status = PaymentStatusEnum::PENDING;

    #[ORM\Column(enumType: PaymentTypeEnum::class)]
    private PaymentTypeEnum $type;
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeCheckoutSessionId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripePaymentIntentId = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private CarbonImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private CarbonImmutable $updatedAt;

    public function __construct(
        User              $owner,
        Engagement        $engagement,
        int               $amountMinor,
        CurrencyCodeEnum  $currency,
        PaymentTypeEnum   $type,
        PaymentStatusEnum $status = PaymentStatusEnum::PENDING,
    )
    {
        $this->owner = $owner;
        $this->engagement = $engagement;
        $this->amountMinor = $amountMinor;
        $this->currency = $currency;
        $this->createdAt = new CarbonImmutable();
        $this->updatedAt = new CarbonImmutable();
        $this->type = $type;
        $this->status = $status;
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new CarbonImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function getEngagement(): Engagement
    {
        return $this->engagement;
    }

    public function getAmountMinor(): int
    {
        return $this->amountMinor;
    }

    public function getCurrency(): CurrencyCodeEnum
    {
        return $this->currency;
    }

    public function getStatus(): PaymentStatusEnum
    {
        return $this->status;
    }

    public function setStatus(PaymentStatusEnum $status): void
    {
        $this->status = $status;
        $this->updatedAt = new CarbonImmutable();
    }

    public function getStripeCheckoutSessionId(): ?string
    {
        return $this->stripeCheckoutSessionId;
    }

    public function setStripeCheckoutSessionId(?string $id): void
    {
        $this->stripeCheckoutSessionId = $id;
    }

    public function getStripePaymentIntentId(): ?string
    {
        return $this->stripePaymentIntentId;
    }

    public function setStripePaymentIntentId(?string $id): void
    {
        $this->stripePaymentIntentId = $id;
    }

    public function getType(): PaymentTypeEnum
    {
        return $this->type;
    }

    public function setType(PaymentTypeEnum $type): void
    {
        $this->type = $type;
    }

    public function getCreatedAt(): CarbonImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(CarbonImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): CarbonImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(CarbonImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function __toString(): string
    {
        return $this->getId()->toRfc4122();
    }
}
