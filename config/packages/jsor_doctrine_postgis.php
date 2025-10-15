<?php

declare(strict_types=1);

use Jsor\Doctrine\PostGIS\Event\ORMSchemaEventListener;
use Jsor\Doctrine\PostGIS\Event\ORMSchemaEventSubscriber;
use Jsor\Doctrine\PostGIS\Functions\ST_AsGeoJSON;
use Jsor\Doctrine\PostGIS\Functions\ST_GeomFromGeoJSON;
use Jsor\Doctrine\PostGIS\Types\GeometryType;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(ORMSchemaEventListener::class)
        ->tag('doctrine.event_subscriber', [
        'connection' => 'default',
    ]);

    $containerConfigurator->extension('doctrine', [
        'dbal' => [
            'mapping_types' => [
                '_text' => 'string',
            ],
            'types' => [
                'geometry' => GeometryType::class,
            ],
        ],
        'orm' => [
            'dql' => [
                'string_functions' => [
                    'ST_AsGeoJSON' => ST_AsGeoJSON::class,
                    'ST_GeomFromGeoJSON' => ST_GeomFromGeoJSON::class,
                ],
            ],
        ],
    ]);
};
