<?php

namespace App\Entity;

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
class UserNotificationSettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    private ?Uuid $id = null;

    // --- TRADER SIDE ---
    // When a client posts a new job that matches me
    #[ORM\Column(type: 'boolean')]
    private bool $traderNewMatchingJobEmail = true;

    #[ORM\Column(type: 'boolean')]
    private bool $traderNewMatchingJobSms = false;

    // --- CLIENT SIDE ---
    // When a trader submits a quote on my job
    #[ORM\Column(type: 'boolean')]
    private bool $clientNewQuoteOnMyJobEmail = true;

    #[ORM\Column(type: 'boolean')]
    private bool $clientNewQuoteOnMyJobSms = false;

    // When there’s a new message on a job I’m involved in
    #[ORM\Column(type: 'boolean')]
    private bool $jobNewMessageEmail = true;

    #[ORM\Column(type: 'boolean')]
    private bool $jobNewMessageSms = false;

    // (Optional) marketing etc.
    #[ORM\Column(type: 'boolean')]
    private bool $marketingEmail = false;

    #[ORM\Column(type: 'boolean')]
    private bool $marketingSms = false;

    public function __construct(
        #[ORM\OneToOne(inversedBy: 'notificationSettings', targetEntity: User::class)]
        #[ORM\JoinColumn(unique: true, nullable: false)]
        private User $user
    )
    {
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function isTraderNewMatchingJobEmail(): bool
    {
        return $this->traderNewMatchingJobEmail;
    }

    public function setTraderNewMatchingJobEmail(bool $traderNewMatchingJobEmail): void
    {
        $this->traderNewMatchingJobEmail = $traderNewMatchingJobEmail;
    }

    public function isTraderNewMatchingJobSms(): bool
    {
        return $this->traderNewMatchingJobSms;
    }

    public function setTraderNewMatchingJobSms(bool $traderNewMatchingJobSms): void
    {
        $this->traderNewMatchingJobSms = $traderNewMatchingJobSms;
    }

    public function isClientNewQuoteOnMyJobEmail(): bool
    {
        return $this->clientNewQuoteOnMyJobEmail;
    }

    public function setClientNewQuoteOnMyJobEmail(bool $clientNewQuoteOnMyJobEmail): void
    {
        $this->clientNewQuoteOnMyJobEmail = $clientNewQuoteOnMyJobEmail;
    }

    public function isClientNewQuoteOnMyJobSms(): bool
    {
        return $this->clientNewQuoteOnMyJobSms;
    }

    public function setClientNewQuoteOnMyJobSms(bool $clientNewQuoteOnMyJobSms): void
    {
        $this->clientNewQuoteOnMyJobSms = $clientNewQuoteOnMyJobSms;
    }

    public function isJobNewMessageEmail(): bool
    {
        return $this->jobNewMessageEmail;
    }

    public function setJobNewMessageEmail(bool $jobNewMessageEmail): void
    {
        $this->jobNewMessageEmail = $jobNewMessageEmail;
    }

    public function isJobNewMessageSms(): bool
    {
        return $this->jobNewMessageSms;
    }

    public function setJobNewMessageSms(bool $jobNewMessageSms): void
    {
        $this->jobNewMessageSms = $jobNewMessageSms;
    }

    public function isMarketingEmail(): bool
    {
        return $this->marketingEmail;
    }

    public function setMarketingEmail(bool $marketingEmail): void
    {
        $this->marketingEmail = $marketingEmail;
    }

    public function isMarketingSms(): bool
    {
        return $this->marketingSms;
    }

    public function setMarketingSms(bool $marketingSms): void
    {
        $this->marketingSms = $marketingSms;
    }
}
