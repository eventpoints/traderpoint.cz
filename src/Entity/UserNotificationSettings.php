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
    private bool $isTraderReceiveEmailOnMatchingJob = true;

    #[ORM\Column(type: 'boolean')]
    private bool $isTraderReceiveSmsOnMatchingJob = false;

    // --- CLIENT SIDE ---
    // When a trader submits a quote on my job
    #[ORM\Column(type: 'boolean')]
    private bool $isClientReceiveEmailOnQuote = true;

    #[ORM\Column(type: 'boolean')]
    private bool $isClientReceiveSmsOnQuote = false;

    // When there’s a new message on a job I’m involved in
    #[ORM\Column(type: 'boolean')]
    private bool $isClientReceiveEmailOnEngagementMessage = true;

    #[ORM\Column(type: 'boolean')]
    private bool $isClientReceiveSmsOnEngagmentMessage = false;

    // (Optional) marketing etc.
    #[ORM\Column(type: 'boolean')]
    private bool $isReceiveMarketingEmail = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isReceiveMarketingSms = false;

    public function __construct(
        #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'notificationSettings')]
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

    public function isTraderReceiveEmailOnMatchingJob(): bool
    {
        return $this->isTraderReceiveEmailOnMatchingJob;
    }

    public function setIsTraderReceiveEmailOnMatchingJob(bool $isTraderReceiveEmailOnMatchingJob): void
    {
        $this->isTraderReceiveEmailOnMatchingJob = $isTraderReceiveEmailOnMatchingJob;
    }

    public function isTraderReceiveSmsOnMatchingJob(): bool
    {
        return $this->isTraderReceiveSmsOnMatchingJob;
    }

    public function setIsTraderReceiveSmsOnMatchingJob(bool $isTraderReceiveSmsOnMatchingJob): void
    {
        $this->isTraderReceiveSmsOnMatchingJob = $isTraderReceiveSmsOnMatchingJob;
    }

    public function isClientReceiveEmailOnQuote(): bool
    {
        return $this->isClientReceiveEmailOnQuote;
    }

    public function setIsClientReceiveEmailOnQuote(bool $isClientReceiveEmailOnQuote): void
    {
        $this->isClientReceiveEmailOnQuote = $isClientReceiveEmailOnQuote;
    }

    public function isClientReceiveSmsOnQuote(): bool
    {
        return $this->isClientReceiveSmsOnQuote;
    }

    public function setIsClientReceiveSmsOnQuote(bool $isClientReceiveSmsOnQuote): void
    {
        $this->isClientReceiveSmsOnQuote = $isClientReceiveSmsOnQuote;
    }

    public function isClientReceiveEmailOnEngagementMessage(): bool
    {
        return $this->isClientReceiveEmailOnEngagementMessage;
    }

    public function setIsClientReceiveEmailOnEngagementMessage(bool $isClientReceiveEmailOnEngagementMessage): void
    {
        $this->isClientReceiveEmailOnEngagementMessage = $isClientReceiveEmailOnEngagementMessage;
    }

    public function isClientReceiveSmsOnEngagmentMessage(): bool
    {
        return $this->isClientReceiveSmsOnEngagmentMessage;
    }

    public function setIsClientReceiveSmsOnEngagmentMessage(bool $isClientReceiveSmsOnEngagmentMessage): void
    {
        $this->isClientReceiveSmsOnEngagmentMessage = $isClientReceiveSmsOnEngagmentMessage;
    }

    public function isReceiveMarketingEmail(): bool
    {
        return $this->isReceiveMarketingEmail;
    }

    public function setIsReceiveMarketingEmail(bool $isReceiveMarketingEmail): void
    {
        $this->isReceiveMarketingEmail = $isReceiveMarketingEmail;
    }

    public function isReceiveMarketingSms(): bool
    {
        return $this->isReceiveMarketingSms;
    }

    public function setIsReceiveMarketingSms(bool $isReceiveMarketingSms): void
    {
        $this->isReceiveMarketingSms = $isReceiveMarketingSms;
    }


}
