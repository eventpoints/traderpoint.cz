<?php

declare(strict_types=1);

namespace App\Twig\Component;

use App\Entity\Engagement;
use App\Entity\User;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('engagement_viewers')]
final class EngagementViewersComponent
{
    use DefaultActionTrait;

    #[LiveProp]
    public Engagement $engagement;

    public function __construct(
        private CacheItemPoolInterface $cache,
        private Security $security,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * Called on every render; we use it to update presence + return count.
     */
    public function getCount(): int
    {
        return $this->updatePresenceAndGetCount();
    }

    private function updatePresenceAndGetCount(): int
    {
        $engagementId = $this->engagement->getId()?->toRfc4122() ?? (string) $this->engagement->getId();
        $key = sprintf('engagement_viewers_%s', $engagementId);

        $item = $this->cache->getItem($key);
        $viewers = $item->isHit() ? (array) $item->get() : [];

        // Identify this viewer: prefer user id, fall back to session id
        $user = $this->security->getUser();
        if ($user instanceof User) {
            $identifier = 'u_' . $user->getId()->toRfc4122();
        } else {
            $session = $this->requestStack->getSession();
            $identifier = 's_' . $session->getId();
        }

        $now = time();
        $cutoff = $now - 30; // “currently viewing” = seen within last 30s

        // Update this viewer
        $viewers[$identifier] = $now;

        // Drop stale viewers
        $viewers = array_filter(
            $viewers,
            static fn (int $timestamp): bool => $timestamp >= $cutoff
        );

        $item->set($viewers);
        // little buffer so key doesn’t live forever
        $item->expiresAfter(60);
        $this->cache->save($item);

        return count($viewers);
    }
}
