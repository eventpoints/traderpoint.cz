<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Conversation;
use App\Entity\ConversationParticipant;
use App\Entity\Engagement;
use App\Entity\User;
use App\Enum\ConversationTypeEnum;

final class ConversationFactory
{
    /**
     * Get (or create) a conversation for this engagement + a participant for this user.
     *
     * @return array{0: Conversation, 1: ConversationParticipant}
     */
    public function getOrCreateForEngagement(
        Engagement $engagement,
        User $currentUser,
        ConversationTypeEnum $type = ConversationTypeEnum::DIRECT, // or JOB_PUBLIC if you add that
    ): array {
        // 1) Conversation for this engagement
        $conversation = $engagement->getConversation();

        if (! $conversation instanceof Conversation) {
            // Use the engagement owner as the "owner" of the conversation
            $conversation = new Conversation($engagement->getOwner());
            $conversation->setType($type);
            $conversation->setEngagement($engagement);
            $engagement->setConversation($conversation);
        }

        // 2) Participant for this user (reuse if exists)
        $participant = $this->getOrCreateParticipantForUser($conversation, $currentUser);

        return [$conversation, $participant];
    }

    private function getOrCreateParticipantForUser(
        Conversation $conversation,
        User $user,
    ): ConversationParticipant {
        foreach ($conversation->getParticipants() as $participant) {
            if ($participant->getOwner() === $user) {
                return $participant;
            }
        }

        // No participant yet for this user → create and attach
        $participant = new ConversationParticipant($conversation, $user);
        $conversation->addParticipant($participant);

        return $participant;
    }
}
