<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Vich\UploaderBundle\Naming\SmartUniqueNamer;
use Vich\UploaderBundle\Naming\UniqidNamer;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('vich_uploader', [
        'db_driver' => 'orm',
        'storage' => 'file_system',
        'metadata' => [
            'type' => 'attribute',
        ],
        'mappings' => [
            'images' => [
                'uri_prefix' => '/uploads/images',
                'upload_destination' => '%kernel.project_dir%/public/uploads/images',
                'namer' => SmartUniqueNamer::class,
                'delete_on_update' => true,
                'delete_on_remove' => true,
                'inject_on_load' => false,
            ],
        ],
    ]);
};
