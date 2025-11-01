<?php

namespace App\Entity;

use App\Repository\PhoneNumberRepository;
use Carbon\CarbonImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: PhoneNumberRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_phone_prefix_number', columns: ['prefix', 'number'])]
#[UniqueEntity(
    fields: ['prefix', 'number'],
    message: new TranslatableMessage('phone_number.already_registered', [], 'validators'),
    errorPath: 'number'
)]
#[Assert\Callback('validatePhone')]
class PhoneNumber
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?CarbonImmutable $confirmedAt = null;

    public function __construct(
        // Country calling code (e.g. 420 for CZ)
        #[ORM\Column]
        #[Assert\NotBlank(message: 'Select a dial code.')]
        #[Assert\Positive(message: 'Dial code must be positive.')]
        private ?int $prefix = null,

        // National significant number (digits only)
        #[ORM\Column(length: 32)]
        #[Assert\NotBlank(message: 'Enter a phone number.')]
        #[Assert\Regex('/^\d+$/', message: 'Digits only.')]
        private ?string $number = null
    ) {}

    public function getId(): ?Uuid { return $this->id; }

    public function getPrefix(): ?int { return $this->prefix; }

    public function setPrefix(?int $prefix): void { $this->prefix = $prefix; }

    public function getNumber(): ?string { return $this->number; }

    public function setNumber(?string $number): void { $this->number = $number; }

    public function getConfirmedAt(): ?CarbonImmutable { return $this->confirmedAt; }

    public function setConfirmedAt(?CarbonImmutable $confirmedAt): void { $this->confirmedAt = $confirmedAt; }

    // Convenience: E.164 string when needed (for SMS send)
    public function getE164(): ?string
    {
        if ($this->prefix === null || $this->number === null) {
            return null;
        }
        $util = PhoneNumberUtil::getInstance();
        $proto = $util->parse('+' . $this->prefix . $this->number, 'ZZ');
        return $util->format($proto, PhoneNumberFormat::E164);
    }

    public function validatePhone(ExecutionContextInterface $ctx): void
    {
        if ($this->prefix === null || $this->number === null) {
            return; // field-level NotBlank will handle empties
        }

        // strip non-digits defensively
        $cc = (int) preg_replace('/\D+/', '', (string) $this->prefix);
        $nsn = (string) preg_replace('/\D+/', '', $this->number);

        if ($cc <= 0 || $nsn === '') {
            $ctx->buildViolation('Enter a valid phone number.')->atPath('number')->addViolation();
            return;
        }

        try {
            $util = PhoneNumberUtil::getInstance();
            $proto = $util->parse('+' . $cc . $nsn, 'ZZ'); // “unknown region” parse

            if (! $util->isValidNumber($proto)) {
                $ctx->buildViolation('Enter a valid phone number.')->atPath('number')->addViolation();
                return;
            }

            // Normalize what you store:
            $this->prefix = $proto->getCountryCode();                // e.g. 420
            $this->number = (string) $proto->getNationalNumber();    // e.g. 777123456

        } catch (\Throwable) {
            $ctx->buildViolation('Enter a valid phone number.')->atPath('number')->addViolation();
        }
    }

    public function getPhoneNumberWithPrefix(): string
    {
        return '+' . $this->prefix . $this->number;
    }
}
