<?php

declare(strict_types=1);

use Intervention\Image\Drivers\Imagick\Driver;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('intervention_image', [
        'driver' => Driver::class,
        'options' => [
            'autoOrientation' => true,
            'strip' => true,
        ],
    ]);
};
