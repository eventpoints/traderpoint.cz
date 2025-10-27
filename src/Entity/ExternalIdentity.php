<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\OauthProviderEnum;
use App\Repository\ExternalIdentityRepository;
use Carbon\CarbonImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ExternalIdentityRepository::class)]
#[ORM\Table(name: 'external_identity')]
#[ORM\UniqueConstraint(name: 'uniq_provider_subject', columns: ['oauth_provider_enum', 'subject'])]
#[ORM\HasLifecycleCallbacks]
class ExternalIdentity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\Column(type: Types::STRING, length: 191, nullable: true)]
    private ?string $emailAtProvider = null;

    // Optional token storage (only if you really need to call APIs)
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $accessToken = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $refreshToken = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?CarbonImmutable $tokenExpiresAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private CarbonImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?CarbonImmutable $lastLoginAt = null;

    /**
     * @param string[] $grantedScopes
     */
    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'externalIdentities')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private User $user,
        #[ORM\Column(type: Types::STRING, length: 32, enumType: OauthProviderEnum::class)]
        private OauthProviderEnum $oauthProviderEnum,
        #[ORM\Column(type: Types::STRING, length: 191)]
        private string $subject,
        #[ORM\Column(type: Types::BOOLEAN)]
        private bool $emailVerified,
        #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
        private ?string $displayName,
        #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
        private ?string $avatarUrl = null,
        #[ORM\Column(type: Types::JSON, nullable: true)]
        private ?array $grantedScopes = []
    )
    {
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    #[ORM\PrePersist]
    public function onCreate(): void
    {
        $this->createdAt = CarbonImmutable::now();
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setOauthProviderEnum(OauthProviderEnum $p): self
    {
        $this->oauthProviderEnum = $p;
        return $this;
    }

    public function getOauthProviderEnum(): OauthProviderEnum
    {
        return $this->oauthProviderEnum;
    }

    public function setSubject(string $s): self
    {
        $this->subject = $s;
        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function updateLastLogin(): void
    {
        $this->lastLoginAt = CarbonImmutable::now();
    }

    public function getEmailAtProvider(): ?string
    {
        return $this->emailAtProvider;
    }

    public function setEmailAtProvider(?string $emailAtProvider): void
    {
        $this->emailAtProvider = $emailAtProvider;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function setEmailVerified(bool $emailVerified): void
    {
        $this->emailVerified = $emailVerified;
    }

    /**
     * @return string[]|null
     */
    public function getGrantedScopes(): ?array
    {
        return $this->grantedScopes;
    }

    /**
     * @param null|string[] $grantedScopes
     */
    public function setGrantedScopes(?array $grantedScopes): void
    {
        $this->grantedScopes = $grantedScopes;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): void
    {
        $this->displayName = $displayName;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): void
    {
        $this->avatarUrl = $avatarUrl;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(?string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(?string $refreshToken): void
    {
        $this->refreshToken = $refreshToken;
    }

    public function getTokenExpiresAt(): ?CarbonImmutable
    {
        return $this->tokenExpiresAt;
    }

    public function setTokenExpiresAt(?CarbonImmutable $tokenExpiresAt): void
    {
        $this->tokenExpiresAt = $tokenExpiresAt;
    }

    public function getCreatedAt(): CarbonImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(CarbonImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getLastLoginAt(): ?CarbonImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?CarbonImmutable $lastLoginAt): void
    {
        $this->lastLoginAt = $lastLoginAt;
    }
}
