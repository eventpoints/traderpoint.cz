<?php

declare(strict_types=1);

namespace App\Service\ImageUploader;

use App\Service\ImageUploader\Contract\Base64ImageUploaderInterface;
use Intervention\Image\Decoders\FilePathImageDecoder;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;

final class Base64ImageUploaderService implements Base64ImageUploaderInterface
{
    public function process(string $realPath, int $width = 800, int $height = 600, bool $isWatermarked = false): string
    {
        $manager = new ImageManager(new Driver());
        $image = $manager->read(input: $realPath, decoders: FilePathImageDecoder::class);
        $image->scaleDown(width: $width,height: $height);
        return $image->toJpeg()->toDataUri();
    }
}
