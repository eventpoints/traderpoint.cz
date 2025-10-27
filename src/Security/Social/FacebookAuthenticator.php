<?php
declare(strict_types=1);

namespace App\Security\Social;

use App\Entity\User;
use App\Entity\ExternalIdentity;
use App\Entity\TraderProfile;
use App\Enum\OauthProviderEnum;
use App\Enum\UserRoleEnum;
use App\Service\AvatarService\AvatarService;
use App\Service\GuestNameGenerator;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

final class FacebookAuthenticator extends OAuth2Authenticator
{
    use TargetPathTrait;

    public function __construct(
        private readonly ClientRegistry              $clients,
        private readonly EntityManagerInterface      $em,
        private readonly RouterInterface             $router,
        private readonly Security                    $security,
        private readonly RequestStack                $requestStack,
        private readonly GuestNameGenerator          $guestNameGenerator,
        private readonly AvatarService               $avatarService,
        private readonly UserPasswordHasherInterface $passwordHasher,
    )
    {
    }

    public function supports(Request $request): bool
    {
        return $request->attributes->get('_route') === 'oauth_facebook_check';
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $session = $this->requestStack->getSession();
        $roleIntent = $session?->get('oauth.intent.role'); // 'client'|'trader'|null
        $session?->remove('oauth.intent.role');
        $session?->remove('oauth.intent.source');

        $client = $this->clients->getClient('facebook');
        /** @var ResourceOwnerInterface $owner */
        $owner = $client->fetchUser();
        $raw = $owner->toArray();

        $subject = (string)$owner->getId();
        $email = (string)($raw['email'] ?? '');

        if ($subject === '') {
            throw new AuthenticationException('Facebook did not return a stable subject (id).');
        }
        if ($email === '') {
            throw new CustomUserMessageAuthenticationException('Facebook did not provide an email address.');
        }

        $emailVerified = false; // FB default scopes
        $displayName = (is_string($raw['name'] ?? null) ? $raw['name'] : trim(
            implode(' ', array_filter([
                is_string($raw['first_name'] ?? null) ? $raw['first_name'] : null,
                is_string($raw['last_name'] ?? null) ? $raw['last_name'] : null,
            ]))
        )) ?: null;

        $avatarUrl = null;
        $pic = $raw['picture'] ?? null;
        if (is_array($pic)) {
            $data = $pic['data'] ?? null;
            if (is_array($data) && is_string($data['url'] ?? null)) {
                $avatarUrl = $data['url'];
            }
        }

        $scopes = isset($raw['scope'])
            ? (is_array($raw['scope']) ? $raw['scope'] : array_values(array_filter(explode(' ', (string)$raw['scope']))))
            : [];

        // 1) Known external identity -> login
        $identity = $this->em->getRepository(ExternalIdentity::class)->findOneBy([
            'oauthProviderEnum' => OauthProviderEnum::FACEBOOK,
            'subject' => $subject,
        ]);
        if ($identity instanceof ExternalIdentity) {
            $identity->updateLastLogin();
            $this->em->flush();
            $u = $identity->getUser();
            return new SelfValidatingPassport(new UserBadge($u->getUserIdentifier(), fn() => $u));
        }

        // 2) Linking while logged in
        $currentUser = $this->security->getUser();
        if ($currentUser instanceof User) {
            $newIdentity = new ExternalIdentity(
                $currentUser, OauthProviderEnum::FACEBOOK, $subject,
                $emailVerified, $displayName, $avatarUrl, $scopes
            );
            $newIdentity->updateLastLogin();
            $this->em->persist($newIdentity);
            $this->em->flush();

            return new SelfValidatingPassport(new UserBadge($currentUser->getUserIdentifier(), fn() => $currentUser));
        }

        // 3) Sign-in/up
        $userRepo = $this->em->getRepository(User::class);
        $user = $userRepo->findOneBy(['email' => $email]);
        $created = false;
        $upgradedToTrader = false;

        if (!$user) {
            $user = new User();
            $user->setEmail($email);
            $user->setFirstName($this->guestNameGenerator->generateFirstName());
            $user->setLastName($this->guestNameGenerator->generateLastName());
            $user->setFirstName($raw['first_name']);
            $user->setLastName($raw['last_name']);
            $user->setAvatar($this->avatarService->generate($email));
            $user->setPreferredLanguage($request->getLocale());

            if ($user->getPassword() === null) {
                $user->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(32))));
            }

            if ($roleIntent === 'trader') {
                $roles = $user->getRoles();
                if (!in_array(UserRoleEnum::ROLE_TRADER->value, $roles, true)) {
                    $roles[] = UserRoleEnum::ROLE_TRADER->value;
                    $user->setRoles($roles);
                }
                $profile = new TraderProfile();
                $profile->setOwner($user);
                $user->setTraderProfile($profile);
            }

            $this->em->persist($user);
            $created = true;
        } else {
            if ($roleIntent === 'trader' && !$user->isTrader()) {
                $roles = $user->getRoles();
                if (!in_array(UserRoleEnum::ROLE_TRADER->value, $roles, true)) {
                    $roles[] = UserRoleEnum::ROLE_TRADER->value;
                    $user->setRoles($roles);
                }
                if ($user->getTraderProfile() === null) {
                    $profile = new TraderProfile();
                    $profile->setOwner($user);
                    $user->setTraderProfile($profile);
                }
                $upgradedToTrader = true;
            }
        }

        $newIdentity = new ExternalIdentity(
            $user, OauthProviderEnum::FACEBOOK, $subject,
            $emailVerified, $displayName, $avatarUrl, $scopes
        );
        $newIdentity->updateLastLogin();

        $this->em->persist($newIdentity);
        $this->em->flush();

        $session?->set('oauth.post.onboard',
            $created ? ($roleIntent ?? 'client') : ($upgradedToTrader ? 'trader' : null)
        );

        return new SelfValidatingPassport(new UserBadge($user->getUserIdentifier(), fn() => $user));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?RedirectResponse
    {
        $session = $this->requestStack->getSession();

        // 1) If we set onboarding during authenticate(), do that first
        if ($session) {
            $post = $session->get('oauth.post.onboard');
            $session->remove('oauth.post.onboard');

            if ($post === 'trader') {
                return new RedirectResponse($this->router->generate('trader_dashboard'));
            }
            if ($post === 'client') {
                return new RedirectResponse($this->router->generate('client_dashboard'));
            }
        }

        // 2) If the user was trying to access a protected page, honor that
        $target = $session ? $this->getTargetPath($session, $firewallName) : null;
        if ($target) {
            return new RedirectResponse($target);
        }

        // 3) Otherwise, send them to the correct dashboard
        $user = $token->getUser();
        if ($user instanceof User && $user->isTrader()) {
            return new RedirectResponse($this->router->generate('trader_dashboard'));
        }

        return new RedirectResponse($this->router->generate('client_dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $e): ?RedirectResponse
    {
        $request->getSession()?->getFlashBag()->add('error', 'Facebook sign-in failed. Try again.');
        return new RedirectResponse($this->router->generate('app_login'));
    }

    public function start(Request $request, AuthenticationException $authException = null): RedirectResponse
    {
        return new RedirectResponse($this->router->generate('app_login'));
    }
}