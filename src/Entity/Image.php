<?php

namespace App\Entity;

use App\Repository\ImageRepository;
use Carbon\CarbonImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ImageRepository::class)]
class Image implements Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    private Uuid $id;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private CarbonImmutable $createdAt;

    /**
     * @param User $owner
     * @param string|null $base64
     * @param string|null $oldFilename
     */
    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'images')]
        private User $owner,
        #[ORM\Column(type: Types::TEXT)]
        private ?string $base64,
        #[ORM\Column(length: 255)]
        private ?string $oldFilename
    )
    {
        $this->createdAt = new CarbonImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getOldFilename(): ?string
    {
        return $this->oldFilename;
    }

    public function setOldFilename(string $oldFilename): static
    {
        $this->oldFilename = $oldFilename;

        return $this;
    }

    public function getCreatedAt(): CarbonImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(CarbonImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): void
    {
        $this->owner = $owner;
    }

    public function getBase64(): ?string
    {
        return $this->base64;
    }

    public function setBase64(?string $base64): void
    {
        $this->base64 = $base64;
    }

    public function __toString(): string
    {
        return (string)$this->getBase64();
    }
}
