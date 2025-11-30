<?php

namespace App\Controller\Controller;

use App\DataTransferObject\LoginFormDto;
use App\DataTransferObject\PasswordResetDto;
use App\Entity\User;
use App\Entity\UserToken;
use App\Enum\FlashEnum;
use App\Enum\UserTokenPurposeEnum;
use App\Form\Form\LoginFormType;
use App\Form\Form\PasswordFormType;
use App\Form\Form\PasswordResetFormType;
use App\Repository\UserRepository;
use App\Service\EmailService\EmailService;
use App\Service\UserTokenService\UserTokenService;
use App\Service\UserTokenService\UserTokenServiceInterface;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
        private readonly UserRepository $userRepository,
        private readonly EmailService $emailService,
        #[Autowire(service: UserTokenService::class)]
        private readonly UserTokenServiceInterface $userTokenService,
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
            'form' => $form,
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
            'token' => 'value',
        ])]
        ?UserToken $token = null
    ): Response
    {
        if (
            ! $token instanceof UserToken
            || ! $token->isActive()
            || $token->getPurpose() !== UserTokenPurposeEnum::EMAIL_VERIFICATION
        ) {
            $this->addFlash(
                FlashEnum::WARNING->value,
                $this->translator->trans('flash.sceptical-issue', [], 'flash')
            );

            return $this->redirectToRoute('app_login');
        }

        $user = $token->getUser();

        $user->setVerifiedAt(CarbonImmutable::now());
        $this->userTokenService->consume($token);

        $this->userRepository->save($user, true);

        $this->addFlash(
            FlashEnum::SUCCESS->value,
            $this->translator->trans('flash.email-address-confirmed', [], 'flash')
        );

        return $this->redirectToRoute(
            $user->isTrader() ? 'trader_dashboard' : 'client_dashboard'
        );
    }

    #[Route('/user/set-password/{token}', name: 'user_set_password')]
    public function setPassword(
        Request $request,
        UserPasswordHasherInterface $hasher,
        #[MapEntity(mapping: [
            'token' => 'value',
        ])]
        ?UserToken $userToken = null,
    ): Response
    {
        if (! $userToken instanceof UserToken
            || ! $userToken->isActive()
            || !in_array($userToken->getPurpose(), [UserTokenPurposeEnum::PASSWORD_SETUP, UserTokenPurposeEnum::PASSWORD_RESET])
        ) {
            $this->addFlash(FlashEnum::ERROR->value, 'security.something-went-wrong');
            return $this->redirectToRoute('app_login');
        }

        $user = $userToken->getUser();

        $passwordForm = $this->createForm(PasswordFormType::class);
        $passwordForm->handleRequest($request);

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $plain = (string) $passwordForm->get('plainPassword')->getData();
            $user->setPassword($hasher->hashPassword($user, $plain));

            $user->setPasswordSetAt(CarbonImmutable::now());

            $this->userTokenService->consumeAllForUserAndPurpose(
                user: $user,
                purpose: UserTokenPurposeEnum::PASSWORD_SETUP
            );

            $this->entityManager->flush();

            $this->addFlash(
                FlashEnum::SUCCESS->value,
                $this->translator->trans('flash.password-changed', [], 'flash')
            );

            $target = $request->getSession()->get('post_set_password_target') ?? '/';
            return $this->redirect($target);
        }

        return $this->render('user/set_password.html.twig', [
            'passwordForm' => $passwordForm,
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route(path: '/password-reset', name: 'request_password_reset')]
    public function passwordReset(Request $request): Response
    {
        $passwordResetForm = $this->createForm(PasswordResetFormType::class);

        $passwordResetForm->handleRequest($request);
        if ($passwordResetForm->isSubmitted() && $passwordResetForm->isValid()) {
            /** @var PasswordResetDto $passwordResetDto */
            $passwordResetDto = $passwordResetForm->getData();

            $user = $this->userRepository->findOneBy([
                'email' => $passwordResetDto->getEmail(),
            ]);

            if (! $user instanceof User) {
                $this->addFlash(FlashEnum::ERROR->value, 'security.something-went-wrong');
                return $this->redirectToRoute('app_login');
            }

            $token = $this->userTokenService->issueToken(
                user: $user,
                purpose: UserTokenPurposeEnum::PASSWORD_RESET,
            );

            $this->emailService->sendPasswordResetEmail(user: $user, locale: $request->getLocale(), context: [
                'user' => $user,
                'token' => $token,
            ]);

            $this->addFlash(FlashEnum::SUCCESS->value, 'security.password-reset-email-sent');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/password_reset.html.twig', [
            'passwordResetForm' => $passwordResetForm,
        ]);
    }
}
