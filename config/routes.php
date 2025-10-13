<?php

declare(strict_types=1);

use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {

    $routingConfigurator->add('index_redirect', '/')
        ->controller(RedirectController::class)
        ->defaults([
            'route' => 'landing',
            'permanent' => true,
            'keepQueryParams' => true,
            'keepRequestMethod' => true,
        ]);

    $routingConfigurator->import(resource: __DIR__ . '/../src/Controller/Controller/', type: 'attribute')
        ->prefix('/{_locale}')
        ->defaults([
            '_locale' => 'en',
        ])
        ->requirements([
            '_locale' => '[a-z]{2}',
        ]);

    $routingConfigurator->import([
        'path' => __DIR__ . '/../src/Controller/Admin',
        'namespace' => 'App\Controller\Admin',
    ], 'attribute');
};
