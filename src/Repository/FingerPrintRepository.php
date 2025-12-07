<?php

namespace App\Repository;

use App\Entity\FingerPrint;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FingerPrint>
 */
class FingerPrintRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, FingerPrint::class);
    }

    public function save(FingerPrint $fingerprint, bool $flush = false): void
    {
        $this->getEntityManager()->persist($fingerprint);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(FingerPrint $fingerprint, bool $flush = false): void
    {
        $this->getEntityManager()->remove($fingerprint);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
