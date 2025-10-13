<?php

namespace App\Controller\Controller;

use App\Entity\Quote;
use App\Repository\EngagementRepository;
use App\Repository\QuoteRepository;
use App\Security\Voter\QuoteVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: 'quote')]
class QuoteController extends AbstractController
{
    public function __construct(
        private readonly EngagementRepository $engagementRepository,
        private readonly QuoteRepository $quoteRepository
    )
    {
    }

    #[Route(path: 'accept/{id}', name: 'accept_quote')]
    #[isGranted('IS_AUTHENTICATED_FULLY')]
    public function accept(Quote $quote): Response
    {
        $this->denyAccessUnlessGranted(QuoteVoter::ACCEPT, $quote);

        $quote->getEngagement()->accept($quote);
        $this->engagementRepository->save($quote->getEngagement(), true);

        return $this->redirectToRoute('client_show_engagement', [
            'id' => $quote->getEngagement()->getId(),
        ]);
    }

    #[Route(path: 'reject/{id}', name: 'reject_quote')]
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