<?php

namespace App\Controller\Controller;

use App\DataTransferObject\LoginFormDto;
use App\Entity\User;
use App\Form\Form\LoginFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(
        AuthenticationUtils      $authenticationUtils,
        #[CurrentUser] null|User $currentUser
    ): Response
    {
        if ($currentUser instanceof User) {
            return $this->redirectToRoute($currentUser->isTrader() ? 'trader_dashboard' : 'client_dashboard');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        $loginFormDto = new LoginFormDto($lastUsername);
        $form = $this->createForm(
            LoginFormType::class,
            $loginFormDto
        );

        return $this->render('security/login.html.twig', [
            'form' => $form->createView(),
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
