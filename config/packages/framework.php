<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('framework', [
        'router' => [
            'default_uri' => '%env(APP_URL)%',
        ],
        'secret' => '%env(APP_SECRET)%',
        'session' => true,
        'http_client' => [
            'scoped_clients' => [
                'sms.46elks.client' => [
                    'scope' => 'https://api.46elks.com',
                    'base_uri' => 'https://api.46elks.com/a1/',
                    'auth_basic' => '%env(ELKS_USERNAME)%:%env(ELKS_PASSWORD)%',
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                    'query' => [
                        'from' => '%env(ELKS_FROM)%',
                    ],
                ],
            ],
        ],
    ]);
    if ($containerConfigurator->env() === 'test') {
        $containerConfigurator->extension('framework', [
            'test' => true,
            'session' => [
                'storage_factory_id' => 'session.storage.factory.mock_file',
            ],
        ]);
    }
};
