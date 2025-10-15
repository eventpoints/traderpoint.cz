<?php

namespace App\Repository;

use App\Entity\Engagement;
use App\Entity\Quote;
use App\Entity\Skill;
use App\Entity\User;
use App\Enum\EngagementStatusEnum;
use App\Enum\PaymentStatusEnum;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

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
    public function findUpcomingBySkillsAndLocation(User $user, bool $isQuery = false): array|Query
    {
        $profile = $user->getTraderProfile();
        if (!$profile || $profile->getLatitude() === null || $profile->getLongitude() === null || !$profile->getServiceRadius()) {
            return $isQuery ? $this->createQueryBuilder('e')->where('1=0')->getQuery() : [];
        }

        $lat = (float)$profile->getLatitude();
        $lng = (float)$profile->getLongitude();
        $radiusKm = (float)$profile->getServiceRadius();
        $meters = (int)round($radiusKm * 1000);

        // --- PostGIS helper returns ordered IDs by distance (each row: ['id' => '...', 'dist' => ...])
        $rows = $this->findNearbyIdsOrdered($lat, $lng, $meters);
        if ($rows === []) {
            return $isQuery ? $this->createQueryBuilder('e')->where('1=0')->getQuery() : [];
        }

        $orderedIdStrings = array_column($rows, 'id');
        $orderedUuids = array_map([Uuid::class, 'fromString'], $orderedIdStrings);

        $qb = $this->createQueryBuilder('engagement');
        $qb->leftJoin('engagement.skills', 'skill');
        $qb->leftJoin('engagement.payments', 'payment');

        $sub = $this->getEntityManager()->createQueryBuilder()
            ->select('1')
            ->from(Quote::class, 'quote')
            ->where('quote.engagement = engagement')
            ->andWhere('quote.owner = :user');

        $qb->andWhere(
            $qb->expr()->not(
                $qb->expr()->exists($sub->getDQL())
            )
        )->setParameter('user', $user);


        $now = CarbonImmutable::now();

        $qb->andWhere(
            $qb->expr()->eq('engagement.status', ':status')
        )->setParameter('status', EngagementStatusEnum::PENDING);


        $skillIds = $user->getTraderProfile()->getSkills()
            ->map(fn(Skill $skill): string => $skill->getId()->toRfc4122())
            ->toArray();

        $qb->andWhere(
            $qb->expr()->in('skill.id', ':skills')
        )->setParameter('skills', $skillIds);

        $qb->andWhere(
            $qb->expr()->eq('payment.status', ':paid')
        )->setParameter('paid', PaymentStatusEnum::PAID);

        $cutoff = $now->addDays(30);
        $qb->andWhere(
            $qb->expr()->lte('engagement.createdAt', ':cutoff')
        )->setParameter('cutoff', $cutoff->toDateTimeImmutable(), \Doctrine\DBAL\Types\Types::DATETIME_IMMUTABLE);

        // --- restrict to nearby IDs
        $qb->andWhere(
            $qb->expr()->in('engagement.id', ':ids')
        )->setParameter('ids', $orderedUuids);

        // --- preserve the PostGIS order with a CASE rank
        $caseParts = [];
        foreach ($orderedIdStrings as $i => $id) {
            $param = 'rank_id_' . $i;
            $caseParts[] = "WHEN engagement.id = :$param THEN $i";
            $qb->setParameter($param, Uuid::fromString($id));
        }
        $rankExpr = '(CASE ' . implode(' ', $caseParts) . ' ELSE ' . count($orderedIdStrings) . ' END)';

        $qb->addSelect($rankExpr . ' AS HIDDEN _rank')
            ->orderBy('_rank', 'ASC')
            ->addOrderBy('engagement.createdAt', 'DESC');

        if ($isQuery) {
            return $qb->getQuery();
        }

        return $qb->getQuery()->getResult();
    }

    private function findNearbyIdsOrdered(float $lat, float $lng, int $meters, int $limit = 500): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $qb = $conn->createQueryBuilder();

        $targetGeom = 'ST_SetSRID(ST_MakePoint(:lng, :lat), 4326)';      // geometry
        $targetGeog = $targetGeom . '::geography';                        // geography (meters)

        // Keep a precise distance column (meters) for display/tie-breaks
        $qb->select('e.id::text AS id')
            ->addSelect("ST_Distance(e.point::geography, $targetGeog) AS dist")
            ->from('engagement', 'e')
            ->where('e.point IS NOT NULL')
            ->andWhere("ST_DWithin(e.point::geography, $targetGeog, :meters)")
            // KNN: nearest-first via index (geometry operator)
            ->orderBy("e.point <-> $targetGeom", 'ASC')
            ->setParameter('lat', $lat)
            ->setParameter('lng', $lng)
            ->setParameter('meters', $meters)
            ->setMaxResults($limit);

        return $qb->executeQuery()->fetchAllAssociative();
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


}
