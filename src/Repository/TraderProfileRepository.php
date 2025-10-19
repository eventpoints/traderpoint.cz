<?php

namespace App\Repository;

use App\Entity\Engagement;
use App\Entity\TraderProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Generator;

/**
 * @extends ServiceEntityRepository<TraderProfile>
 */
class TraderProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TraderProfile::class);
    }

    public function save(TraderProfile $entity, bool $flush = false): void
    {
        $this->getEntityManager()
            ->persist($entity);

        if ($flush) {
            $this->getEntityManager()
                ->flush();
        }
    }

    public function remove(TraderProfile $entity, bool $flush = false): void
    {
        $this->getEntityManager()
            ->remove($entity);

        if ($flush) {
            $this->getEntityManager()
                ->flush();
        }
    }

    /**
     * @return Generator<TraderProfile>
     */
    public function iterateTradersForEngagement(Engagement $engagement, bool $requireAllSkills = false): Generator
    {
        $em = $this->getEntityManager();
        $eid = $engagement->getId()->toRfc4122();

        $sqlAny = <<<SQL
WITH job AS (
  SELECT e.id,
         COALESCE(e.point::geography,
                  ST_SetSRID(ST_MakePoint(e.longitude, e.latitude), 4326)::geography) AS geo
  FROM engagement e
  WHERE e.id = :engagement_id
)
SELECT tp.*
FROM trader_profile tp
JOIN trader_profile_skill tps
  ON tps.trader_profile_id = tp.id
JOIN engagement_skill es
  ON es.engagement_id = :engagement_id
 AND es.skill_id = tps.skill_id
CROSS JOIN job j
WHERE tp.latitude IS NOT NULL
  AND tp.longitude IS NOT NULL
  AND tp.service_radius IS NOT NULL
  AND ST_DWithin(
        ST_SetSRID(ST_MakePoint(tp.longitude, tp.latitude), 4326)::geography,
        j.geo,
        (tp.service_radius * 1000)::int
      )
GROUP BY tp.id, j.geo
ORDER BY ST_Distance(
         ST_SetSRID(ST_MakePoint(tp.longitude, tp.latitude), 4326)::geography,
         j.geo
       ) ASC
SQL;

        $sqlAll = <<<SQL
WITH job AS (
  SELECT e.id,
         COALESCE(e.point::geography,
                  ST_SetSRID(ST_MakePoint(e.longitude, e.latitude), 4326)::geography) AS geo
  FROM engagement e WHERE e.id = :engagement_id
)
SELECT tp.*
FROM trader_profile tp
JOIN engagement_skill es
  ON es.engagement_id = :engagement_id
JOIN trader_profile_skill tps
  ON tps.trader_profile_id = tp.id
 AND tps.skill_id = es.skill_id
CROSS JOIN job j
WHERE tp.latitude IS NOT NULL
  AND tp.longitude IS NOT NULL
  AND tp.service_radius IS NOT NULL
  AND ST_DWithin(
        ST_SetSRID(ST_MakePoint(tp.longitude, tp.latitude), 4326)::geography,
        j.geo,
        (tp.service_radius * 1000)::int
      )
GROUP BY tp.id
HAVING COUNT(DISTINCT es.skill_id) = (
  SELECT COUNT(*) FROM engagement_skill es2 WHERE es2.engagement_id = :engagement_id
)
ORDER BY ST_Distance(
         ST_SetSRID(ST_MakePoint(tp.longitude, tp.latitude), 4326)::geography,
         j.geo
       ) ASC
SQL;

        $sql = $requireAllSkills ? $sqlAll : $sqlAny;

        $rsm = new ResultSetMappingBuilder($em);
        $rsm->addRootEntityFromClassMetadata(TraderProfile::class, 'tp');

        $q = $em->createNativeQuery($sql, $rsm)
            ->setParameter('engagement_id', $eid);

        foreach ($q->toIterable() as $profile) {
            yield $profile; // $profile->getUser() → email / locale
            // Optionally clear per chunk if iterating thousands:
            // static $i=0; if ((++$i % 200) === 0) { $em->clear(TraderProfile::class); }
        }
    }
}
