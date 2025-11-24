<?php

namespace App\Controller\Controller;

use App\Entity\User;
use App\Form\Form\CardPaymentFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class PaywallController extends AbstractController
{
    #[Route('/trader/paywall', name: 'trader_paywall')]
    public function __invoke(
        #[CurrentUser]
        ?User $user,
    ): Response {

        $cardPaymentForm = $this->createForm(CardPaymentFormType::class);

        // You can use $user?->getStripeProfile() to customise messaging
        return $this->render('trader/paywall.html.twig', [
            'cardPaymentForm' => $cardPaymentForm,
            'user' => $user,
            'profile' => $user?->getStripeProfile(),
        ]);
    }
}