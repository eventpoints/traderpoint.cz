<?php

namespace App\Controller\Controller;

use App\Entity\User;
use App\Form\Form\CardPaymentFormType;
use App\Service\StandardPlanSubscriptionService;
use Stripe\Exception\ApiErrorException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class PaywallController extends AbstractController
{
    public function __construct(
        private StandardPlanSubscriptionService $standardPlanSubscriptionService,
        #[Autowire('%env(STRIPE_PUBLIC_KEY)%')]
        private string                          $stripePublicKey,
    )
    {
    }

    #[Route('/trader/paywall', name: 'trader_paywall', methods: ['GET'])]
    public function paywall(Request $request, #[CurrentUser] ?User $user): Response
    {
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $profile = $user->getStripeProfile();
        $setupIntent = $this->standardPlanSubscriptionService->createSetupIntentFor($user);

        // Card form (CardPaymentFormType uses floating labels etc.)
        $form = $this->createForm(CardPaymentFormType::class, null, [
            'action' => $this->generateUrl('trader_subscription_process_payment'),
            'method' => 'POST',
        ]);

        // Optional reason passed from the access listener
        $reason = $request->query->get('reason');

        return $this->render('trader/paywall.html.twig', [
            'user' => $user,
            'profile' => $profile,
            'reason' => $reason,
            'cardPaymentForm' => $form->createView(),
            'stripe_public_key' => $this->stripePublicKey,
            'setup_intent_client_secret' => $setupIntent->client_secret,
        ]);
    }

    #[Route(path: '/trader/subscript/process/payment', name: 'trader_subscription_process_payment', methods: ['POST'])]
    public function subscriptionProcessPayment(
        Request $request,
        #[CurrentUser] ?User $user,
    ): Response {
        if (! $user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        // Rebuild the form to handle submission
        $form = $this->createForm(CardPaymentFormType::class);
        $form->handleRequest($request);

        if (! $form->isSubmitted() || ! $form->isValid()) {
            // Generic error → back to paywall
            $this->addFlash('danger', 'trader_paywall.error.generic');

            return $this->redirectToRoute('trader_paywall');
        }

        /** @var string|null $paymentMethodId */
        $paymentMethodId = $form->get('payment_method')->getData();

        if (! $paymentMethodId) {
            $this->addFlash('danger', 'trader_paywall.error.generic');

            return $this->redirectToRoute('trader_paywall');
        }

        try {
            // Attach PM + create/activate Standard Plan subscription
            $subscription = $this->standardPlanSubscriptionService
                ->activateStandardPlanFromPaymentMethod($user, $paymentMethodId);

            // Success – show a flash and send them somewhere useful (trader dashboard, etc.)
            $this->addFlash('success', 'trader_paywall.success.subscription_activated');

            // change this route to wherever your trader “home” is
            return $this->redirectToRoute('trader_dashboard');
        } catch (ApiErrorException $e) {
            // Stripe error – log + flash & send back to paywall
            $this->addFlash('danger', 'trader_paywall.error.generic');

            return $this->redirectToRoute('trader_paywall');
        }
    }
}
