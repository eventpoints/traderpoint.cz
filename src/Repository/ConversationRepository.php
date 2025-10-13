<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conversation>
 */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    public function save(Conversation $entity, bool $flush = false): void
    {
        $this->getEntityManager()
            ->persist($entity);

        if ($flush) {
            $this->getEntityManager()
                ->flush();
        }
    }

    public function remove(Conversation $entity, bool $flush = false): void
    {
        $this->getEntityManager()
            ->remove($entity);

        if ($flush) {
            $this->getEntityManager()
                ->flush();
        }
    }

    public function findOneByUsers(User $targetUser, User $currentUser): null|Conversation
    {
        $qb = $this->createQueryBuilder('conversation');

        // Join participants of the conversation
        $qb->leftJoin('conversation.participants', 'participant');

        // Check for the target user and current user in the participants
        $qb->andWhere(
            $qb->expr()->in('participant.owner', ':users')
        )
            ->setParameter('users', [$targetUser, $currentUser])
            ->groupBy('conversation.id')
            ->having('COUNT(participant.owner) = 2');

        return $qb->getQuery()->getOneOrNullResult();
    }
}
