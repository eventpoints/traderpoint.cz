<?php

declare(strict_types=1);

use Endroid\QrCode\Writer\PngWriter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('endroid_qr_code', [
        'default' => [
            'writer' => PngWriter::class,
            'size' => 300,
            'margin' => 10,
            'encoding' => 'UTF-8',
            'error_correction_level' => 'low',
            'round_block_size_mode' => 'margin',
            'validate_result' => false,
        ],
    ]);
};
