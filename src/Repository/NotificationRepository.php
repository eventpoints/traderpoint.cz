<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use App\Enum\NotificationTypeEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
final class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * Find an existing logical Notification for this user/type/dedupeKey,
     * or create & persist a new one (no flush).
     *
     * @param array<mixed>|null $context
     */
    public function findOrCreateForUserTypeAndDedupeKey(
        User $user,
        NotificationTypeEnum $type,
        ?string $dedupeKey,
        string $locale,
        ?array $context = null,
    ): Notification
    {
        $existing = $this->findOneBy([
            'user' => $user,
            'type' => $type,
            'dedupeKey' => $dedupeKey,
        ]);

        if ($existing instanceof Notification) {
            return $existing;
        }

        return new Notification($user, $type, $locale, $dedupeKey, $context);
    }
}
