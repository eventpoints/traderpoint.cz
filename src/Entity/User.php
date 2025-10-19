<?php

namespace App\Entity;

use App\Enum\UserRoleEnum;
use App\Repository\UserRepository;
use Carbon\CarbonImmutable;
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
class User implements UserInterface, PasswordAuthenticatedUserInterface, \Stringable
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

    #[ORM\OneToOne(targetEntity: TraderProfile::class, mappedBy: 'owner', cascade: ['persist', 'remove'])]
    private ?TraderProfile $traderProfile = null;

    /**
     * @var Collection<int, Review>
     */
    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'owner', cascade: ['persist', 'remove'])]
    private Collection $reviews;

    /**
     * @var Collection<int, Engagement>
     */
    #[ORM\OneToMany(targetEntity: Engagement::class, mappedBy: 'owner', cascade: ['persist', 'remove'])]
    private Collection $engagements;

    #[ORM\OneToOne(targetEntity: PhoneNumber::class, cascade: ['persist', 'remove'])]
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

    #[ORM\Column(type: Types::STRING, length: 15, nullable: true)]
    private null|string $preferredLanguage = null;

    /**
     * @var string[] $languages
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Assert\All([
        new Assert\Language(message: 'Invalid language code.'),
    ])]
    #[Assert\Count(max: 10)]
    private array $languages = [];

    #[ORM\Column(length: 180)]
    #[Assert\Email]
    private string $email;

    #[ORM\Column(type: Types::TEXT)]
    private string $avatar;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private null|CarbonImmutable $verifiedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private CarbonImmutable $createdAt;

    public function __construct()
    {
        $this->token = Uuid::v7();
        $this->reviews = new ArrayCollection();
        $this->engagements = new ArrayCollection();
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
        if (! $this->reviews->contains($review)) {
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

    /**
     * @return Collection<int, Engagement>
     */
    public function getEngagements(): Collection
    {
        return $this->engagements;
    }

    public function addEngagement(Engagement $engagement): static
    {
        if (! $this->engagements->contains($engagement)) {
            $this->engagements->add($engagement);
            $engagement->setOwner($this);
        }

        return $this;
    }

    public function removeEngagement(Engagement $engagement): static
    {
        if ($this->engagements->removeElement($engagement) && $engagement->getOwner() === $this) {
            $engagement->setOwner(null);
        }

        return $this;
    }

    public function getVerifiedAt(): ?CarbonImmutable
    {
        return $this->verifiedAt;
    }

    public function setVerifiedAt(?CarbonImmutable $verifiedAt): void
    {
        $this->verifiedAt = $verifiedAt;
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
        if (! $this->conversationParticipates->contains($conversationParticipate)) {
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

    public function isTrader(): bool
    {
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

    public function getPreferredLanguage(): ?string
    {
        return $this->preferredLanguage;
    }

    public function setPreferredLanguage(?string $preferredLanguage): void
    {
        $this->preferredLanguage = $preferredLanguage;
    }

    /**
     * @return string[]
     */
    public function getLanguages(): array
    {
        return $this->languages;
    }

    /**
     * @param array<string> $langs
     * @return $this
     */
    public function setLanguages(array $langs): self
    {
        $langs = array_map(
            static fn ($l) => strtolower(str_replace('-', '_', trim($l))),
            $langs
        );
        $this->languages = array_values(array_unique($langs));
        return $this;
    }

    public function addLanguage(string $lang): self
    {
        $lang = strtolower(str_replace('-', '_', trim($lang)));
        if (! in_array($lang, $this->languages, true)) {
            $this->languages[] = $lang;
        }
        return $this;
    }
}

