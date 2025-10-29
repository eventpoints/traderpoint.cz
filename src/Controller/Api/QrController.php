<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class QrController extends AbstractController
{
    #[Route(path: '/qr_redirect', name: 'qr_redirect')]
    public function index(): Response
    {
        return $this->redirectToRoute('landing');
    }
}
