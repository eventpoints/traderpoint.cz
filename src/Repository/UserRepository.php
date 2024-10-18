<?php

namespace App\Repository;

use App\DataTransferObject\UserAvgRatingDto;
use App\DataTransferObject\UserFilterDto;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }


    public function findByUserFilterDto(UserFilterDto $userFilterDto): array
    {
        $qb = $this->createQueryBuilder('user');

        $qb->leftJoin('user.skills', 'user_skill')
            ->leftJoin('user_skill.skill', 'skill')
            ->leftJoin('skill.trade', 'trade')
            ->leftJoin('user.reviews', 'review');

        $qb->orWhere($qb->expr()->like($qb->expr()->lower('user.name'), ':keyword'));
        $qb->orWhere($qb->expr()->like($qb->expr()->lower('trade.name'), ':keyword'));
        $qb->orWhere($qb->expr()->like($qb->expr()->lower('skill.name'), ':keyword'));

        $qb->setParameter('keyword', '%' . strtolower($userFilterDto->getKeyword()) . '%');

        $qb->addSelect('user', $qb->expr()->avg('review.overallRating') . ' AS averageOverallRating');

        $qb->groupBy('user.id');

        $output = [];
        foreach ($qb->getQuery()->getResult() as $result) {
            $output[] = new UserAvgRatingDto(user: $result[0], averageRating: $result['averageOverallRating']);
        }

        return $output;
    }

    public function findOneAverageRatingByUser(User $user)
    {
        $qb = $this->createQueryBuilder('user');
        $qb->leftJoin('user.reviews', 'review')
            ->select($qb->expr()->avg('review.overallRating'))
            ->where($qb->expr()->eq('user.id', ':userId'))
            ->setParameter('userId', $user->getId(), 'uuid');

        return $qb->getQuery()->getSingleScalarResult();
    }


}
