<?php

namespace App\Repository;

use App\Entity\TrackedLink;
use App\Entity\TrackedLinkClick;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TrackedLinkClick>
 */
class TrackedLinkClickRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrackedLinkClick::class);
    }

    public function save(TrackedLinkClick $entity, bool $flush = false): void
    {
        $this->getEntityManager()
            ->persist($entity);

        if ($flush) {
            $this->getEntityManager()
                ->flush();
        }
    }

    public function remove(TrackedLinkClick $entity, bool $flush = false): void
    {
        $this->getEntityManager()
            ->remove($entity);

        if ($flush) {
            $this->getEntityManager()
                ->flush();
        }
    }

    public function countByTrackedLink(TrackedLink $trackedLink): int
    {
        return $this->count(['trackedLink' => $trackedLink]);
    }

    /**
     * @return array<int, TrackedLinkClick>
     */
    public function findByTrackedLink(TrackedLink $trackedLink, int $limit = 100): array
    {
        return $this->createQueryBuilder('tlc')
            ->where('tlc.trackedLink = :trackedLink')
            ->setParameter('trackedLink', $trackedLink)
            ->orderBy('tlc.clickedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
