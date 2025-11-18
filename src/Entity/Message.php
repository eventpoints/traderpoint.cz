<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Carbon\CarbonImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    private Uuid $id;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private CarbonImmutable $createdAt;

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'messages')]
        #[ORM\JoinColumn(nullable: false)]
        private null|ConversationParticipant $participant,
        #[ORM\ManyToOne(inversedBy: 'messages')]
        private ?Conversation $conversation
    )
    {
        $this->createdAt = new CarbonImmutable();
    }

    public function getId(): null|Uuid
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getParticipant(): ?ConversationParticipant
    {
        return $this->participant;
    }

    public function setParticipant(?ConversationParticipant $participant): static
    {
        $this->participant = $participant;

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

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(?Conversation $conversation): static
    {
        $this->conversation = $conversation;

        return $this;
    }
}
