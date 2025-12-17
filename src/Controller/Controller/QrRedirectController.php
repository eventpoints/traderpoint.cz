<?php

namespace App\Controller\Controller;

use App\Entity\TrackedLink;
use App\Entity\TrackedLinkClick;
use App\Repository\TrackedLinkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class QrRedirectController extends AbstractController
{
    #[Route('/go/{code}', name: 'qr_go', methods: ['GET'])]
    public function __invoke(
        string $code,
        Request $request,
        TrackedLinkRepository $trackedLinkRepository,
        EntityManagerInterface $entityManager,
    ): RedirectResponse {
        $link = $trackedLinkRepository->findByCode($code);

        if (! $link instanceof TrackedLink) {
            return $this->redirectToRoute('landing');
        }

        // Create click tracking record
        $click = new TrackedLinkClick();
        $click->setTrackedLink($link);
        $click->setIpAddress((string) $request->getClientIp());
        $click->setUserAgent((string) $request->headers->get('user-agent', ''));
        $click->setReferer((string) $request->headers->get('referer', ''));

        // Increment the link's click count
        $link->incrementClickCount();

        $entityManager->persist($click);
        $entityManager->flush();

        // Build redirect target with UTM parameters
        $target = $link->getOriginalUrl();
        $glue = str_contains((string) $target, '?') ? '&' : '?';

        $utmParams = [
            'utm_source' => $link->getSource()?->value ?? 'qr',
            'utm_medium' => 'tracked_link',
        ];

        if ($link->getAdvertisingCampaign() instanceof \App\Entity\AdvertisingCampaign) {
            $utmParams['utm_campaign'] = $link->getAdvertisingCampaign()->getCode();
        }

        $utmParams['utm_content'] = $code;

        $target .= $glue . http_build_query($utmParams);

        $response = new RedirectResponse($target, 302);

        // Set attribution cookies
        $response->headers->setCookie(
            Cookie::create('tp_src')
                ->withValue($link->getSource()?->value ?? 'direct')
                ->withPath('/')
                ->withExpires(strtotime('+30 days'))
        );

        $response->headers->setCookie(
            Cookie::create('tp_link')
                ->withValue($code)
                ->withPath('/')
                ->withExpires(strtotime('+30 days'))
        );

        return $response;
    }
}