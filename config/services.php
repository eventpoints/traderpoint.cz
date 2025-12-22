<?php

declare(strict_types=1);

use App\Controller\Stripe\StripeWebhookController;
use App\Service\IssueMediationAIService;
use App\Validator\Constraint\CompanyNumberConstraintValidator;
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

    $services->set(IssueMediationAIService::class)
        ->arg('$anthropicApiKey', param('env(ANTHROPIC_API_KEY)'));

    $services
        ->set(CompanyNumberConstraintValidator::class)
        ->tag('validator.constraint_validator');
};
