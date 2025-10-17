<?php

namespace App\Controller\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class AppController extends AbstractController
{
    #[Route(path: '/', name: 'landing')]
    public function landing(Request $request, #[CurrentUser] null|User $currentUser): Response
    {
        if ($currentUser instanceof User) {
            if ($currentUser->isTrader()) {
                return $this->redirectToRoute('trader_dashboard');
            }
            return $this->redirectToRoute('client_dashboard');
        }

        return $this->render('app/landing.html.twig');
    }

    #[Route(path: '/terms-of-use', name: 'terms_of_use')]
    public function termsOfUse(): Response
    {
        return $this->render('app/terms-of-use.html.twig');
    }
}