<?php

declare(strict_types=1);

namespace App\Security\Social;

use App\Entity\ExternalIdentity;
use App\Entity\TraderProfile;
use App\Entity\User;
use App\Enum\OauthProviderEnum;
use App\Enum\UserRoleEnum;
use App\Enum\UserTokenPurposeEnum;
use App\Service\AvatarService\AvatarService;
use App\Service\EmailService\EmailService;
use App\Service\StandardPlanSubscriptionService;
use App\Service\UserTokenService\UserTokenService;
use App\Service\UserTokenService\UserTokenServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Psr\Log\LoggerInterface;
use Random\RandomException;
use Stripe\Exception\ApiErrorException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

final class GoogleAuthenticator extends OAuth2Authenticator
{
    use TargetPathTrait;

    public function __construct(
        private readonly ClientRegistry $clients,
        private readonly EntityManagerInterface $em,
        private readonly RouterInterface $router,
        private readonly Security $security,
        private readonly RequestStack $requestStack,
        private readonly AvatarService $avatarService,
        private readonly StandardPlanSubscriptionService $standardPlanSubscriptionService,
        private readonly EmailService $emailService,
        private readonly LoggerInterface $logger,
        #[Autowire(service: UserTokenService::class)]
        private readonly UserTokenServiceInterface $userTokenService,
    )
    {
    }

    public function supports(Request $request): bool
    {
        return $request->attributes->get('_route') === 'oauth_google_check';
    }

    /**
     * @throws ApiErrorException
     * @throws RandomException
     */
    public function authenticate(Request $request): SelfValidatingPassport
    {

        $session = $this->requestStack->getSession();
        $roleIntent = $this->getRoleIntent($session); // 'client' | 'trader' | null

        $data = $this->fetchGoogleUserData();
        $subject = $data['subject'];
        $email = $data['email'];
        $emailVerified = $data['emailVerified'];
        $displayName = $data['displayName'];
        $avatarUrl = $data['avatarUrl'];
        $scopes = $data['scopes'];
        $raw = $data['raw'];

        // 1) Known external identity -> login
        $identityRepo = $this->em->getRepository(ExternalIdentity::class);
        $identity = $identityRepo->findOneBy([
            'oauthProviderEnum' => OauthProviderEnum::GOOGLE,
            'subject' => $subject,
        ]);

        if ($identity instanceof ExternalIdentity) {
            return $this->loginKnownIdentity($identity);
        }

        // 2) Linking while logged in
        $currentUser = $this->security->getUser();
        if ($currentUser instanceof User) {
            return $this->linkIdentityForCurrentUser(
                $currentUser,
                $subject,
                $emailVerified,
                $displayName,
                $avatarUrl,
                $scopes
            );
        }

        // 3) Sign-in / sign-up
        $userRepo = $this->em->getRepository(User::class);
        /** @var User|null $user */
        $user = $userRepo->findOneBy([
            'email' => $email,
        ]);
        $isNewAccount = false;

        if (! $user instanceof User) {
            $user = $this->createNewUserFromGoogle(
                raw: $raw,
                email: $email,
                role: $roleIntent,
                locale: $request->getLocale()
            );
            $this->em->persist($user);
            $isNewAccount = true;
        }

        // If they came through the trader flow, start trial (new trader account)
        if ($isNewAccount && $roleIntent === 'trader') {
            $this->standardPlanSubscriptionService->startStandardPlanTrial($user);
        }

        $newIdentity = $this->createExternalIdentityEntity(
            user: $user,
            subject: $subject,
            emailVerified: $emailVerified,
            displayName: $displayName,
            avatarUrl: $avatarUrl,
            scopes: $scopes
        );
        $this->em->persist($newIdentity);
        $this->em->flush();

        $this->sendWelcomeEmailIfNeeded(
            user: $user,
            isNewAccount: $isNewAccount,
            roleIntent: $roleIntent,
            locale: $request->getLocale()
        );

        // Onboarding only for brand-new users
        $session->set(
            'oauth.post.onboard',
            $isNewAccount ? ($roleIntent ?? 'client') : null
        );

        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier(), fn(): object => $user)
        );
    }

    private function getRoleIntent(SessionInterface $session): ?string
    {
        $roleIntent = $session->get('oauth.intent.role');
        // 'client'|'trader'|null
        $session->remove('oauth.intent.role');
        $session->remove('oauth.intent.source');

        return is_string($roleIntent) ? $roleIntent : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchGoogleUserData(): array
    {
        $client = $this->clients->getClient('google');
        /** @var ResourceOwnerInterface $owner */
        $owner = $client->fetchUser();
        $raw = $owner->toArray();

        $subject = (string) $owner->getId();
        $email = (string) ($raw['email'] ?? '');

        if ($subject === '') {
            throw new AuthenticationException('Google did not return a stable subject (id).');
        }
        if ($email === '') {
            throw new CustomUserMessageAuthenticationException('Google did not provide an email address.');
        }

        $emailVerified = (bool) ($raw['email_verified'] ?? false);
        $displayName = (is_string($raw['name'] ?? null) ? $raw['name'] : trim(
            implode(' ', array_filter([
                is_string($raw['given_name'] ?? null) ? $raw['given_name'] : null,
                is_string($raw['family_name'] ?? null) ? $raw['family_name'] : null,
            ]))
        )) ?: null;

        $avatarUrl = is_string($raw['picture'] ?? null) ? $raw['picture'] : null;

        $scopes = isset($raw['scope'])
            ? (is_array($raw['scope'])
                ? $raw['scope']
                : array_values(array_filter(explode(' ', (string) $raw['scope']))))
            : [];

        return [
            'subject' => $subject,
            'email' => $email,
            'emailVerified' => $emailVerified,
            'displayName' => $displayName,
            'avatarUrl' => $avatarUrl,
            'scopes' => $scopes,
            'raw' => $raw,
        ];
    }

    private function loginKnownIdentity(ExternalIdentity $identity): SelfValidatingPassport
    {
        $identity->updateLastLogin();
        $this->em->flush();

        $user = $identity->getUser();

        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier(), fn(): User => $user)
        );
    }

    /**
     * @param array<int,string> $scopes
     */
    private function linkIdentityForCurrentUser(
        User $currentUser,
        string $subject,
        bool $emailVerified,
        ?string $displayName,
        ?string $avatarUrl,
        array $scopes
    ): SelfValidatingPassport
    {
        $newIdentity = $this->createExternalIdentityEntity(
            user: $currentUser,
            subject: $subject,
            emailVerified: $emailVerified,
            displayName: $displayName,
            avatarUrl: $avatarUrl,
            scopes: $scopes
        );

        $this->em->persist($newIdentity);
        $this->em->flush();

        return new SelfValidatingPassport(
            new UserBadge($currentUser->getUserIdentifier(), fn(): User => $currentUser)
        );
    }

    /**
     * @param array<int, string> $raw
     */
    private function createNewUserFromGoogle(
        array $raw,
        string $email,
        ?string $role,
        string $locale
    ): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($raw['given_name'] ?? '');
        $user->setLastName($raw['family_name'] ?? ($raw['given_name'] ?? ''));
        $user->setAvatar($this->avatarService->generate($email));
        $user->setPreferredLanguage($locale);

        if ($role === 'trader') {
            $this->setupTraderAccount($user);
        } else {
            $this->setupClientAccount($user);
        }

        return $user;
    }

    private function setupTraderAccount(User $user): void
    {
        $roles = $user->getRoles();
        if (! in_array(UserRoleEnum::ROLE_TRADER->name, $roles, true)) {
            $roles[] = UserRoleEnum::ROLE_TRADER->name;
            $user->setRoles($roles);
        }

        if (! $user->getTraderProfile() instanceof \App\Entity\TraderProfile) {
            $profile = new TraderProfile();
            $profile->setOwner($user);
            $user->setTraderProfile($profile);
        }
    }

    private function setupClientAccount(User $user): void
    {
        $roles = $user->getRoles();
        if (! in_array(UserRoleEnum::ROLE_USER->name, $roles, true)) {
            $roles[] = UserRoleEnum::ROLE_USER->name;
            $user->setRoles($roles);
        }
    }

    /**
     * @param array<int, string> $scopes
     */
    private function createExternalIdentityEntity(
        User $user,
        string $subject,
        bool $emailVerified,
        ?string $displayName,
        ?string $avatarUrl,
        array $scopes
    ): ExternalIdentity
    {
        $identity = new ExternalIdentity(
            $user,
            OauthProviderEnum::GOOGLE,
            $subject,
            $emailVerified,
            $displayName,
            $avatarUrl,
            $scopes
        );
        $identity->updateLastLogin();

        return $identity;
    }

    private function sendWelcomeEmailIfNeeded(
        User $user,
        bool $isNewAccount,
        ?string $roleIntent,
        string $locale
    ): void
    {

        if (! $isNewAccount) {
            return;
        }

        $this->logger->info('Sending welcome email after Google signup', [
            'email' => $user->getEmail(),
            'roleIntent' => $roleIntent,
        ]);

        $token = $this->userTokenService->issueToken(user: $user, purpose: UserTokenPurposeEnum::EMAIL_VERIFICATION);

        if ($roleIntent === 'trader') {
            $this->emailService->sendTraderWelcomeEmail(
                user: $user,
                locale: $locale,
                context: [
                    'user' => $user,
                    'token' => $token,
                ],
            );
        } else {
            $this->emailService->sendClientWelcomeEmail(
                user: $user,
                locale: $locale,
                context: [
                    'user' => $user,
                    'token' => $token,
                ],
            );
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?RedirectResponse
    {
        $session = $this->requestStack->getSession();

        // 1) onboarding hint set during authenticate()
        $post = $session->get('oauth.post.onboard');
        $session->remove('oauth.post.onboard');

        if ($post === 'trader') {
            return new RedirectResponse($this->router->generate('trader_dashboard'));
        }
        if ($post === 'client') {
            return new RedirectResponse($this->router->generate('client_dashboard'));
        }

        // 2) previously requested protected page
        $target = $this->getTargetPath($session, $firewallName);
        if ($target) {
            return new RedirectResponse($target);
        }

        // 3) default: user dashboard based on role
        $user = $token->getUser();
        if ($user instanceof User && $user->isTrader()) {
            return new RedirectResponse($this->router->generate('trader_dashboard'));
        }

        return new RedirectResponse($this->router->generate('client_dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $e): ?RedirectResponse
    {
        $bag = $request->getSession()->getBag('flashes');
        if ($bag instanceof FlashBagInterface) {
            $bag->add('error', 'Google sign-in failed. Try again.');
        }
        return new RedirectResponse($this->router->generate('app_login'));
    }

    public function start(Request $request, ?AuthenticationException $authException = null): RedirectResponse
    {
        return new RedirectResponse($this->router->generate('app_login'));
    }
}
