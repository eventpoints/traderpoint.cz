<?php

namespace App\Entity;

use App\Repository\PhoneNumberRepository;
use Carbon\CarbonImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PhoneNumberRepository::class)]
class PhoneNumber
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    private null|Uuid $id = null;
    #[ORM\Column]
    private int $prefix;

    #[ORM\Column]
    private int $number;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private null|CarbonImmutable $confirmedAt = null;

    /**
     * @param int $prefix
     * @param int $number
     */
    public function __construct(int $prefix, int $number)
    {
        $this->prefix = $prefix;
        $this->number = $number;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getPrefix(): int
    {
        return $this->prefix;
    }

    public function setPrefix(int $prefix): void
    {
        $this->prefix = $prefix;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): void
    {
        $this->number = $number;
    }

    public function getConfirmedAt(): ?CarbonImmutable
    {
        return $this->confirmedAt;
    }

    public function setConfirmedAt(?CarbonImmutable $confirmedAt): void
    {
        $this->confirmedAt = $confirmedAt;
    }

}
