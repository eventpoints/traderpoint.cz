<?php

namespace App\Controller\Stripe;

use App\Entity\Payment;
use App\Entity\User;
use App\Enum\FlashEnum;
use App\Repository\PaymentRepository;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Contracts\Translation\TranslatorInterface;

class StripePaymentController extends AbstractController
{
    public function __construct(
        private readonly StripeClient $stripe,
        private readonly PaymentRepository $paymentRepository,
        private readonly TranslatorInterface $translator,
    )
    {
    }

    #[Route('/payment/check', name: 'check_payment', methods: ['GET'])]
    public function check(
        Request $request,
        #[CurrentUser]
        ?User $user,
    ): Response
    {
        $sessionId = (string) $request->query->get('session_id', '');
        $result = (string) $request->query->get('result', 'unknown');

        if ($sessionId === '' || $sessionId === '0' ) {
            $this->addFlash('error', 'Can’t find that payment.');
            return $this->redirectToRoute('app_login');
        }

        $payment = $this->paymentRepository->findOneByCheckoutId($sessionId);
        if (! $payment instanceof Payment) {
            $this->addFlash('error', 'Unknown or expired payment session.');
            return $this->redirectToRoute('app_login');
        }

        if (! $user || $payment->getOwner()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        try {
            $session = $this->stripe->checkout->sessions->retrieve($sessionId, []);
        } catch (\Throwable) {
            $this->addFlash('error', 'Could not verify payment status.');
            return $this->redirectToRoute($user->isTrader() ? 'trader_dashboard' : 'client_dashboard');
        }

        // Extra paranoia: ensure session ties back to the same Payment
        $metaPaymentId = $session->metadata['payment_id'] ?? null;
        if ($metaPaymentId && (string) $payment->getId() !== (string) $metaPaymentId) {
            $this->addFlash('error', 'Payment session mismatch.');
            return $this->redirectToRoute($user->isTrader() ? 'trader_dashboard' : 'client_dashboard');
        }

        $status = ($session->status ?? '');
        $paymentStatus = ($session->payment_status ?? '');

        if ($paymentStatus === 'paid') {
            $this->addFlash(FlashEnum::SUCCESS->value, $this->translator->trans(id: 'flash.payment.success', domain: 'flash'));
        } elseif ($status === 'expired') {
            $this->addFlash(FlashEnum::ERROR->value, $this->translator->trans(id: 'flash.payment.expired', domain: 'flash'));
        } elseif ($result === 'cancel') {
            $this->addFlash(FlashEnum::ERROR->value, $this->translator->trans(id: 'flash.payment.failed', domain: 'flash'));
        } else {
            $this->addFlash(FlashEnum::WARNING->value, $this->translator->trans(id: 'flash.payment.pending', domain: 'flash'));
        }

        return $this->redirectToRoute($user->isTrader() ? 'trader_dashboard' : 'client_dashboard');
    }
}