<?php

namespace App\Repository;

use App\Entity\Engagement;
use App\Entity\Skill;
use App\Entity\User;
use App\Enum\EngagementStatusEnum;
use App\Enum\PaymentStatusEnum;
use Carbon\CarbonImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Engagement>
 */
class EngagementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Engagement::class);
    }

    /**
     * @return array<int,Engagement>|Query
     */
    public function findUpcomingBySkills(User $user, bool $isQuery = false): array|Query
    {
        $profile = $user->getTraderProfile();
        if (! $profile || $profile->getLatitude() === null || $profile->getLongitude() === null || ! $profile->getServiceRadius()) {
            return $isQuery ? $this->createQueryBuilder('e')->where('1=0')->getQuery() : [];
        }

        $lat = $profile->getLatitude();
        $lng = $profile->getLongitude();
        $radiusKm = (float) $profile->getServiceRadius();

        // ---- radius filter via native SQL
        $radiusIds = $this->findEngagementIdsWithinRadius($lat, $lng, $radiusKm);
        if ($radiusIds === []) {
            return $isQuery ? $this->createQueryBuilder('e')->where('1=0')->getQuery() : [];
        }

        $qb = $this->createQueryBuilder('engagement');
        $qb->leftJoin('engagement.skills', 'skill');
        $qb->leftJoin('engagement.payments', 'payment');

        $now = CarbonImmutable::now();

        $qb->andWhere(
            $qb->expr()->eq('engagement.status', ':status')
        )->setParameter('status', EngagementStatusEnum::PENDING);

        $skillIds = $user->getTraderProfile()->getSkills()->map(fn(Skill $skill): string => $skill->getId()->toRfc4122())->toArray();
        $qb->andWhere(
            $qb->expr()->in('skill.id', ':skills')
        )->setParameter('skills', $skillIds);

        $qb->andWhere(
            $qb->expr()->gt('engagement.dueAt', ':dueAt')
        )->setParameter('dueAt', $now->toDateTimeImmutable());

        $qb->andWhere(
            $qb->expr()->eq('payment.status', ':paid')
        )->setParameter('paid', PaymentStatusEnum::PAID);

        $cutoff = $now->addDays(30);
        $qb->andWhere(
            $qb->expr()->lte('engagement.createdAt', ':cutoff')
        )->setParameter('cutoff', $cutoff->toDateTimeImmutable(), Types::DATETIME_IMMUTABLE);

        // ---- apply radius IDs (UUIDs or ints)
        $qb->andWhere(
            $qb->expr()->in('engagement.id', ':radiusIds')
        )->setParameter('radiusIds', $radiusIds);

        if ($isQuery) {
            return $qb->getQuery();
        }

        return $qb->getQuery()->getResult();
    }

    public function save(Engagement $entity, bool $flush = false): void
    {
        $this->getEntityManager()
            ->persist($entity);

        if ($flush) {
            $this->getEntityManager()
                ->flush();
        }
    }

    public function remove(Engagement $entity, bool $flush = false): void
    {
        $this->getEntityManager()
            ->remove($entity);

        if ($flush) {
            $this->getEntityManager()
                ->flush();
        }
    }

    public function findByOwner(User $currentUser, bool $isQuery = false)
    {
        $qb = $this->createQueryBuilder('engagement');
        $qb->leftJoin('engagement.payments', 'payment');

        $qb->andWhere(
            $qb->expr()->eq('engagement.owner', ':owner')
        )->setParameter('owner', $currentUser->getId());

        $qb->andWhere(
            $qb->expr()->eq('engagement.status', ':status')
        )->setParameter('status', EngagementStatusEnum::PENDING);

        $qb->orderBy('engagement.createdAt', Order::Descending->value);

        if ($isQuery) {
            return $qb->getQuery();
        }

        return $qb->getQuery()->getResult();
    }

    private function findEngagementIdsWithinRadius(float $lat, float $lng, float $radiusKm): array
    {
        $conn = $this->getEntityManager()->getConnection();

        // Fast bbox to cut work, then exact Haversine (meters)
        $latDelta = $radiusKm / 111.32;
        $lngDelta = $radiusKm / (111.32 * max(0.00001, cos(deg2rad($lat))));

        $sql = <<<SQL
SELECT e.id
FROM engagement e
WHERE e.latitude BETWEEN :latMin AND :latMax
  AND e.longitude BETWEEN :lngMin AND :lngMax
  AND (
        6371000 * 2 * ASIN(
            SQRT(
                POWER(SIN(RADIANS(e.latitude  - :lat) / 2), 2) +
                COS(RADIANS(:lat)) * COS(RADIANS(e.latitude)) *
                POWER(SIN(RADIANS(e.longitude - :lng) / 2), 2)
            )
        )
      ) <= (:radiusKm * 1000)
SQL;

        $rows = $conn->executeQuery($sql, [
            'lat' => $lat,
            'lng' => $lng,
            'radiusKm' => $radiusKm,
            'latMin' => $lat - $latDelta,
            'latMax' => $lat + $latDelta,
            'lngMin' => $lng - $lngDelta,
            'lngMax' => $lng + $lngDelta,
        ])->fetchAllAssociative();

        // Returns UUID strings (or ints) depending on your PK
        return array_map(static fn(array $r): mixed => $r['id'], $rows);
    }
}
