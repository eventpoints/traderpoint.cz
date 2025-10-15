<?php

namespace App\Entity;

use App\Enum\TraderStatusEnum;
use App\Repository\TraderProfileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Jsor\Doctrine\PostGIS\Types\PostGISType;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: TraderProfileRepository::class)]
#[ORM\Index(
    fields: ['point'],
    flags: ['spatial'],
)]
#[ORM\HasLifecycleCallbacks]
class TraderProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    private null|Uuid $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private null|string $avatar = null;

    /**
     * @var Collection<int, Skill>
     */
    #[ORM\ManyToMany(targetEntity: Skill::class, inversedBy: 'users', cascade: ['persist'])]
    private Collection $skills;

    #[ORM\Column(nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(nullable: true)]
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

    #[ORM\Column(nullable: true)]
    private ?int $serviceRadius = null;

    #[ORM\Column(enumType: TraderStatusEnum::class)]
    private TraderStatusEnum $status = TraderStatusEnum::INACTIVE;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'traderProfile')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $owner;

    public function __construct()
    {
        $this->skills = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getAvatar(): string
    {
        return $this->avatar;
    }

    public function setAvatar(string $avatar): void
    {
        $this->avatar = $avatar;
    }

    public function getStatus(): TraderStatusEnum
    {
        return $this->status;
    }

    public function setStatus(TraderStatusEnum $status): void
    {
        $this->status = $status;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): void
    {
        $this->owner = $owner;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): void
    {
        $this->latitude = $latitude;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): void
    {
        $this->longitude = $longitude;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): void
    {
        $this->address = $address;
    }

    public function getServiceRadius(): ?int
    {
        return $this->serviceRadius;
    }

    public function setServiceRadius(?int $serviceRadius): void
    {
        $this->serviceRadius = $serviceRadius;
    }

    public function getPoint(): ?string
    {
        return $this->point;
    }

    public function setPoint(?string $point): void
    {
        $this->point = $point;
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

    public function isLocationConfiured(): bool
    {
        return $this->skills->count() === 0 || $this->serviceRadius !== null && $this->serviceRadius !== 0 || ! empty($this->getLatitude()) || ! empty($this->getLongitude());
    }
}