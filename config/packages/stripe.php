<?php

declare(strict_types=1);

use Stripe\StripeClient;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set('stripe.client', StripeClient::class)
        ->args([
        '%env(STRIPE_SECRET_KEY)%',
    ]);

    $services->alias(StripeClient::class, 'stripe.client');
};
