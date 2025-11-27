<?php

declare(strict_types=1);

namespace App\Event\Listener;

use App\Entity\StripeProfile;
use App\Entity\User;
use App\Security\Accessor\TraderSubscriptionAccessor;
use App\Service\StandardPlanSubscriptionService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsEventListener(
    event: KernelEvents::REQUEST,
    method: 'onKernelRequest',
    priority: 0,
)]
final readonly class TraderSubscriptionListener
{
    public function __construct(
        private Security $security,
        private TraderSubscriptionAccessor $traderSubscriptionAccessor,
        private UrlGeneratorInterface $urlGenerator,
        private StandardPlanSubscriptionService $standardPlanSubscriptionService,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {

        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = (string) $request->attributes->get('_route');

        if ($route === '') {
            return;
        }

        $whitelistedRoutes = [
            'trader_paywall',
            'landing',
            'trader_register',
            'app_register',
            'stripe_webhook',
            'app_login',
            'app_logout',
            'trader_subscription_process_payment',
        ];

        if (in_array($route, $whitelistedRoutes, true)) {
            return;
        }

        $user = $this->security->getUser();
        if (! $user instanceof User) {
            return;
        }

        if (! $user->isTrader()) {
            return;
        }

        $stripeProfile = $user->getStripeProfile();
        if (! $stripeProfile instanceof StripeProfile) {
            $this->standardPlanSubscriptionService->startStandardPlanTrial($user);

            $stripeProfile = $user->getStripeProfile();
        }

        if ($this->traderSubscriptionAccessor->canAccess($user)) {
            return;
        }

        $reason = $this->traderSubscriptionAccessor->getDenialReason($user);

        $url = $this->urlGenerator->generate('trader_paywall', [
            'reason' => $reason,
        ]);

        $event->setResponse(new RedirectResponse($url));
    }
}
