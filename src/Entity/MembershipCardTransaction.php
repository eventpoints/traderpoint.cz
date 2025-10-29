<?php
declare(strict_types=1);

namespace App\Entity;

use Carbon\CarbonImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Index(name: 'idx_mct_created', columns: ['created_at'])]
#[ORM\Index(name: 'idx_mct_store', columns: ['store_id'])]
#[ORM\Index(name: 'idx_mct_user_created', columns: ['user_id', 'created_at'])]
#[UniqueEntity('ref')]
class MembershipCardTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Partner::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Partner $partner;

    #[ORM\ManyToOne(targetEntity: Store::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Store $store;

    /** Human-friendly receipt/reference (e.g. MCT-9F3A2). */
    #[ORM\Column(length: 24)]
    private string $ref;

    /** Snapshot of the % applied at checkout (store override or partner default). */
    #[ORM\Column(type: 'smallint')]
    private int $appliedPercent;

    /** Optional basket total in minor units (e.g., haléře). */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $orderAmountMinor = null;

    /** If you rotate QR tokens, keep JTI for audit (optional). */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $tokenJti = null;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private CarbonImmutable $createdAt;

    public function __construct(User $user, Partner $partner, Store $store, string $ref, int $appliedPercent)
    {
        $this->user = $user;
        $this->partner = $partner;
        $this->store = $store;
        $this->ref = $ref;
        $this->appliedPercent = $appliedPercent;
        $this->createdAt = CarbonImmutable::now();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getPartner(): Partner
    {
        return $this->partner;
    }

    public function getStore(): Store
    {
        return $this->store;
    }

    public function getRef(): string
    {
        return $this->ref;
    }

    public function getAppliedPercent(): int
    {
        return $this->appliedPercent;
    }

    public function getOrderAmountMinor(): ?int
    {
        return $this->orderAmountMinor;
    }

    public function setOrderAmountMinor(?int $v): self
    {
        $this->orderAmountMinor = $v;
        return $this;
    }

    public function getTokenJti(): ?string
    {
        return $this->tokenJti;
    }

    public function setTokenJti(?string $jti): self
    {
        $this->tokenJti = $jti;
        return $this;
    }

    public function getCreatedAt(): CarbonImmutable
    {
        return $this->createdAt;
    }
}
