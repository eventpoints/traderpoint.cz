<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
class StripeProfile
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $stripeCustomerId = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $stripeSubscriptionId = null;

    // e.g. "standard", "premium", null
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $currentPlan = null;

    // Mirror Stripe subscription status: trialing, active, canceled, past_due, etc.
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $subscriptionStatus = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $trialEndsAt = null;

    public function __construct(
        #[ORM\OneToOne(inversedBy: 'billingProfile')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private User $user
    )
    {
        $this->id = Uuid::v4();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getStripeCustomerId(): ?string
    {
        return $this->stripeCustomerId;
    }

    public function setStripeCustomerId(?string $stripeCustomerId): self
    {
        $this->stripeCustomerId = $stripeCustomerId;
        return $this;
    }

    public function getStripeSubscriptionId(): ?string
    {
        return $this->stripeSubscriptionId;
    }

    public function setStripeSubscriptionId(?string $stripeSubscriptionId): self
    {
        $this->stripeSubscriptionId = $stripeSubscriptionId;
        return $this;
    }

    public function getCurrentPlan(): ?string
    {
        return $this->currentPlan;
    }

    public function setCurrentPlan(?string $currentPlan): self
    {
        $this->currentPlan = $currentPlan;
        return $this;
    }

    public function getSubscriptionStatus(): ?string
    {
        return $this->subscriptionStatus;
    }

    public function setSubscriptionStatus(?string $subscriptionStatus): self
    {
        $this->subscriptionStatus = $subscriptionStatus;
        return $this;
    }

    public function getTrialEndsAt(): ?\DateTimeImmutable
    {
        return $this->trialEndsAt;
    }

    public function setTrialEndsAt(?\DateTimeImmutable $trialEndsAt): self
    {
        $this->trialEndsAt = $trialEndsAt;
        return $this;
    }

    public function isOnTrial(): bool
    {
        return $this->subscriptionStatus === 'trialing'
            && $this->trialEndsAt instanceof \DateTimeImmutable
            && $this->trialEndsAt > new \DateTimeImmutable();
    }

    public function isActivePaid(): bool
    {
        return $this->subscriptionStatus === 'active';
    }
}