<?php

// src/EventListener/RequirePhoneListener.php
declare(strict_types=1);

namespace App\Event\Subscriber;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsEventListener(event: KernelEvents::CONTROLLER, priority: 0)]
final readonly class RequirePhoneSubscriber
{
    public function __construct(
        private Security $security,
        private UrlGeneratorInterface $urls,
    ) {}

    public function __invoke(ControllerEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = (string) $request->attributes->get('_route', '');

        // skip public/dev/api routes to avoid loops or breaking APIs
        if (
            $route === '' ||
            \in_array($route, ['app_login', 'app_logout', 'app_register', 'create_phone_number', 'phone_verification'], true) ||
            str_starts_with($route, '_profiler') ||
            str_starts_with($route, '_wdt') ||
            str_starts_with($route, '_errors') ||
            str_starts_with($route, 'ux_') ||
            str_starts_with($route, 'webpack_') ||
            str_starts_with($route, 'api_')
        ) {
            return;
        }

        /** @var User|null $user */
        $user = $this->security->getUser();
        if (! $user instanceof User) {
            return; // only enforce for authenticated users
        }

        $phone = $user->getPhoneNumber();

        // no phone → redirect to create
        if (! $phone instanceof \App\Entity\PhoneNumber) {
            $event->setController(fn (): \Symfony\Component\HttpFoundation\RedirectResponse => new RedirectResponse(
                $this->urls->generate('create_phone_number', [
                    'return' => $request->getRequestUri(),
                ]),
                $request->isMethodCacheable() ? 302 : 303
            ));
            return;
        }

        // unverified phone → redirect to verify (remove if you only require presence)
        if (! $phone->getConfirmedAt() instanceof \Carbon\CarbonImmutable) {
            $event->setController(fn (): \Symfony\Component\HttpFoundation\RedirectResponse => new RedirectResponse(
                $this->urls->generate('phone_verification', [
                    'return' => $request->getRequestUri(),
                ]),
                $request->isMethodCacheable() ? 302 : 303
            ));
            return;
        }
    }
}
