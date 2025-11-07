<?php

declare(strict_types=1);

namespace App\Event\Subscriber;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

#[AsEventListener(event: KernelEvents::REQUEST, method: 'onKernelRequest', priority: 0)]
final readonly class ForcePasswordSetListener
{
    public function __construct(
        private Security        $security,
        private RouterInterface $router,
    )
    {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return; // not logged in
        }

        if ($user->hasPassword()) {
            return; // password already set, all good
        }

        $route = $request->attributes->get('_route');
        $path = $request->getPathInfo();

        // Routes allowed while password is missing
        $whitelist = [
            'user_set_password',
            'app_logout',
            'app_login',
            'oauth_google_check',
            'oauth_facebook_check',
        ];

        if (\in_array($route, $whitelist, true)) {
            return;
        }

        // Ignore dev/profiler/asset/API/AJAX/non-HTML noise
        if (
            str_starts_with($path, '/_wdt')
            || str_starts_with($path, '/_profiler')
            || str_starts_with($path, '/build/')
            || str_starts_with($path, '/assets/')
            || $request->isXmlHttpRequest()
            || $request->getPreferredFormat() === 'json'
        ) {
            return;
        }

        // Remember where to go back to after setting password
        if ($request->hasSession()) {
            $request->getSession()->set(
                'post_set_password_target',
                $request->getRequestUri()
            );
        }

        $event->setResponse(
            new RedirectResponse($this->router->generate('user_set_password'))
        );
    }
}
