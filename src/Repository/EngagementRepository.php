<?php

namespace App\Repository;

use App\Entity\Engagement;
use App\Entity\Quote;
use App\Entity\Skill;
use App\Entity\User;
use App\Enum\EngagementStatusEnum;
use App\Enum\EngagementStatusGroupEnum;
use App\Enum\QuoteStatusEnum;
use Carbon\CarbonImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Nette\Utils\Strings;
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

        $lat = $profile->getLatitude();
        $lng = $profile->getLongitude();
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

        $qb->andWhere(
            $qb->expr()->eq('engagement.status', ':status')
        )->setParameter('status', EngagementStatusEnum::RECEIVING_QUOTES);

        $skillIds = $user->getTraderProfile()->getSkills()
            ->map(fn(Skill $skill): string => $skill->getId()->toRfc4122())
            ->toArray();

        $qb->andWhere(
            $qb->expr()->in('skill.id', ':skills')
        )
            ->setParameter('skills', $skillIds)
            ->distinct();

        $now = CarbonImmutable::now();
        $qb->andWhere(
            $qb->expr()->gte('engagement.createdAt', ':thirtyDaysAgo')
        )->setParameter('thirtyDaysAgo', $now->subMonth()->startOfDay()->toDateTimeImmutable(), Types::DATETIME_IMMUTABLE);

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


    /**
     * @return list<array{id: string, dist: numeric-string}>
     * @throws \Doctrine\DBAL\Exception
     */
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

    /**
     * @return array<int, Engagement>|Query
     */
    public function findByOwnerAndEngagementStatus(User $user, bool $isQuery = false, EngagementStatusGroupEnum $status = EngagementStatusGroupEnum::DISCOVER): array|Query
    {
        $qb = $this->createQueryBuilder('engagement');

        $qb->andWhere(
            $qb->expr()->eq('engagement.owner', ':owner')
        )->setParameter('owner', $user->getId());

        $qb->andWhere(
            $qb->expr()->eq('engagement.isDeleted', ':false')
        )->setParameter('false', false);

        $this->findByStatus(qb: $qb, user: $user, engagementStatusGroupEnum: $status, isQuery: true);

        $qb->orderBy('engagement.createdAt', Order::Descending->value);

        if ($isQuery) {
            return $qb->getQuery();
        }

        return $qb->getQuery()->getResult();
    }

    public function findByStatus(null|QueryBuilder $qb, User $user, null|EngagementStatusGroupEnum $engagementStatusGroupEnum = EngagementStatusGroupEnum::DISCOVER, bool $isQuery = false): QueryBuilder|array
    {
        if (!$qb instanceof QueryBuilder) {
            $qb = $this->createQueryBuilder('engagement');
        }

        $statuses = match (true) {
            $user->isTrader() && $engagementStatusGroupEnum == EngagementStatusGroupEnum::HISTORICAL => EngagementStatusEnum::getHistoricalStatusesForTrader(),
            $user->isTrader() && $engagementStatusGroupEnum == EngagementStatusGroupEnum::DISCOVER => EngagementStatusEnum::getActiveStatusesForTrader(),
            !$user->isTrader() && $engagementStatusGroupEnum == EngagementStatusGroupEnum::ACTIVE => EngagementStatusEnum::getActiveStatusesForClient(),
            !$user->isTrader() && $engagementStatusGroupEnum == EngagementStatusGroupEnum::HISTORICAL => EngagementStatusEnum::getHistoricalStatusesForClient(),
        };

        $qb->andWhere(
            $qb->expr()->in('engagement.status', ':statuses')
        )->setParameter('statuses', $statuses);

        return $isQuery ? $qb : $qb->getQuery()->getResult();
    }

    public function findByPendingQuoteForTrader(User $user, bool $isQuery = false): array|Query
    {
        $qb = $this->createQueryBuilder('engagement');
        $qb->innerJoin('engagement.quotes', 'quote');

        // Find engagements where this trader has submitted a quote
        $qb->andWhere(
            $qb->expr()->eq('quote.owner', ':trader')
        )->setParameter('trader', $user->getId(), 'uuid');

        // Quote must be in SUBMITTED status (awaiting client response)
        $qb->andWhere(
            $qb->expr()->eq('quote.status', ':quoteStatus')
        )->setParameter('quoteStatus', QuoteStatusEnum::SUBMITTED);

        // Engagement must be in RECEIVING_QUOTES state
        $qb->andWhere(
            $qb->expr()->eq('engagement.status', ':engagementStatus')
        )->setParameter('engagementStatus', EngagementStatusEnum::RECEIVING_QUOTES);

        // Use distinct to avoid duplicate results from join
        $qb->distinct(true);

        // Order by engagement creation date instead of quote creation date to avoid pagination issues
        $qb->orderBy('engagement.createdAt', 'DESC');

        return $isQuery ? $qb->getQuery() : $qb->getQuery()->getResult();
    }

    public function findHistoricalForTrader(User $user, bool $isQuery, null|EngagementStatusEnum $engagementStatusEnum = null) : array|QueryBuilder
    {
        $qb = $this->createQueryBuilder('engagement');
        $qb->leftJoin('engagement.quote', 'quote');

        $qb->andWhere(
            $qb->expr()->eq('quote.owner', ':owner')
        )->setParameter('owner', $user->getId(), 'uuid');

        if (!$engagementStatusEnum instanceof EngagementStatusEnum) {
            $this->findByStatus($qb, $user, EngagementStatusGroupEnum::HISTORICAL, $isQuery);
        } else {
            $qb->andWhere(
                $qb->expr()->eq('engagement.status', ':status')
            )->setParameter('status', $engagementStatusEnum);
        }

        return $isQuery ? $qb : $qb->getQuery()->getResult();
    }


}