<?php

namespace App\Repository;

use App\Entity\Partner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Partner>
 */
class PartnerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Partner::class);
    }

    public function findOneBySlug(string $slug): null|Partner
    {
        $qb = $this->createQueryBuilder('p');

        $qb->andWhere(
            $qb->expr()->eq('p.slug', ':slug')
        )->setParameter('slug', $slug);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
