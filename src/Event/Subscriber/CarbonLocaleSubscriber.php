<?php
declare(strict_types=1);

namespace App\Event\Subscriber;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class CarbonLocaleSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => 'onKernelRequest'];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $locale = $event->getRequest()->getLocale();
        Carbon::setLocale($locale);
        CarbonImmutable::setLocale($locale);
    }
}
