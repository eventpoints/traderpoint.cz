<?php

namespace App\Controller\Controller;

use App\Entity\Quote;
use App\Enum\FlashEnum;
use App\Repository\EngagementRepository;
use App\Repository\QuoteRepository;
use App\Security\Voter\QuoteVoter;
use App\Service\EngagementWorkflowService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: 'quote')]
class QuoteController extends AbstractController
{
    public function __construct(
        private readonly EngagementRepository $engagementRepository,
        private readonly QuoteRepository $quoteRepository,
        private readonly EngagementWorkflowService $workflowService,
        private readonly TranslatorInterface $translator,
    )
    {
    }

    #[Route(path: '/accept/{id}', name: 'accept_quote')]
    #[isGranted('IS_AUTHENTICATED_FULLY')]
    public function accept(Quote $quote): Response
    {
        $this->denyAccessUnlessGranted(QuoteVoter::ACCEPT, $quote);

        $engagement = $quote->getEngagement();

        try {
            // Use workflow service to accept quote
            $this->workflowService->acceptQuote($engagement, $quote);

            $this->addFlash(
                FlashEnum::SUCCESS->value,
                $this->translator->trans('quote.accepted_successfully')
            );
        } catch (\LogicException $e) {
            $this->addFlash(
                FlashEnum::ERROR->value,
                $this->translator->trans('quote.cannot_accept', ['error' => $e->getMessage()])
            );
        }

        return $this->redirectToRoute('client_show_engagement', [
            'id' => $engagement->getId(),
        ]);
    }

    #[Route(path: '/reject/{id}', name: 'reject_quote')]
    public function decline(Quote $quote): Response
    {
        $this->denyAccessUnlessGranted(QuoteVoter::REJECT, $quote);

        $quote->reject();
        $this->quoteRepository->save($quote, true);

        return $this->redirectToRoute('client_show_engagement', [
            'id' => $quote->getEngagement()->getId(),
        ]);
    }
}