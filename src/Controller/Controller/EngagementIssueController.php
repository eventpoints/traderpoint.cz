<?php

declare(strict_types=1);

namespace App\Controller\Controller;

use App\Entity\Engagement;
use App\Entity\EngagementIssue;
use App\Entity\User;
use App\Enum\FlashEnum;
use App\Repository\EngagementIssueRepository;
use App\Service\IssueMediationAIService;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/engagement-issue')]
#[IsGranted('ROLE_USER')]
class EngagementIssueController extends AbstractController
{
    public function __construct(
        private readonly EngagementIssueRepository $issueRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
        private readonly IssueMediationAIService $mediationService,
    ) {
    }

    #[Route('/{id}', name: 'engagement_issue_show', methods: ['GET', 'POST'])]
    public function show(
        EngagementIssue $issue,
        Request $request,
        #[CurrentUser] User $currentUser
    ): Response {
        $engagement = $issue->getEngagement();

        // Check user has access (either engagement owner or quote owner)
        $isClient = $engagement->getOwner()->getId() === $currentUser->getId();
        $isTrader = $engagement->getQuote()?->getOwner()->getId() === $currentUser->getId();

        if (!$isClient && !$isTrader) {
            throw $this->createAccessDeniedException('You do not have access to this issue');
        }

        // Handle evidence submission
        if ($request->isMethod('POST')) {
            $evidence = $request->request->get('evidence');

            if (empty($evidence)) {
                $this->addFlash(
                    FlashEnum::ERROR->value,
                    $this->translator->trans('issue.evidence_required')
                );
            } else {
                if ($isClient) {
                    if ($issue->hasClientSubmitted()) {
                        $this->addFlash(
                            FlashEnum::ERROR->value,
                            $this->translator->trans('issue.already_submitted')
                        );
                    } else {
                        $issue->setClientEvidence($evidence);
                        $issue->setClientSubmittedAt(new CarbonImmutable());

                        $this->addFlash(
                            FlashEnum::SUCCESS->value,
                            $this->translator->trans('issue.evidence_submitted')
                        );
                    }
                } elseif ($isTrader) {
                    if ($issue->hasTraderSubmitted()) {
                        $this->addFlash(
                            FlashEnum::ERROR->value,
                            $this->translator->trans('issue.already_submitted')
                        );
                    } else {
                        $issue->setTraderEvidence($evidence);
                        $issue->setTraderSubmittedAt(new CarbonImmutable());

                        $this->addFlash(
                            FlashEnum::SUCCESS->value,
                            $this->translator->trans('issue.evidence_submitted')
                        );
                    }
                }

                $this->entityManager->flush();

                // If both parties submitted, trigger AI mediation
                if ($issue->bothPartiesSubmitted() && !$issue->getAiMediation()) {
                    $this->triggerAiMediation($issue);
                }

                return $this->redirectToRoute('engagement_issue_show', ['id' => $issue->getId()]);
            }
        }

        return $this->render('engagement_issue/show.html.twig', [
            'issue' => $issue,
            'engagement' => $engagement,
            'isClient' => $isClient,
            'isTrader' => $isTrader,
            'canSubmit' => ($isClient && !$issue->hasClientSubmitted()) || ($isTrader && !$issue->hasTraderSubmitted()),
            'hasSubmitted' => ($isClient && $issue->hasClientSubmitted()) || ($isTrader && $issue->hasTraderSubmitted()),
        ]);
    }

    private function triggerAiMediation(EngagementIssue $issue): void
    {
        // Use AI to mediate the dispute
        $mediation = $this->mediationService->mediateIssue($issue);
        $issue->setAiMediation($mediation);
        $this->entityManager->flush();
    }
}
