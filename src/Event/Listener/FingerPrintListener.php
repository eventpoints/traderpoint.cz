<?php

declare(strict_types=1);

namespace App\Event\Listener;

use App\Entity\FingerPrint;
use App\Entity\User;
use App\Repository\FingerPrintRepository;
use App\Service\FingerPrintService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, method: '__invoke', priority: -10)]
final readonly class FingerPrintListener
{
    public function __construct(
        private FingerPrintService     $fingerPrintService,
        private FingerPrintRepository  $fingerPrintRepository,
        private EntityManagerInterface $entityManager,
        private Security               $security,
    )
    {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $hash = $this->fingerPrintService->generate($request);

        if ($hash === '') {
            return;
        }

        /** @var mixed $user */
        $user = $this->security->getUser();
        $owner = $user instanceof User ? $user : null;

        $fingerPrint = $this->fingerPrintRepository->findOneBy(['fingerprint' => $hash]);

        if ($fingerPrint === null) {
            $fingerPrint = new FingerPrint($hash);

            if ($owner !== null) {
                $fingerPrint->setOwner($owner);
            }

            try {
                $this->entityManager->persist($fingerPrint);
                $this->entityManager->flush();
            } catch (UniqueConstraintViolationException) {
                $this->entityManager->clear();

                $fingerPrint = $this->fingerPrintRepository->findOneBy(['fingerprint' => $hash]);

                if ($fingerPrint !== null && $owner !== null && $fingerPrint->getOwner() === null) {
                    $fingerPrint->setOwner($owner);
                    $this->entityManager->flush();
                }
            }

            return;
        }

        // If user is signed in later, attach owner once
        if ($owner !== null && $fingerPrint->getOwner() === null) {
            $fingerPrint->setOwner($owner);
            $this->entityManager->flush();
        }
    }
}
