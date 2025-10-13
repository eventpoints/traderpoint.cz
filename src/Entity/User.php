<?php

namespace App\Entity;

use App\Enum\UserRoleEnum;
use App\Repository\UserRepository;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{

    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $token;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    private null|Uuid $id = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private string $password;

    #[ORM\OneToOne(targetEntity: TraderProfile::class, mappedBy: 'owner', cascade: ['persist','remove'])]
    private ?TraderProfile $traderProfile = null;

    /**
     * @var Collection<int, Review>
     */
    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'owner', cascade: ['persist', 'remove'])]
    private Collection $reviews;

    #[ORM\OneToOne(targetEntity: PhoneNumber::class)]
    #[ORM\JoinColumn(name: 'phone_number_id', referencedColumnName: 'id')]
    private PhoneNumber|null $phoneNumber = null;

    /**
     * @var Collection<int, ConversationParticipant>
     */
    #[ORM\OneToMany(targetEntity: ConversationParticipant::class, mappedBy: 'owner')]
    private Collection $conversationParticipates;
    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $firstName;
    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $lastName;
    #[ORM\Column(length: 180)]
    #[Assert\Email]
    private string $email;
    #[ORM\Column(type: Types::TEXT)]
    private string $avatar;
    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private CarbonImmutable $createdAt;

    public function __construct()
    {
        $this->token = Uuid::v7();
        $this->reviews = new ArrayCollection();
        $this->conversationParticipates = new ArrayCollection();
        $this->createdAt = new CarbonImmutable();
    }

    public
    function getId(): Uuid
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @return list<string>
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = UserRoleEnum::ROLE_USER->name;
        return array_values(array_unique($roles));
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setOwner($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review) && $review->getOwner() === $this) {
            $review->setOwner(null);
        }

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getAvatar(): string
    {
        return $this->avatar;
    }

    public function setAvatar(string $avatar): void
    {
        $this->avatar = $avatar;
    }

    public function eraseCredentials(): void
    {
    }

    public function getToken(): Uuid
    {
        return $this->token;
    }

    public function setToken(Uuid $token): void
    {
        $this->token = $token;
    }

    public function getPhoneNumber(): ?PhoneNumber
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?PhoneNumber $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * @return Collection<int, ConversationParticipant>
     */
    public function getConversationParticipates(): Collection
    {
        return $this->conversationParticipates;
    }

    public function addConversationParticipate(ConversationParticipant $conversationParticipate): static
    {
        if (!$this->conversationParticipates->contains($conversationParticipate)) {
            $this->conversationParticipates->add($conversationParticipate);
            $conversationParticipate->setOwner($this);
        }

        return $this;
    }

    public function removeConversationParticipate(ConversationParticipant $conversationParticipate): static
    {
        $this->conversationParticipates->removeElement($conversationParticipate);
        return $this;
    }

    public function isTrader(): bool {
        return in_array(UserRoleEnum::ROLE_TRADER->value, $this->getRoles(), true);
    }

    public function getTraderProfile(): ?TraderProfile
    {
        return $this->traderProfile;
    }

    public function setTraderProfile(?TraderProfile $traderProfile): void
    {
        $this->traderProfile = $traderProfile;
    }

    public function getCreatedAt(): CarbonImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(CarbonImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }

}

