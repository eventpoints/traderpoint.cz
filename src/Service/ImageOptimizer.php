<?php
declare(strict_types=1);

namespace App\Service;

use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class ImageOptimizer
{
    public function __construct(
        private ImageManager $images,
        #[Autowire('%kernel.project_dir%/public/images/tp-logo.png')] private string $watermarkPath
    ) {}

    public function getOptimizedFile(UploadedFile $file, int $targetBytes = 2_000_000): UploadedFile
    {
        $sourcePath = $file->getPathname();
        $driver     = $this->images->driver();

        $supportsAvif = $driver->supports('avif');
        $supportsWebp = $driver->supports('webp');

        $maxDim   = 1920;
        $minDim   = 640;
        $quality  = $supportsAvif ? 50 : ($supportsWebp ? 70 : 75);
        $minQ     = $supportsAvif ? 28 : ($supportsWebp ? 50 : 55);

        $ext  = $supportsAvif ? 'avif' : ($supportsWebp ? 'webp' : 'jpg');
        $mime = $ext === 'jpg' ? 'image/jpeg' : "image/{$ext}";

        $tmpBase = tempnam(sys_get_temp_dir(), 'img_');
        @unlink($tmpBase);
        $dest = $tmpBase.'.'.$ext;

        while (true) {
            $img = $this->images->read($sourcePath)->scaleDown(width: $maxDim, height: $maxDim);
            $img = $this->applyTiledWatermark($img, $this->watermarkPath, opacity: 15, angle: 45, tileWidth: 280, gap: 120);

            if ($ext === 'avif') {
                $encoded = $img->toAvif(quality: $quality, strip: true);
            } elseif ($ext === 'webp') {
                $encoded = $img->toWebp(quality: $quality, strip: true);
            } else {
                $encoded = $img->toJpeg(quality: $quality, progressive: true, strip: true);
            }

            $encoded->save($dest);
            $size = filesize($dest) ?: PHP_INT_MAX;

            if ($size <= $targetBytes) {
                break;
            }

            if ($quality > $minQ) {
                $quality -= 5;
            } elseif ($maxDim > $minDim) {
                $maxDim = max((int) floor($maxDim * 0.9), $minDim);
            } else {
                break;
            }
        }

        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $clientName = $base.'.'.$ext;

        return new UploadedFile($dest, $clientName, $mime, null, true);
    }

    private function applyTiledWatermark(
        ImageInterface $img,
        string $watermarkPath,
        int $opacity = 15,
        int $angle = 45,
        int $tileWidth = 300,
        int $gap = 100
    ): ImageInterface {
        $wm = $this->images->read($watermarkPath)
            ->scaleDown(width: $tileWidth, height: $tileWidth)
            ->rotate($angle, 'transparent');

        $wmW = $wm->width();
        $wmH = $wm->height();
        $imgW = $img->width();
        $imgH = $img->height();

        $startX = -max($wmW, $wmH);
        $startY = -max($wmW, $wmH);
        $stepX  = $wmW + $gap;
        $stepY  = $wmH + $gap;

        for ($y = $startY; $y < $imgH + $wmH; $y += $stepY) {
            for ($x = $startX; $x < $imgW + $wmW; $x += $stepX) {
                $img = $img->place($wm, 'top-left', (int) $x, (int) $y, $opacity);
            }
        }

        return $img;
    }

}


