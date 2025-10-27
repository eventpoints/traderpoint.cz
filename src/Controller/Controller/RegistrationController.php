<?php

declare(strict_types=1);

namespace App\Controller\Controller;

use App\DataTransferObject\UserTraderDto;
use App\Entity\TraderProfile;
use App\Entity\User;
use App\Enum\UserRoleEnum;
use App\Form\Form\RegistrationFormType;
use App\Form\Form\TraderRegisterFormType;
use App\Repository\UserRepository;
use App\Security\AppCustomAuthenticator;
use App\Service\AvatarService\AvatarService;
use App\Service\EmailService\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly AvatarService $avatarService,
        private readonly UserPasswordHasherInterface $userPasswordHasher,
        private readonly EmailService $emailService
    )
    {
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserAuthenticatorInterface $userAuthenticator,
        AppCustomAuthenticator $authenticator,
        #[CurrentUser]
        null|User $currentUser
    ): ?Response
    {
        if ($currentUser instanceof User) {
            if ($currentUser->isTrader()) {
                return $this->redirectToRoute('trader_dashboard');
            }
            return $this->redirectToRoute('client_dashboard');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $avatar = $this->avatarService->generate($user->getEmail());
            $user->setAvatar($avatar);
            $user->setPreferredLanguage($request->getLocale());

            $password = $this->userPasswordHasher->hashPassword(user: $user, plainPassword: $form->get('plainPassword')->getData());
            $user->setPassword($password);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            if ($user->isTrader()) {
                $this->emailService->sendTraderWelcomeEmail(user: $user, locale: $request->getLocale(), context: [
                    'user' => $user,
                    'token' => $user->getToken(),
                ]);
            } else {
                $this->emailService->sendClientWelcomeEmail(user: $user, locale: $request->getLocale(), context: [
                    'user' => $user,
                    'token' => $user->getToken(),
                ]);
            }

            return $userAuthenticator->authenticateUser($user, $authenticator, $request);
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('trader/register', name: 'trader_register')]
    public function traderRegister(
        Request $request,
        UserAuthenticatorInterface $userAuthenticator,
        AppCustomAuthenticator $authenticator,
        #[CurrentUser]
        null|User $currentUser,
    ): ?Response
    {
        if ($currentUser instanceof User) {
            if ($currentUser->isTrader()) {
                return $this->redirectToRoute('trader_dashboard');
            }
            return $this->redirectToRoute('client_dashboard');
        }

        $userTraderDto = new UserTraderDto();
        $form = $this->createForm(TraderRegisterFormType::class, $userTraderDto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user = $this->userRepository->findOneBy([
                'email' => $userTraderDto->getEmail(),
            ]);
            if ($user !== null) {
                return $this->redirectToRoute('trader_register');
            }

            $avatar = $this->avatarService->generate($userTraderDto->getEmail());

            $user = new User();
            $user->setRoles([UserRoleEnum::ROLE_TRADER->value]);
            $user->setAvatar($avatar);
            $user->setFirstName($userTraderDto->getFirstName());
            $user->setLastName($userTraderDto->getLastName());
            $user->setEmail($userTraderDto->getEmail());
            $user->setEmail($userTraderDto->getEmail());
            $password = $this->userPasswordHasher->hashPassword(user: $user, plainPassword: $form->get('plainPassword')->getData());
            $user->setPassword($password);

            $traderProfile = new TraderProfile();
            foreach ($userTraderDto->getSkills() as $skill) {
                $traderProfile->addSkill($skill);
            }
            $traderProfile->setOwner($user);
            $user->setTraderProfile($traderProfile);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $userAuthenticator->authenticateUser($user, $authenticator, $request);
        }

        return $this->render('registration/trader/register.html.twig', [
            'traderForm' => $form->createView(),
        ]);
    }
}
