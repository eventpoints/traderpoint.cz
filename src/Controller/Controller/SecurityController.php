<?php

namespace App\Controller\Controller;

use App\DataTransferObject\LoginFormDto;
use App\Entity\User;
use App\Enum\FlashEnum;
use App\Form\Form\LoginFormType;
use App\Form\Form\PasswordFormType;
use App\Repository\UserRepository;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
        private readonly UserRepository $userRepository,
    )
    {
    }

    #[Route(path: '/login', name: 'app_login')]
    public function login(
        AuthenticationUtils $authenticationUtils,
        #[CurrentUser]
        null|User $currentUser
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

    #[Route('/verify/email/{token}', name: 'verify_email')]
    public function verifyEmail(
        #[MapEntity(mapping: [
            'token' => 'token',
        ])]
        null|User $user = null
    ): Response
    {
        if (! $user instanceof User) {
            $this->addFlash(FlashEnum::WARNING->value, $this->translator->trans(id: 'flash.sceptical-issue', domain: 'flash'));
            return $this->redirectToRoute('app_login');
        }

        $user->setVerifiedAt(CarbonImmutable::now());
        $user->setToken(Uuid::v7());
        $this->userRepository->save(entity: $user, flush: true);
        $this->addFlash(FlashEnum::SUCCESS->value, $this->translator->trans(id: 'flash.email-address-confirmed', domain: 'flash'));

        if ($user->isTrader()) {
            return $this->redirectToRoute('trader_dashboard');
        }

        return $this->redirectToRoute('client_dashboard');
    }

    #[Route('/user/set-password', name: 'user_set_password')]
    public function setPassword(
        Request $request,
        UserPasswordHasherInterface $hasher,
        #[CurrentUser]
        User $currentUser
    ): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $passwordForm = $this->createForm(PasswordFormType::class);
        $passwordForm->handleRequest($request);

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $plain = (string) $passwordForm->get('plainPassword')->getData();
            $currentUser->setPassword($hasher->hashPassword($currentUser, $plain));

            if (method_exists($currentUser, 'setPasswordSetAt')) {
                $currentUser->setPasswordSetAt(CarbonImmutable::now());
            }

            $this->entityManager->flush();
            $this->addFlash(FlashEnum::SUCCESS->value, $this->translator->trans(id: 'flash.password-changed', domain: 'flash'));

            $target = $request->getSession()->get('post_set_password_target') ?? '/';
            return $this->redirect($target);
        }

        return $this->render('user/set_password.html.twig', [
            'passwordForm' => $passwordForm,
        ]);
    }
}
