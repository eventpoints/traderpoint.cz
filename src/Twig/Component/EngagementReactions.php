<?php

declare(strict_types=1);

namespace App\Twig\Component;

use App\Entity\Engagement;
use App\Entity\EngagementReaction;
use App\Entity\Reaction;
use App\Entity\User;
use App\Repository\EngagementReactionRepository;
use App\Repository\ReactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: 'engagement_reactions',
    template: 'components/engagement_reactions.html.twig'
)]
final class EngagementReactions
{
    use DefaultActionTrait;

    #[LiveProp]
    public Engagement $engagement;

    public function __construct(
        private ReactionRepository $reactionRepository,
        private EngagementReactionRepository $engagementReactionRepository,
        private EntityManagerInterface $em,
        private Security $security,
    )
    {
    }

    /**
     * @return Reaction[]
     */
    public function getReactions(): array
    {
        return $this->reactionRepository->createQueryBuilder('r')
            ->andWhere('r.active = :active')
            ->setParameter('active', true)
            ->orderBy('r.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Exposed in Twig as: this.counts
     *
     * @return array<string,int> [code => count]
     */
    public function getCounts(): array
    {
        $qb = $this->engagementReactionRepository->createQueryBuilder('er')
            ->select('IDENTITY(er.reaction) AS reaction_id, COUNT(er.id) AS cnt')
            ->andWhere('er.engagement = :engagement')
            ->setParameter('engagement', $this->engagement)
            ->groupBy('er.reaction');

        $rows = $qb->getQuery()->getArrayResult();

        // Map by id so we can convert to codes
        $byId = [];
        foreach ($this->getReactions() as $reaction) {
            $byId[$reaction->getId()->toRfc4122()] = $reaction;
        }

        $counts = [];
        foreach ($rows as $row) {
            $reaction = $byId[$row['reaction_id']] ?? null;
            if ($reaction instanceof Reaction) {
                $counts[$reaction->getCode()] = (int) $row['cnt'];
            }
        }

        return $counts;
    }

    /**
     * Exposed in Twig as: this.userReactionCodes
     *
     * @return string[]
     */
    public function getUserReactionCodes(): array
    {
        $user = $this->getUser();
        if (! $user instanceof User) {
            return [];
        }

        $reactions = $this->engagementReactionRepository->findBy([
            'engagement' => $this->engagement,
            'user' => $user,
        ]);

        $codes = [];
        foreach ($reactions as $engagementReaction) {
            $codes[] = $engagementReaction->getReaction()->getCode();
        }

        return $codes;
    }

    #[LiveAction]
    public function toggle(#[LiveArg] string $code): void
    {
        $user = $this->getUser();
        if (! $user instanceof User) {
            return;
        }

        $reaction = $this->reactionRepository->findOneBy([
            'code' => $code,
            'active' => true,
        ]);
        if (! $reaction instanceof Reaction) {
            return;
        }

        $user = $this->em->getReference(User::class, $user->getId());

        $existing = $this->engagementReactionRepository->findOneBy([
            'engagement' => $this->engagement,
            'user' => $user,
            'reaction' => $reaction,
        ]);

        if ($existing !== null) {
            $this->engagement->removeReaction($existing);
            $user->removeReaction($existing);
            $this->em->remove($existing);
        } else {
            $engagementReaction = new EngagementReaction(engagement: $this->engagement, user: $user, reaction: $reaction);
            $this->engagement->addReaction($engagementReaction);
            $user->addReaction($engagementReaction);
            $this->em->persist($engagementReaction);
        }

        $this->em->flush();
    }

    private function getUser(): ?User
    {
        $user = $this->security->getUser();
        return $user instanceof User ? $user : null;
    }
}
