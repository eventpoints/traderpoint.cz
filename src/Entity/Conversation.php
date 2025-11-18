<?php

namespace App\Entity;

use App\Enum\ConversationTypeEnum;
use App\Repository\ConversationRepository;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ConversationRepository::class)]
class Conversation
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 32, enumType: ConversationTypeEnum::class)]
    private ConversationTypeEnum $type;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private null|CarbonInterface $createdAt = null;

    /**
     * @var Collection<int, ConversationParticipant>
     */
    #[ORM\OneToMany(targetEntity: ConversationParticipant::class, mappedBy: 'conversation', cascade: ['persist'])]
    private Collection $participants;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'conversation', cascade: ['persist'])]
    private Collection $messages;

    #[ORM\ManyToOne(inversedBy: 'createdConversations')]
    private ?User $owner = null;

    #[ORM\OneToOne(inversedBy: 'conversation')]
    #[ORM\JoinColumn(name: 'engagement_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Engagement $engagement = null;

    public function __construct(?User $owner)
    {
        $this->owner      = $owner;
        $this->participants = new ArrayCollection();
        $this->messages     = new ArrayCollection();
        $this->createdAt    = new CarbonImmutable();
        $this->type         = ConversationTypeEnum::DIRECT; // or whatever default
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getCreatedAt(): ?CarbonInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?CarbonInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return Collection<int, ConversationParticipant>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(ConversationParticipant $conversationParticipate): static
    {
        if (!$this->participants->contains($conversationParticipate)) {
            $this->participants->add($conversationParticipate);
            $conversationParticipate->setConversation($this);
        }

        return $this;
    }

    public function removeParticipant(ConversationParticipant $conversationParticipate): static
    {
        if ($this->participants->removeElement($conversationParticipate) && $conversationParticipate->getConversation() === $this) {
            $conversationParticipate->setConversation(null);
        }

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setConversation($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message) && $message->getConversation() === $this) {
            $message->setConversation(null);
        }

        return $this;
    }

    public function getEngagement(): ?Engagement
    {
        return $this->engagement;
    }

    public function setEngagement(?Engagement $engagement): static
    {
        $this->engagement = $engagement;

        return $this;
    }

    public function getType(): ConversationTypeEnum
    {
        return $this->type;
    }

    public function setType(ConversationTypeEnum $type): static
    {
        $this->type = $type;

        return $this;
    }
}

