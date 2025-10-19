<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {

    if ($containerConfigurator->env() === 'dev') {

        $containerConfigurator->extension('intervention_image', [
            'driver' => \Intervention\Image\Drivers\Imagick\Driver::class,
            'options' => [
                'autoOrientation' => true,
                'strip' => true,
            ],
        ]);
    }

    if ($containerConfigurator->env() === 'prod') {
        $containerConfigurator->extension('intervention_image', [
            'driver' => \Intervention\Image\Drivers\Gd\Driver::class,
            'options' => [
                'autoOrientation' => true,
                'strip' => true,
            ],
        ]);
    }
};
