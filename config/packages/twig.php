<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('twig', [
        'file_name_pattern' => '*.twig',
        'form_themes' => [
            'bootstrap_5_layout.html.twig',
            'form/fields/rating_range.html.twig',
            'form/switch.html.twig',
            'form/smart_range_theme.html.twig',
            'form/map_location.html.twig',
            'form/password_input.html.twig',
            'form/phone_number.html.twig',
            'form/_form_theme.html.twig',
            'form/flatpickr.html.twig',
            'form/ux_autocomplete_floating.html.twig',
        ],
        'globals' => [
            'stripe_public_key' => '%env(STRIPE_PUBLIC_KEY)%',
            'stripe_private_key' => '%env(STRIPE_PRIVATE_KEY)%',
            'cookies' => '%app.cookies%',
        ],
    ]);
    if ($containerConfigurator->env() === 'test') {
        $containerConfigurator->extension('twig', [
            'strict_variables' => true,
        ]);
    }
};
