<?php

namespace App\Repository;

use App\Entity\TrackedLink;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TrackedLink>
 */
class TrackedLinkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrackedLink::class);
    }

    public function findByCode(string $code): ?TrackedLink
    {
        return $this->findOneBy([
            'code' => $code,
            'isActive' => true,
        ]);
    }

    public function save(TrackedLink $entity, bool $flush = false): void
    {
        $this->getEntityManager()
            ->persist($entity);

        if ($flush) {
            $this->getEntityManager()
                ->flush();
        }
    }

    public function remove(TrackedLink $entity, bool $flush = false): void
    {
        $this->getEntityManager()
            ->remove($entity);

        if ($flush) {
            $this->getEntityManager()
                ->flush();
        }
    }
}
