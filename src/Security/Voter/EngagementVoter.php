<?php

namespace App\Security\Voter;

use App\Entity\Engagement;
use App\Entity\Skill;
use App\Entity\TraderProfile;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @extends Voter<'TRADER_VIEW'|'CLIENT_VIEW'|'EDIT'|'DELETE'|'APPROVE'|'REJECT'|'START_WORK'|'COMPLETE_WORK'|'RAISE_ISSUE'|'REVIEW'|'CANCEL', Engagement>
 */
final class EngagementVoter extends Voter
{
    public function __construct(
        private readonly WorkflowInterface $engagementStateMachine,
    ) {
    }
    public const TRADER_VIEW = 'TRADER_VIEW';

    public const CLIENT_VIEW = 'CLIENT_VIEW';

    public const EDIT = 'EDIT';

    public const DELETE = 'DELETE';

    // Workflow permissions
    public const APPROVE = 'APPROVE';

    public const REJECT = 'REJECT';

    public const START_WORK = 'START_WORK';

    public const COMPLETE_WORK = 'COMPLETE_WORK';

    public const RAISE_ISSUE = 'RAISE_ISSUE';

    public const REVIEW = 'REVIEW';

    public const CANCEL = 'CANCEL';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [
            self::TRADER_VIEW,
            self::CLIENT_VIEW,
            self::EDIT,
            self::DELETE,
            self::APPROVE,
            self::REJECT,
            self::START_WORK,
            self::COMPLETE_WORK,
            self::RAISE_ISSUE,
            self::REVIEW,
            self::CANCEL,
        ], true) && $subject instanceof Engagement;
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
            self::APPROVE, self::REJECT => $this->canApproveOrReject($user),
            self::START_WORK, self::COMPLETE_WORK => $this->canManageWork($engagement, $user),
            self::RAISE_ISSUE => $this->canRaiseIssue($engagement, $user),
            self::REVIEW => $this->canReview($engagement, $user),
            self::CANCEL => $this->canCancel($engagement, $user),
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

        // Check if engagement is in a state that traders can view
        // Traders should only see engagements that have been approved (not UNDER_ADMIN_REVIEW)
        $marking = $this->engagementStateMachine->getMarking($engagement);
        $allowedPlaces = ['RECEIVING_QUOTES', 'QUOTE_ACCEPTED', 'IN_PROGRESS', 'ISSUE_RESOLUTION', 'WORK_COMPLETED', 'AWAITING_REVIEW', 'REVIEWED'];

        $canViewState = false;
        foreach ($allowedPlaces as $place) {
            if ($marking->has($place)) {
                $canViewState = true;
                break;
            }
        }

        if (!$canViewState) {
            return false;
        }

        // Check if the trader has submitted a quote for this engagement
        // If they have, they should be able to view it even if their quote wasn't selected
        $hasSubmittedQuote = $engagement->getQuotes()->exists(
            static fn (int $i, $quote): bool => $quote->getOwner()->getId() === $user->getId()
        );

        if ($hasSubmittedQuote) {
            return true;
        }

        // Otherwise, check if their skills match
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

    private function canApproveOrReject(User $user): bool
    {
        return \in_array('ROLE_ADMIN', $user->getRoles(), true);
    }

    private function canManageWork(Engagement $engagement, User $user): bool
    {
        // Only the tradesman who owns the accepted quote can manage work
        $quote = $engagement->getQuote();
        if ($quote === null) {
            return false;
        }

        return $quote->getOwner()->getId() === $user->getId();
    }

    private function canRaiseIssue(Engagement $engagement, User $user): bool
    {
        // Either the engagement owner or the tradesman can raise an issue
        if ($this->isOwner($engagement, $user)) {
            return true;
        }

        $quote = $engagement->getQuote();
        if ($quote === null) {
            return false;
        }

        return $quote->getOwner()->getId() === $user->getId();
    }

    private function canReview(Engagement $engagement, User $user): bool
    {
        // Only the engagement owner can submit a review
        return $this->isOwner($engagement, $user);
    }

    private function canCancel(Engagement $engagement, User $user): bool
    {
        // Either the engagement owner or an admin can cancel
        return $this->isOwner($engagement, $user) || $this->canApproveOrReject($user);
    }
}
