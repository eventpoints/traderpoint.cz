<?php

namespace App\Service\ImageUploader\Contract;

interface Base64ImageUploaderInterface
{
    public function process(string $realPath, int $width = 800, int $height = 600, bool $isWatermarked = false): string;
}
