<?php
declare(strict_types=1);

namespace App\Entity;

use Carbon\CarbonImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Partner
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?Uuid $id = null;

    #[ORM\Column(length: 120)]
    private string $name;

    #[ORM\Column(length: 120, unique: true)]
    private string $slug;

    /** Default membership % for Traderpoint users across this partner’s stores (e.g. 15 or 20). */
    #[ORM\Column(type: 'smallint')]
    private int $memberPercent;

    /** @var Collection<int, Store> */
    #[ORM\OneToMany(mappedBy: 'partner', targetEntity: Store::class)]
    private Collection $stores;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private CarbonImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private CarbonImmutable $updatedAt;

    public function __construct(string $name, string $slug, int $memberPercent)
    {
        $this->name = $name;
        $this->slug = $slug;
        $this->memberPercent = $memberPercent;
        $this->stores = new ArrayCollection();
        $this->createdAt = CarbonImmutable::now();
        $this->updatedAt = CarbonImmutable::now();
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = CarbonImmutable::now();
    }

    public function getId(): ?Uuid { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): self { $this->slug = $slug; return $this; }
    public function getMemberPercent(): int { return $this->memberPercent; }
    public function setMemberPercent(int $p): self { $this->memberPercent = $p; return $this; }

    /** @return Collection<int, Store> */
    public function getStores(): Collection { return $this->stores; }
}
