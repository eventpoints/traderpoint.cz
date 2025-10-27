<?php

declare(strict_types=1);

use App\Enum\OauthProviderEnum;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('knpu_oauth2_client', [
        'clients' => [
            'google' => [
                'type' => 'google',
                'client_id' => '%env(OAUTH_GOOGLE_CLIENT_ID)%',
                'client_secret' => '%env(OAUTH_GOOGLE_CLIENT_SECRET)%',
                'redirect_route' => 'oauth_google_check',
                'use_oidc_mode' => true,
            ],
            'facebook' => [
                'type' => 'facebook',
                'client_id' => '%env(OAUTH_FACEBOOK_CLIENT_ID)%',
                'client_secret' => '%env(OAUTH_FACEBOOK_CLIENT_SECRET)%',
                'redirect_route' => 'oauth_facebook_check',
                'graph_api_version' => 'v20.0',
            ],
        ],
    ]);
};
