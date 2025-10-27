<?php

namespace App\Repository;

use App\Entity\ExternalIdentity;
use App\Enum\OauthProviderEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExternalIdentity>
 */
final class ExternalIdentityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExternalIdentity::class);
    }

    public function findOneByProviderAndSubject(OauthProviderEnum $provider, string $subject): ?ExternalIdentity
    {
        return $this->findOneBy([
            'oauthProviderEnum' => $provider,
            'subject' => $subject,
        ]);
    }
}