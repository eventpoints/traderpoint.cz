<?php

namespace App\Repository;

use App\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    public function save(Review $entity, bool $flush = false): void
    {
        $this->getEntityManager()
            ->persist($entity);

        if ($flush) {
            $this->getEntityManager()
                ->flush();
        }
    }

    public function remove(Review $entity, bool $flush = false): void
    {
        $this->getEntityManager()
            ->remove($entity);

        if ($flush) {
            $this->getEntityManager()
                ->flush();
        }
    }

    public function getAverageRatingByUser(Uuid $userId): float
    {
        $qb = $this->createQueryBuilder('rating');
        $qb->select('AVG(rating.overallRating) as averageRating');

        $qb->andWhere(
            $qb->expr()->eq(x: 'rating.owner', y: ':userId')
        )->setParameter(key: 'userId', value: $userId, type: 'uuid');
        $qb->groupBy('rating.owner');

        return round($qb->getQuery()->getSingleScalarResult(), 2);
    }

    /**
     * @return array<int, Review>|Query
     */
    public function findByReviewee(Uuid $userId, bool $isQuery = false) : array|Query
    {
        $qb = $this->createQueryBuilder('review');
        $qb->andWhere(
            $qb->expr()->eq('review.reviewee', ':revieweeId')
        )->setParameter('revieweeId', $userId, 'uuid');

        $qb->orderBy('review.createdAt', Order::Descending->value);

        if ($isQuery) {
            return $qb->getQuery();
        }

        return $qb->getQuery()->getResult();
    }

}
