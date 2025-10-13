<?php

namespace App\Controller\Controller;

use App\Entity\Conversation;
use App\Entity\ConversationParticipant;
use App\Entity\Message;
use App\Entity\User;
use App\Form\Form\MessageFormType;
use App\Repository\ConversationRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class ConversationController extends AbstractController
{
    public function __construct(
        private readonly ConversationRepository $conversationRepository
    )
    {
    }

    #[Route(path: '/conversation/{id}', name: 'show_conversation', methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function show(Conversation $conversation, #[CurrentUser] User $currentUser, Request $request): Response
    {
        $participant = $conversation->getParticipants()->findFirst(fn(int $key, ConversationParticipant $participant): bool => $participant->getOwner()->getId() === $currentUser->getId());

        $message = new Message(participant: $participant, conversation: $conversation);
        $messageForm = $this->createForm(MessageFormType::class, $message);

        $messageForm->handleRequest($request);

        if ($messageForm->isSubmitted() && $messageForm->isValid()) {
            $conversation->addMessage($message);
            $this->conversationRepository->save(entity: $conversation, flush: true);
            return $this->redirectToRoute('show_conversation', [
                'id' => $conversation->getId(),
            ]);
        }

        return $this->render('conversation/show.html.twig', [
            'messageForm' => $messageForm,
            'conversation' => $conversation,
        ]);
    }

    #[Route(path: '/direct/{id}/message', name: 'direct_message', methods: ['GET'])]
    public function directMessage(User $user, #[CurrentUser] User $currentUser): Response
    {
        $conversation = $this->conversationRepository->findOneByUsers($user, $currentUser);

        if ($conversation instanceof Conversation) {
            return $this->redirectToRoute('show_conversation', [
                'id' => $conversation->getId(),
            ]);
        }

        $conversation = new Conversation(owner: $currentUser);
        $conversationOwnerParticipant = new ConversationParticipant(conversation: $conversation, owner: $currentUser);
        $conversation->addParticipant($conversationOwnerParticipant);
        $conversationTargetParticipant = new ConversationParticipant(conversation: $conversation, owner: $user);
        $conversation->addParticipant($conversationTargetParticipant);
        $this->conversationRepository->save(entity: $conversation, flush: true);
        return $this->redirectToRoute('show_conversation', [
            'id' => $conversation->getId(),
        ]);
    }
}
