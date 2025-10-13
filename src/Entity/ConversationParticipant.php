<?php

namespace App\Entity;

use App\Repository\ConversationParticipantRepository;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ConversationParticipantRepository::class)]
class ConversationParticipant
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    private Uuid $id;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private null|CarbonInterface $createdAt = null;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'participant')]
    private Collection $messages;

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'participants')]
        private ?Conversation $conversation,
        #[ORM\ManyToOne(inversedBy: 'conversationParticipates')]
        private User $owner
    )
    {
        $this->createdAt = new CarbonImmutable();
        $this->messages = new ArrayCollection();
    }

    public function getId(): null|Uuid
    {
        return $this->id;
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

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): static
    {
        $this->owner = $owner;

        return $this;
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
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (! $this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setParticipant($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        // set the owning side to null (unless already changed)
        if ($this->messages->removeElement($message) && $message->getParticipant() === $this) {
            $message->setParticipant(null);
        }

        return $this;
    }
}
