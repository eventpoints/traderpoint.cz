<?php

namespace App\Entity;

use App\Enum\VerificationPurposeEnum;
use App\Enum\VerificationTypeEnum;
use Carbon\CarbonImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Index(name: 'vc_common_idx', columns: ['type', 'purpose', 'verified', 'expires_at'])]
#[ORM\Index(name: 'vc_phone_idx', columns: ['phone_number_id', 'purpose', 'verified'])]
#[ORM\Index(name: 'vc_owner_idx', columns: ['owner_id', 'purpose', 'verified'])]
class VerificationCode
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\Column(enumType: VerificationTypeEnum::class)]
    private VerificationTypeEnum $type;

    // Exactly one of these should be set (based on $type):
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $owner = null; // for EMAIL

    #[ORM\ManyToOne(targetEntity: PhoneNumber::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?PhoneNumber $phoneNumber = null; // for PHONE

    // password_hash() of the OTP
    #[ORM\Column(length: 255)]
    private string $codeHash;

    // HMAC(sha256) of the OTP for deterministic lookup
    // Use BINARY(32) for raw bytes:
    #[ORM\Column(length: 64)]
    private string $codeDigest;

    #[ORM\Column(length: 32, enumType: VerificationPurposeEnum::class)]
    private VerificationPurposeEnum $purpose;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private CarbonImmutable $expiresAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private CarbonImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private CarbonImmutable $updatedAt;

    #[ORM\Column(type: 'integer', options: [
        'default' => 0,
    ])]
    private int $attempts = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?CarbonImmutable $lastSentAt = null;

    #[ORM\Column(type: 'boolean', options: [
        'default' => false,
    ])]
    private bool $verified = false;

    // Optional UX/audit snapshot like "+420***345" or masked email
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $destinationSnapshot = null;

    private function __construct() {}

    /**
     * Factory for PHONE verification
     */
    public static function forPhone(
        PhoneNumber $phone,
        string $codeHash,
        string $codeDigestBinary,
        VerificationPurposeEnum $purpose,
        CarbonImmutable $expiresAt
    ): self {
        $self = new self();
        $self->type = VerificationTypeEnum::PHONE;
        $self->phoneNumber = $phone;
        $self->codeHash = $codeHash;
        $self->codeDigest = $codeDigestBinary; // 32 bytes
        $self->purpose = $purpose;
        $self->expiresAt = $expiresAt;
        $self->createdAt = CarbonImmutable::now();
        $self->updatedAt = $self->createdAt;
        return $self;
    }

    /**
     * Factory for EMAIL verification
     */
    public static function forEmail(
        User $owner,
        string $codeHash,
        string $codeDigestBinary,
        VerificationPurposeEnum $purpose,
        CarbonImmutable $expiresAt
    ): self {
        $self = new self();
        $self->type = VerificationTypeEnum::EMAIL;
        $self->owner = $owner;
        $self->codeHash = $codeHash;
        $self->codeDigest = $codeDigestBinary;
        $self->purpose = $purpose;
        $self->expiresAt = $expiresAt;
        $self->createdAt = CarbonImmutable::now();
        $self->updatedAt = $self->createdAt;
        return $self;
    }

    // ——— Getters / mutators ———

    public function getId(): ?Uuid { return $this->id; }

    public function getType(): VerificationTypeEnum { return $this->type; }

    public function getOwner(): ?User { return $this->owner; }

    public function getPhoneNumber(): ?PhoneNumber { return $this->phoneNumber; }

    public function getPurpose(): VerificationPurposeEnum { return $this->purpose; }

    public function isVerified(): bool { return $this->verified; }

    public function getExpiresAt(): CarbonImmutable { return $this->expiresAt; }

    public function getCreatedAt(): CarbonImmutable { return $this->createdAt; }

    public function getUpdatedAt(): CarbonImmutable { return $this->updatedAt; }

    public function getCodeHash(): string { return $this->codeHash; }

    public function getCodeDigest(): string { return $this->codeDigest; } // binary

    public function getAttempts(): int { return $this->attempts; }

    public function setAttempts(int $n): void {
        $this->attempts = $n;
        $this->updatedAt = CarbonImmutable::now();
    }

    public function setVerified(bool $v): void {
        $this->verified = $v;
        $this->updatedAt = CarbonImmutable::now();
    }

    public function setLastSentAt(CarbonImmutable $t): void {
        $this->lastSentAt = $t;
        $this->updatedAt = CarbonImmutable::now();
    }

    public function getLastSentAt(): ?CarbonImmutable { return $this->lastSentAt; }

    public function setDestinationSnapshot(?string $snapshot): void { $this->destinationSnapshot = $snapshot; }

    public function getDestinationSnapshot(): ?string { return $this->destinationSnapshot; }

    /**
     * Optional runtime assertion to ensure target matches type
     */
    public function assertTargetValid(): void
    {
        $isPhone = $this->type === VerificationTypeEnum::PHONE;
        if ($isPhone && ! $this->phoneNumber) {
            throw new \LogicException('PHONE verification requires phoneNumber.');
        }
        if (! $isPhone && ! $this->owner) {
            throw new \LogicException('EMAIL verification requires owner (User).');
        }
    }
}