<?php

namespace App\Repository;

use App\DataTransferObject\UserFilterDto;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (! $user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()
            ->persist($entity);

        if ($flush) {
            $this->getEntityManager()
                ->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()
            ->remove($entity);

        if ($flush) {
            $this->getEntityManager()
                ->flush();
        }
    }

    /**
     * @return array<int, User>|Query
     */
    public function findByUserFilterDto(UserFilterDto $userFilterDto, bool $isQuery = false): array|Query
    {
        $qb = $this->createQueryBuilder('user');

        $qb->leftJoin('user.skills', 'skill')
            ->leftJoin('skill.trade', 'trade')
            ->leftJoin('user.receivedReviews', 'review');

        $qb->orWhere($qb->expr()->like($qb->expr()->lower('user.name'), ':keyword'))
            ->orWhere($qb->expr()->like($qb->expr()->lower('trade.name'), ':keyword'))
            ->orWhere($qb->expr()->like($qb->expr()->lower('skill.name'), ':keyword'));

        $qb->setParameter('keyword', '%' . strtolower($userFilterDto->getKeyword()) . '%');

        if ($isQuery) {
            return $qb->getQuery();
        }

        return $qb->getQuery()->getResult();
    }

    public function findOneAverageRatingByUser(User $user): null|float
    {
        $qb = $this->createQueryBuilder('user');
        $qb->leftJoin('user.reviews', 'review')
            ->select($qb->expr()->avg('review.overallRating'))
            ->where($qb->expr()->eq('user.id', ':userId'))
            ->setParameter('userId', $user->getId(), 'uuid');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
