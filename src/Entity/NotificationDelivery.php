<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\NotificationChannelEnum;
use App\Enum\NotificationDeliveryStatusEnum;
use App\Repository\NotificationDeliveryRepository;
use Carbon\CarbonImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: NotificationDeliveryRepository::class)]
#[ORM\Index(columns: ['channel', 'status', 'created_at'])]
final class NotificationDelivery
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Notification::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Notification $notification = null;

    #[ORM\Column(enumType: NotificationChannelEnum::class)]
    private NotificationChannelEnum $channel;

    #[ORM\Column(enumType: NotificationDeliveryStatusEnum::class)]
    private NotificationDeliveryStatusEnum $status = NotificationDeliveryStatusEnum::PENDING;

    /**
     * Template identifier for email/SMS/push
     */
    #[ORM\Column(length: 160, nullable: true)]
    private ?string $template = null;

    /**
     * Provider message ID (SendGrid, Twilio, etc.)
     */
    #[ORM\Column(length: 160, nullable: true)]
    private ?string $providerMessageId = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $payload = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deliveredAt = null;

    public function __construct(
        Notification $notification,
        NotificationChannelEnum $channel,
        ?string $template = null,
        ?array $payload = null,
    ) {
        $this->notification = $notification;
        $this->channel = $channel;
        $this->template = $template;
        $this->payload = $payload;
        $this->createdAt = CarbonImmutable::now();
    }

    public function markSent(?string $providerMessageId = null): void
    {
        $this->status = NotificationDeliveryStatusEnum::SENT;
        $this->sentAt = CarbonImmutable::now();
        $this->providerMessageId = $providerMessageId ?? $this->providerMessageId;
    }

    public function markDelivered(): void
    {
        $this->status = NotificationDeliveryStatusEnum::DELIVERED;
        $this->deliveredAt = CarbonImmutable::now();
    }

    public function markFailed(string $error): void
    {
        $this->status = NotificationDeliveryStatusEnum::FAILED;
        $this->errorMessage = $error;
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getNotification(): ?Notification
    {
        return $this->notification;
    }

    public function getChannel(): NotificationChannelEnum
    {
        return $this->channel;
    }

    public function getStatus(): NotificationDeliveryStatusEnum
    {
        return $this->status;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function getProviderMessageId(): ?string
    {
        return $this->providerMessageId;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getPayload(): ?array
    {
        return $this->payload;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function getDeliveredAt(): ?\DateTimeImmutable
    {
        return $this->deliveredAt;
    }

}