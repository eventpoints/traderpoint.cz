<?php

declare(strict_types=1);

namespace App\Security\Social;

use App\Entity\ExternalIdentity;
use App\Entity\TraderProfile;
use App\Entity\User;
use App\Enum\OauthProviderEnum;
use App\Enum\UserRoleEnum;
use App\Service\AvatarService\AvatarService;
use App\Service\StandardPlanSubscriptionService;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Random\RandomException;
use Stripe\Exception\ApiErrorException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
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
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly StandardPlanSubscriptionService $standardPlanSubscriptionService,
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
        $roleIntent = $session->get('oauth.intent.role'); // 'client'|'trader'|null
        $session->remove('oauth.intent.role');
        $session->remove('oauth.intent.source');

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
            ? (is_array($raw['scope']) ? $raw['scope'] : array_values(array_filter(explode(' ', (string) $raw['scope']))))
            : [];

        // 1) Known external identity -> login
        $identity = $this->em->getRepository(ExternalIdentity::class)->findOneBy([
            'oauthProviderEnum' => OauthProviderEnum::GOOGLE,
            'subject' => $subject,
        ]);
        if ($identity instanceof ExternalIdentity) {
            $identity->updateLastLogin();
            $this->em->flush();
            $u = $identity->getUser();
            return new SelfValidatingPassport(new UserBadge($u->getUserIdentifier(), fn(): \App\Entity\User => $u));
        }

        // 2) Linking while logged in
        $currentUser = $this->security->getUser();
        if ($currentUser instanceof User) {
            $newIdentity = new ExternalIdentity(
                $currentUser,
                OauthProviderEnum::GOOGLE,
                $subject,
                $emailVerified,
                $displayName,
                $avatarUrl,
                $scopes
            );
            $newIdentity->updateLastLogin();
            $this->em->persist($newIdentity);
            $this->em->flush();

            return new SelfValidatingPassport(new UserBadge($currentUser->getUserIdentifier(), fn(): \App\Entity\User => $currentUser));
        }

        // 3) Sign-in/up
        $userRepo = $this->em->getRepository(User::class);
        $user = $userRepo->findOneBy([
            'email' => $email,
        ]);
        $created = false;
        $upgradedToTrader = false;

        if ($user === null) {
            $user = new User();
            $user->setEmail($email);
            $user->setFirstName($raw['given_name']);
            $user->setLastName($raw['family_name'] ?? $raw['given_name']);
            $user->setAvatar($this->avatarService->generate($email));
            $user->setPreferredLanguage($request->getLocale());
            // If password column is NOT NULL, set an unusable random hash
            if ($user->getPassword() === null) {
                $user->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(32))));
            }
            if ($roleIntent === 'trader') {
                $roles = $user->getRoles();
                if (! in_array(UserRoleEnum::ROLE_TRADER->name, $roles, true)) {
                    $roles[] = UserRoleEnum::ROLE_TRADER->name;
                    $user->setRoles($roles);
                }
                $profile = new TraderProfile();
                $profile->setOwner($user);
                $user->setTraderProfile($profile);
            }
            $this->em->persist($user);
            $created = true;
        } elseif ($roleIntent === 'trader' && ! $user->isTrader()) {
            // Upgrade if trader intent
            $roles = $user->getRoles();
            if (! in_array(UserRoleEnum::ROLE_TRADER->name, $roles, true)) {
                $roles[] = UserRoleEnum::ROLE_TRADER->name;
                $user->setRoles($roles);
            }
            if ($user->getTraderProfile() === null) {
                $profile = new TraderProfile();
                $profile->setOwner($user);
                $user->setTraderProfile($profile);
            }
            $upgradedToTrader = true;

            $this->em->persist($user);
        }

        if ($roleIntent === 'trader') {
            $this->standardPlanSubscriptionService->startStandardPlanTrial($user);
        }

        $newIdentity = new ExternalIdentity(
            $user,
            OauthProviderEnum::GOOGLE,
            $subject,
            $emailVerified,
            $displayName,
            $avatarUrl,
            $scopes
        );
        $newIdentity->updateLastLogin();

        $this->em->persist($newIdentity);
        $this->em->flush();

        // Onboarding only for brand-new users (or upgrade to trader)
        $session->set(
            'oauth.post.onboard',
            $created ? ($roleIntent ?? 'client') : ($upgradedToTrader ? 'trader' : null)
        );

        return new SelfValidatingPassport(new UserBadge($user->getUserIdentifier(), fn(): object => $user));
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
