<?php

namespace App\Controller\Controller;

use App\Enum\OauthProviderEnum;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OAuthController extends AbstractController
{
    #[Route('/connect/start/{provider}', name: 'oauth_start', requirements: [
        'provider' => 'google|facebook',
    ])]
    public function connect(Request $request, string $provider, ClientRegistry $clients): Response
    {
        $session = $request->getSession();
        $session->remove('oauth.intent.role');
        $session->set('oauth.intent.source', 'login');

        $scopes = $provider === OauthProviderEnum::FACEBOOK->value
            ? ['public_profile', 'email']
            : ['openid', 'profile', 'email'];

        return $clients->getClient($provider)->redirect($scopes, []);
    }

    #[Route('/connect/start/{role}/{provider}', name: 'oauth_start_with_role', requirements: [
        'role' => 'client|trader',
        'provider' => 'google|facebook',
    ])]
    public function connectWithRole(Request $request, string $role, string $provider, ClientRegistry $clients): Response
    {
        $session = $request->getSession();
        $session->set('oauth.intent.role', $role);
        $session->set('oauth.intent.source', 'register');

        $scopes = $provider === OauthProviderEnum::FACEBOOK->value
            ? ['public_profile', 'email']
            : ['openid', 'profile', 'email'];

        return $clients->getClient($provider)->redirect($scopes, []);
    }

    #[Route('/connect/google/check', name: 'oauth_google_check')]
    public function googleCheck(): void {}

    #[Route('/connect/facebook/check', name: 'oauth_facebook_check')]
    public function facebookCheck(): void {}
}
