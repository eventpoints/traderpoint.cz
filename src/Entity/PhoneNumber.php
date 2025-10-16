<?php

namespace App\Entity;

use App\Repository\PhoneNumberRepository;
use Carbon\CarbonImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PhoneNumberRepository::class)]
#[ORM\UniqueConstraint(
    name: 'uniq_phone_prefix_number',
    columns: ['prefix', 'number']
)]
#[UniqueEntity(
    fields: ['prefix', 'number'],
    message: new TranslatableMessage('phone_number.already_registered', [], 'validators'),
    errorPath: 'number'
)]
class PhoneNumber
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    private null|Uuid $id = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private null|CarbonImmutable $confirmedAt = null;

    public function __construct(
        #[ORM\Column]
        private null|int $prefix = null,
        #[ORM\Column(length: 32, unique: true)]
        private null|string $number = null
    )
    {
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

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): void
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
