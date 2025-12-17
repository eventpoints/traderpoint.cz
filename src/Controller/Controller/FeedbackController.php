<?php

namespace App\Controller\Controller;

use App\Entity\Feedback;
use App\Entity\User;
use App\Enum\FlashEnum;
use App\Form\Form\FeedbackFormType;
use App\Repository\FeedbackRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Contracts\Translation\TranslatorInterface;

class FeedbackController extends AbstractController
{
    public function __construct(
        private readonly FeedbackRepository $feedbackRepository,
        private readonly TranslatorInterface $translator,
    )
    {
    }

    #[Route(path: '/feedback', name: 'feedback_submit')]
    public function submit(Request $request, #[CurrentUser] ?User $currentUser): Response
    {
        $feedback = new Feedback();

        if ($currentUser instanceof User) {
            $feedback->setOwner($currentUser);
        }

        $referer = $request->headers->get('referer');
        if ($referer !== null) {
            $feedback->setPage($referer);
        }

        $form = $this->createForm(FeedbackFormType::class, $feedback);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->feedbackRepository->save(entity: $feedback, flush: true);
            $this->addFlash(FlashEnum::SUCCESS->value, $this->translator->trans('feedback.submitted', [], 'messages'));

            return $this->redirectToRoute('feedback_submit');
        }

        return $this->render('feedback/submit.html.twig', [
            'feedbackForm' => $form,
        ]);
    }
}
