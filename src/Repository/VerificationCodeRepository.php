<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PhoneNumber;
use App\Entity\VerificationCode;
use App\Enum\VerificationPurposeEnum;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VerificationCode>
 */
final class VerificationCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VerificationCode::class);
    }

    public function expireActiveForPhone(PhoneNumber $phone, VerificationPurposeEnum $purpose): int
    {
        $qb = $this->createQueryBuilder('verification_code');
        $past = new DateTimeImmutable('-1 second');
        $now = new DateTimeImmutable();

        $qb->update(VerificationCode::class, 'verification_code');

        $qb->set('verification_code.expiresAt', ':past')
            ->set('verification_code.updatedAt', ':now')
            ->setParameter('past', $past)
            ->setParameter('now', $now);

        $qb->andWhere($qb->expr()->eq('verification_code.phoneNumber', ':phone'))
            ->setParameter('phone', $phone);

        $qb->andWhere($qb->expr()->eq('verification_code.purpose', ':purpose'))
            ->setParameter('purpose', $purpose);

        $qb->andWhere($qb->expr()->eq('verification_code.verified', ':verified'))
            ->setParameter('verified', false);

        $qb->andWhere($qb->expr()->gt('verification_code.expiresAt', ':past'));

        return $qb->getQuery()->execute();
    }

    public function findActiveByDigestForPhone(
        PhoneNumber $phone,
        VerificationPurposeEnum $purpose,
        string $digestBinary
    ): ?VerificationCode
    {
        $qb = $this->createQueryBuilder('verification_code');

        $qb->andWhere($qb->expr()->eq('verification_code.phoneNumber', ':phoneNumber'))
            ->setParameter('phoneNumber', $phone);

        $qb->andWhere($qb->expr()->eq('verification_code.purpose', ':purpose'))
            ->setParameter('purpose', $purpose);

        $qb->andWhere($qb->expr()->eq('verification_code.codeDigest', ':digest'))
            ->setParameter('digest', $digestBinary);

        $qb->andWhere($qb->expr()->eq('verification_code.verified', ':verified'))
            ->setParameter('verified', false);

        $qb->andWhere($qb->expr()->gt('verification_code.expiresAt', ':now'))
            ->setParameter('now', new DateTimeImmutable());

        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function expireActiveForEmail(string $email, VerificationPurposeEnum $purpose): int
    {
        $qb = $this->createQueryBuilder('verification_code');
        $qb->leftJoin('verification_code.owner', 'owner');
        $past = new DateTimeImmutable('-1 second');
        $now = new DateTimeImmutable();

        $qb->update(VerificationCode::class, 'verification_code');

        $qb->set('verification_code.expiresAt', ':past')
            ->set('verification_code.updatedAt', ':now')
            ->setParameter('past', $past)
            ->setParameter('now', $now);

        $qb->andWhere($qb->expr()->eq('owner.email', ':email'))
            ->setParameter('email', $email);

        $qb->andWhere($qb->expr()->eq('verification_code.purpose', ':purpose'))
            ->setParameter('purpose', $purpose);

        $qb->andWhere($qb->expr()->eq('verification_code.verified', ':verified'))
            ->setParameter('verified', false);

        $qb->andWhere($qb->expr()->gt('verification_code.expiresAt', ':past'));

        return $qb->getQuery()->execute();
    }

    public function findActiveByDigestForEmail(
        string $email,
        VerificationPurposeEnum $purpose,
        string $digestBinary
    ): ?VerificationCode
    {
        $qb = $this->createQueryBuilder('verification_code');
        $qb->leftJoin('verification_code.owner', 'owner');

        $qb->andWhere($qb->expr()->eq('owner.email', ':email'))
            ->setParameter('email', $email);

        $qb->andWhere($qb->expr()->eq('verification_code.purpose', ':purpose'))
            ->setParameter('purpose', $purpose);

        $qb->andWhere($qb->expr()->eq('verification_code.codeDigest', ':digest'))
            ->setParameter('digest', $digestBinary);

        $qb->andWhere($qb->expr()->eq('verification_code.verified', ':verified'))
            ->setParameter('verified', false);

        $qb->andWhere($qb->expr()->gt('verification_code.expiresAt', ':now'))
            ->setParameter('now', new DateTimeImmutable());

        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
