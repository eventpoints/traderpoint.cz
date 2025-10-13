<?php
declare(strict_types=1);

namespace App\Controller\Controller;

use App\Enum\PaymentStatusEnum;
use App\Repository\PaymentRepository;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Webhook as StripeWebhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class StripeWebhookController extends AbstractController
{
    public function __construct(
        private readonly PaymentRepository $payments,
        private readonly LoggerInterface   $logger,
        private readonly string            $webHookSecret,
    )
    {
    }

    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = (string)$request->headers->get('stripe-signature', '');

        try {
            $event = StripeWebhook::constructEvent($payload, $sigHeader, $this->webHookSecret);
        } catch (\Throwable $e) {
            $this->logger->warning('Stripe webhook signature failed', ['error' => $e->getMessage()]);
            return new Response('bad signature', 400);
        }

        // Use match + IIFE closures for side-effects
        (match ($event->type) {
            'checkout.session.completed',
            'checkout.session.async_payment_succeeded' =>
            function () use ($event): void {
                /** @var StripeSession $session */
                $session = $event->data->object;
                $this->markPaidBySessionId($session->id, $session->payment_intent ?? null);
            },

            'checkout.session.expired' =>
            function () use ($event): void {
                $this->markStatus((string)$event->data->object->id, PaymentStatusEnum::EXPIRED);
            },

            'checkout.session.async_payment_failed' =>
            function () use ($event): void {
                $this->markStatus((string)$event->data->object->id, PaymentStatusEnum::FAILED);
            },

            // Optional: also accept PI events as a belt-and-braces fallback
            'payment_intent.succeeded' =>
            function () use ($event): void {
                $piId = (string)$event->data->object->id;
                $this->markPaidByPaymentIntentId($piId);
            },

            default =>
            function (): void {
                // ignore others
            },
        })();

        return new Response('ok');
    }

    private function markPaidBySessionId(string $sessionId, ?string $paymentIntentId): void
    {
        $payment = $this->payments->findOneByCheckoutId($sessionId);
        if (!$payment) {
            $this->logger->warning('Payment not found for session', ['session_id' => $sessionId]);
            return;
        }

        $payment->setStatus(PaymentStatusEnum::PAID);
        if ($paymentIntentId) {
            $payment->setStripePaymentIntentId($paymentIntentId);
        }
        $this->payments->save($payment, true);
    }

    private function markPaidByPaymentIntentId(string $paymentIntentId): void
    {
        // Implement this if you store PI ids (e.g., add a repository finder).
        // $payment = $this->payments->findOneByPaymentIntentId($paymentIntentId);
        // if ($payment) { $payment->setStatus(PaymentStatusEnum::PAID); $this->payments->save($payment, true); }
    }

    private function markStatus(string $sessionId, PaymentStatusEnum $status): void
    {
        $payment = $this->payments->findOneByCheckoutId($sessionId);
        if (!$payment) {
            return;
        }
        $payment->setStatus($status);
        $this->payments->save($payment, true);
    }
}
