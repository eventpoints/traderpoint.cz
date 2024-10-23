<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator->import('../src/Controller/Controller/', 'attribute')
        ->prefix('/{_locale}')
        ->defaults([
            '_locale' => 'en',
        ])
        ->requirements([
            '_locale' => '[a-z]{2}',
        ]);

};
