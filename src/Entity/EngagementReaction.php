<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EngagementReactionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: EngagementReactionRepository::class)]
#[ORM\UniqueConstraint(
    name: 'uniq_engagement_user_reaction',
    columns: ['engagement_id', 'user_id', 'reaction_id']
)]
class EngagementReaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(#[ORM\ManyToOne(targetEntity: Engagement::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Engagement $engagement, #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user, #[ORM\ManyToOne(targetEntity: Reaction::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Reaction $reaction)
    {
        $this->id = Uuid::v4();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getEngagement(): Engagement
    {
        return $this->engagement;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getReaction(): Reaction
    {
        return $this->reaction;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
