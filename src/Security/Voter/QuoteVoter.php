<?php

namespace App\Security\Voter;

use App\Entity\Quote;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<'ACCEPT'|'REJECT'|'WITHDRAW', Quote>
 */
final class QuoteVoter extends Voter
{
    public const ACCEPT = 'ACCEPT';

    public const REJECT = 'REJECT';

    public const WITHDRAW = 'WITHDRAW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::ACCEPT, self::REJECT, self::WITHDRAW])
            && $subject instanceof Quote;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (! $user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::ACCEPT, self::REJECT => $this->canUserAcceptOrReject($subject, $user),
            self::WITHDRAW => $this->canUserWithdraw($subject, $user),
        };

    }

    private function canUserAcceptOrReject(Quote $quote, User $user): bool
    {
        return $quote->getEngagement()->getOwner() === $user;
    }

    private function canUserWithdraw(Quote $quote, User $user): bool
    {
        return $quote->getOwner() === $user;
    }
}
