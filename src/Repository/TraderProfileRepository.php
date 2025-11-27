<?php

namespace App\Repository;

use App\Entity\Engagement;
use App\Entity\TraderProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Persistence\ManagerRegistry;

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
     * @return \Generator<TraderProfile>
     */
    public function iterateTradersForEngagement(Engagement $engagement, bool $requireAllSkills = false): \Generator
    {
        $em = $this->getEntityManager();
        $eid = $engagement->getId()->toRfc4122();

        // ANY matching skill
        $sqlAny = <<<SQL
WITH job AS (
  SELECT e.id,
         e.point::geography AS geo
  FROM engagement e
  WHERE e.id = :engagement_id
    AND e.point IS NOT NULL
)
SELECT tp.*
FROM trader_profile tp
JOIN trader_profile_skill tps
  ON tps.trader_profile_id = tp.id
JOIN engagement_skill es
  ON es.engagement_id = :engagement_id
 AND es.skill_id = tps.skill_id
CROSS JOIN job j
JOIN "user" u
  ON u.id = tp.owner_id
JOIN user_notification_settings uns
  ON uns.user_id = u.id
 AND uns.trader_new_matching_job_email = TRUE
WHERE tp.point IS NOT NULL
  AND tp.service_radius IS NOT NULL
  AND ST_DWithin(
        tp.point::geography,
        j.geo,
        (tp.service_radius * 1000)::int
      )
GROUP BY tp.id, j.geo
ORDER BY ST_Distance(
         tp.point::geography,
         j.geo
       ) ASC
SQL;

        // ALL skills must match
        $sqlAll = <<<SQL
WITH job AS (
  SELECT e.id,
         e.point::geography AS geo
  FROM engagement e
  WHERE e.id = :engagement_id
    AND e.point IS NOT NULL
)
SELECT tp.*
FROM trader_profile tp
JOIN engagement_skill es
  ON es.engagement_id = :engagement_id
JOIN trader_profile_skill tps
  ON tps.trader_profile_id = tp.id
 AND tps.skill_id = es.skill_id
CROSS JOIN job j
JOIN "user" u
  ON u.id = tp.owner_id
JOIN user_notification_settings uns
  ON uns.user_id = u.id
 AND uns.trader_new_matching_job_email = TRUE
WHERE tp.point IS NOT NULL
  AND tp.service_radius IS NOT NULL
  AND ST_DWithin(
        tp.point::geography,
        j.geo,
        (tp.service_radius * 1000)::int
      )
GROUP BY tp.id
HAVING COUNT(DISTINCT es.skill_id) = (
  SELECT COUNT(*) FROM engagement_skill es2 WHERE es2.engagement_id = :engagement_id
)
ORDER BY ST_Distance(
         tp.point::geography,
         j.geo
       ) ASC
SQL;

        $sql = $requireAllSkills ? $sqlAll : $sqlAny;

        $rsm = new ResultSetMappingBuilder($em);
        $rsm->addRootEntityFromClassMetadata(TraderProfile::class, 'tp');

        $q = $em->createNativeQuery($sql, $rsm)
            ->setParameter('engagement_id', $eid);

        foreach ($q->toIterable() as $profile) {
            /** @var TraderProfile $profile */
            yield $profile;
        }
    }
}
