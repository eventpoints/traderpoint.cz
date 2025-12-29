<?php

namespace App\Repository;

use App\Entity\Engagement;
use App\Entity\Quote;
use App\Enum\QuoteFilterEnum;
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

    /**
     * @return array<int, Quote>
     */
    public function findByEngagementAndFilter(Engagement $engagement, QuoteFilterEnum $filter = QuoteFilterEnum::ALL): array
    {
        $qb = $this->createQueryBuilder('quote');

        $qb->andWhere(
            $qb->expr()->eq('quote.engagement', ':engagement')
        )->setParameter('engagement', $engagement);

        // Filter by status
        if ($filter === QuoteFilterEnum::ACTIVE) {
            // Active quotes: submitted status AND not expired
            $qb->andWhere($qb->expr()->eq('quote.status', ':status'))
                ->setParameter('status', 'submitted')
                ->andWhere($qb->expr()->gte('quote.validUntil', ':now'))
                ->setParameter('now', new \DateTimeImmutable());
        } elseif ($filter === QuoteFilterEnum::PAST) {
            // Past quotes: rejected OR expired
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('quote.status', ':rejectedStatus'),
                    $qb->expr()->lt('quote.validUntil', ':now')
                )
            )
            ->setParameter('rejectedStatus', 'rejected')
            ->setParameter('now', new \DateTimeImmutable());
        }

        $qb->orderBy('quote.price', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
