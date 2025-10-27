<?php

declare(strict_types=1);

namespace App\Event\Subscriber;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

final readonly class ForcePasswordSetOnLoginSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RouterInterface $router
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => ['onLoginSuccess', 0],
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (! $user instanceof User) {
            return;
        }

        if ($user->hasPassword()) {
            return;
        }

        $request = $event->getRequest();

        // Avoid loops / non-HTML contexts
        if ($request->attributes->get('_route') === 'account_set_password') {
            return;
        }
        if ($request->isXmlHttpRequest() || $request->getPreferredFormat() === 'json') {
            return;
        }

        // Remember where to go back to after setting the password
        if ($request->hasSession()) {
            $request->getSession()->set(
                'post_set_password_target',
                $request->query->get('_target_path') ?? $request->headers->get('referer') ?? '/'
            );
        }

        $event->setResponse(new RedirectResponse(
            $this->router->generate('user_set_password')
        ));
    }
}
