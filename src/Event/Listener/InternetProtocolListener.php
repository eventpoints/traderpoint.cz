<?php

declare(strict_types=1);

namespace App\Event\Listener;

use App\Entity\InternetProtocol;
use App\Entity\User;
use App\Repository\InternetProtocolRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, method: '__invoke', priority: -10)]
final readonly class InternetProtocolListener
{
    public function __construct(
        private Security                   $security,
        private InternetProtocolRepository $internetProtocolRepository,
        private EntityManagerInterface     $em,
    )
    {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        /** @var User $user */
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        $request = $event->getRequest();
        $ip = $request->getClientIp();

        if ($ip === null || $ip === '') {
            return;
        }

        $existing = $this->internetProtocolRepository->findOneBy([
            'owner' => $user,
            'address' => $ip,
        ]);

        if ($existing !== null) {
            return;
        }

        $internetProtocol = new InternetProtocol($ip);
        $internetProtocol->setOwner($user);

        $user->addInternetProtocol($internetProtocol);

        try {
            $this->em->persist($internetProtocol);
            $this->em->flush();
        } catch (UniqueConstraintViolationException) {
            // Race condition protection (two parallel requests)
            // Safe to ignore
        }
    }
}
