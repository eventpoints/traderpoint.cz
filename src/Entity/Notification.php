<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\NotificationTypeEnum;
use App\Repository\NotificationRepository;
use Carbon\CarbonImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Index(columns: ['type', 'created_at'])]
final class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private CarbonImmutable $createdAt;

    /**
     * @param array<string, string>|null $context
     */
    public function __construct(
        #[ORM\ManyToOne(targetEntity: User::class)]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private ?User $user,
        /**
         * Business reason / event key, e.g.:
         * - trader.service_radius_missing
         * - membership.renewal_soon
         * - billing.payment_failed
         */
        #[ORM\Column(length: 120, enumType: NotificationTypeEnum::class)]
        private NotificationTypeEnum $type,
        /**
         * Locale used for this notification (e.g. "en", "cs", "ru").
         * This is the locale we actually rendered the content in.
         */
        #[ORM\Column(length: 8)]
        private string $locale,
        /**
         * Optional grouping key to avoid duplicates across time windows,
         * e.g. "service-radius-2025-12" or a hash.
         */
        #[ORM\Column(length: 120, nullable: true)]
        private ?string $dedupeKey = null,
        /**
         * Arbitrary context snapshot (safe, minimal),
         * e.g. { "radius": null, "profileId": "...", "template": "..." }
         */
        #[ORM\Column(type: Types::JSON, nullable: true)]
        private ?array $context = null,
    ) {
        $this->createdAt = CarbonImmutable::now();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getType(): NotificationTypeEnum
    {
        return $this->type;
    }

    public function getDedupeKey(): ?string
    {
        return $this->dedupeKey;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @return array<string, string>|null
     */
    public function getContext(): ?array
    {
        return $this->context;
    }

    public function getCreatedAt(): CarbonImmutable
    {
        return $this->createdAt;
    }
}
