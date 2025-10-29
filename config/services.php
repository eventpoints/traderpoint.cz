<?php

declare(strict_types=1);

use App\Controller\Stripe\StripeWebhookController;
use Stripe\StripeClient;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure();

    $services->load('App\\', __DIR__ . '/../src/')
        ->exclude([
            __DIR__ . '/../src/DependencyInjection/',
            __DIR__ . '/../src/Entity/',
            __DIR__ . '/../src/Kernel.php',
        ]);

    $services->set(StripeWebhookController::class)
        ->arg('$webHookSecret', param('env(STRIPE_WEBHOOK_SECRET)'));

    $services->set(StripeClient::class)
        ->args([[
            'api_key' => param(name: 'env(STRIPE_PRIVATE_KEY)'),
        ]]);

};
