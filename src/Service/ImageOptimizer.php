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
        #[Autowire('%kernel.project_dir%/public/images/tp-logo.png')]
        private string $watermarkPath
    )
    {
    }

    public function getOptimizedFile(UploadedFile $file, int $targetBytes = 2_000_000): UploadedFile
    {
        $sourcePath = $file->getPathname();
        $driver = $this->images->driver();

        $supportsAvif = $driver->supports('avif');
        $supportsWebp = $driver->supports('webp');

        $maxDim = 1920;
        $minDim = 640;
        $quality = $supportsAvif ? 50 : ($supportsWebp ? 70 : 75);
        $minQ = $supportsAvif ? 28 : ($supportsWebp ? 50 : 55);

        $ext = $supportsAvif ? 'avif' : ($supportsWebp ? 'webp' : 'jpg');
        $mime = $ext === 'jpg' ? 'image/jpeg' : "image/{$ext}";

        $tmpBase = tempnam(sys_get_temp_dir(), 'img_');
        @unlink($tmpBase);
        $dest = $tmpBase . '.' . $ext;

        while (true) {
            $img = $this->images->read($sourcePath)->scaleDown(width: $maxDim, height: $maxDim);
            $img = $this->applyTiledWatermark($img, $this->watermarkPath);

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
        $clientName = $base . '.' . $ext;

        return new UploadedFile($dest, $clientName, $mime, null, true);
    }

    public function getOptimizedAvatarFile(
        UploadedFile $file,
        int $targetBytes = 120_000,
        int $maxDim = 512,
        int $minDim = 128,
        bool $square = true
    ): UploadedFile {
        $sourcePath = $file->getPathname();
        $driver = $this->images->driver();

        $supportsAvif = $driver->supports('avif');
        $supportsWebp = $driver->supports('webp');

        // Prefer modern formats (keep alpha support if you later decide to mask to circle via CSS)
        $quality = $supportsAvif ? 45 : ($supportsWebp ? 70 : 80);
        $minQ = $supportsAvif ? 28 : ($supportsWebp ? 50 : 60);

        $ext = $supportsAvif ? 'avif' : ($supportsWebp ? 'webp' : 'jpg');
        $mime = $ext === 'jpg' ? 'image/jpeg' : "image/{$ext}";

        $tmpBase = tempnam(sys_get_temp_dir(), 'ava_');
        @unlink($tmpBase);
        $dest = $tmpBase . '.' . $ext;

        // Iteratively reduce quality/dimension until under target bytes
        while (true) {
            $img = $this->images->read($sourcePath);

            if ($square) {
                // center-crop to square without upscaling
                $w = $img->width();
                $h = $img->height();
                $side = min($w, $h);
                $x = (int) floor(($w - $side) / 2);
                $y = (int) floor(($h - $side) / 2);
                $img->crop($side, $side, $x, $y);
            }

            // scale down to fit (prevents upscaling)
            $img = $img->scaleDown(width: $maxDim, height: $maxDim);

            // Encode (strip metadata)
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
        $clientName = $base . '-avatar.' . $ext;

        return new UploadedFile($dest, $clientName, $mime, null, true);
    }

    private function applyTiledWatermark(
        ImageInterface $img,
        string $watermarkPath,
        int $opacity = 25,
        int $angle = 35,
        ?int $tileWidth = 60,
        ?int $gap = 240
    ): ImageInterface {
        $imgW = $img->width();
        $imgH = $img->height();

        $tileWidth ??= max(160, (int) round(min($imgW, $imgH) * 0.22));
        $gap ??= (int) round($tileWidth * 0.35);

        $wm = $this->images->read($watermarkPath)
            ->scaleDown(width: $tileWidth, height: $tileWidth)
            ->rotate($angle, 'transparent');

        $wmTmpBase = tempnam(sys_get_temp_dir(), 'wm_');
        $wmTmp = $wmTmpBase . '.png';
        @unlink($wmTmpBase);
        $wm->toPng()->save($wmTmp);

        $wmW = $wm->width();
        $wmH = $wm->height();

        for ($y = 0; $y < $imgH; $y += ($wmH + $gap)) {
            for ($x = 0; $x < $imgW; $x += ($wmW + $gap)) {
                $img->place($wmTmp, 'top-left', $x, $y, $opacity);
            }
        }

        @unlink($wmTmp);

        return $img;
    }

    public function toBase64(UploadedFile $file): string
    {
        $mime = $file->getMimeType() ?? 'application/octet-stream';
        $path = $file->getPathname();
        $data = is_file($path) ? file_get_contents($path) : '';
        return sprintf('data:%s;base64,%s', $mime, base64_encode($data));
    }
}

