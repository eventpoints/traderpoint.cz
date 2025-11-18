<?php
declare(strict_types=1);

namespace App\Event\Listener;

use App\Entity\User;
use App\Security\Accessor\TraderSubscriptionAccessor;
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
final class TraderSubscriptionListener
{
    public function __construct(
        private Security                   $security,
        private TraderSubscriptionAccessor $traderSubscriptionAccessor,
        private UrlGeneratorInterface      $urlGenerator,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route   = (string) $request->attributes->get('_route');

        // If no route (e.g. static assets handled elsewhere), bail out
        if ($route === '') {
            return;
        }

        // Routes that must remain accessible even when blocked,
        // otherwise you get redirect loops or break webhooks.
        $whitelistedRoutes = [
            'trader_paywall',
            'stripe_webhook',
            'app_login',
            'app_logout',
            // add register/forgot-password/etc if needed
        ];

        if (in_array($route, $whitelistedRoutes, true)) {
            return;
        }

        // Only care about logged-in users
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }


        // Optional: if you really want to block *only* traders, keep this.
        // If you literally want "any logged-in user must have a valid subscription",
        // then remove this block.
        if (!$user->isTrader()) {
            return;
        }

        // Subscription / trial check
        if ($this->traderSubscriptionAccessor->canAccess($user)) {
            return; // OK, let them through
        }

        // Denied → redirect to paywall
        $reason = $this->traderSubscriptionAccessor->getDenialReason($user);

        $url = $this->urlGenerator->generate('trader_paywall', [
            'reason' => $reason,
        ]);

        $event->setResponse(new RedirectResponse($url));
    }
}

