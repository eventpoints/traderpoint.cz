<?php

namespace App\Security\Voter;

use App\Entity\Engagement;
use App\Entity\Skill;
use App\Entity\TraderProfile;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<'TRADER_VIEW'|'CLIENT_VIEW'|'EDIT'|'DELETE', Engagement>
 */
final class EngagementVoter extends Voter
{
    public const TRADER_VIEW = 'TRADER_VIEW';

    public const CLIENT_VIEW = 'CLIENT_VIEW';

    public const EDIT = 'EDIT';

    public const DELETE = 'DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::TRADER_VIEW, self::CLIENT_VIEW, self::EDIT, self::DELETE], true)
            && $subject instanceof Engagement;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (! $user instanceof User) {
            return false;
        }

        /** @var Engagement $engagement */
        $engagement = $subject;

        return match ($attribute) {
            self::TRADER_VIEW => $this->canTraderView($engagement, $user),
            self::CLIENT_VIEW => $this->canClientView($engagement, $user),
            self::EDIT, self::DELETE => $this->isOwner($engagement, $user),
            default => false,
        };
    }

    private function canTraderView(Engagement $engagement, User $user): bool
    {
        if (! $user->isTrader()) {
            return false;
        }

        $profile = $user->getTraderProfile();
        if (! $profile instanceof TraderProfile) {
            return false;
        }

        $traderSkills = $profile->getSkills();
        if ($traderSkills->isEmpty() || $engagement->getSkills()->isEmpty()) {
            return false;
        }

        return $engagement->getSkills()->exists(
            static fn (int $i, Skill $s): bool => $traderSkills->contains($s)
        );
    }

    private function canClientView(Engagement $engagement, User $user): bool
    {
        return $this->isOwner($engagement, $user);
    }

    private function isOwner(Engagement $engagement, User $user): bool
    {
        return $engagement->getOwner()?->getId() === $user->getId();
    }
}
