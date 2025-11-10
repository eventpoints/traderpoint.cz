<?php

namespace App\Repository;

use App\Entity\Engagement;
use App\Entity\Quote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Quote>
 */
class QuoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quote::class);
    }

    public function save(Quote $entity, bool $flush = false): void
    {
        $this->getEntityManager()
            ->persist($entity);

        if ($flush) {
            $this->getEntityManager()
                ->flush();
        }
    }

    public function remove(Quote $entity, bool $flush = false): void
    {
        $this->getEntityManager()
            ->remove($entity);

        if ($flush) {
            $this->getEntityManager()
                ->flush();
        }
    }

    /**
     * @return array<int, Quote>
     */
    public function findByEngagement(Engagement $engagement): array
    {
        $qb = $this->createQueryBuilder('quote');

        $qb->andWhere(
            $qb->expr()->eq('quote.engagement', ':engagement')
        )->setParameter('engagement', $engagement);

        $qb->orderBy('quote.price', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
