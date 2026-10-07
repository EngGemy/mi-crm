<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class OptimizeLayersImages extends Command
{
    protected $signature = 'quotations:optimize-layers-images';

    protected $description = 'يضغط صور عرض البياض إلى resources/quotations/layers';

    public function handle(): int
    {
        if (! extension_loaded('gd')) {
            $this->error('امتداد GD غير متوفر. Intervention Image غير مثبّت، ولا يُضاف أي package بدون إذن.');

            return self::FAILURE;
        }

        $source = base_path('docs/quotations/layers/media');
        $target = resource_path('quotations/layers');

        if (! is_dir($source)) {
            $this->error('مجلد المصدر غير موجود: '.$source);

            return self::FAILURE;
        }

        if (! is_dir($target) && ! mkdir($target, 0755, true) && ! is_dir($target)) {
            $this->error('تعذر إنشاء مجلد الهدف: '.$target);

            return self::FAILURE;
        }

        $rows = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $path = $file->getPathname();
            if (str_contains(str_replace('\\', '/', $path), '/_unused/')) {
                continue;
            }

            $before = (int) $file->getSize();
            $saved = $this->writeOptimized($path, $target);
            if ($saved === null) {
                $this->warn('تخطّي ملف غير مدعوم: '.$file->getFilename());

                continue;
            }

            $after = (int) filesize($saved);
            $savedPercent = $before > 0 ? round((1 - ($after / $before)) * 100, 1) : 0;
            $rows[] = [
                basename($saved),
                $this->kb($before),
                $this->kb($after),
                $savedPercent.'%',
            ];
        }

        if ($rows === []) {
            $this->warn('لا توجد صور للمعالجة.');

            return self::SUCCESS;
        }

        $this->table(['الاسم', 'الحجم قبل', 'الحجم بعد', 'نسبة التوفير %'], $rows);
        $this->info('تم الحفظ في '.$target);

        return self::SUCCESS;
    }

    private function writeOptimized(string $source, string $targetDir): ?string
    {
        $info = @getimagesize($source);
        if ($info === false) {
            return null;
        }

        $image = $this->load($source, (int) $info[2]);
        if ($image === null) {
            return null;
        }

        $image = $this->fitWidth($image, 1600);
        $name = $this->outputName(basename($source));
        $destination = $targetDir.DIRECTORY_SEPARATOR.$name;
        $png = str_ends_with(strtolower($name), '.png');

        if ($png) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagepng($image, $destination, 9);
        } else {
            $flat = $this->flatten($image);
            imagejpeg($flat, $destination, 80);
            if ($flat !== $image) {
                imagedestroy($flat);
            }
        }

        imagedestroy($image);

        return $destination;
    }

    private function outputName(string $filename): string
    {
        if (strcasecmp($filename, 'image24.png') === 0) {
            return 'image24.png';
        }

        return pathinfo($filename, PATHINFO_FILENAME).'.jpg';
    }

    private function load(string $path, int $type): ?\GdImage
    {
        $image = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_GIF => @imagecreatefromgif($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };

        return $image instanceof \GdImage ? $image : null;
    }

    private function fitWidth(\GdImage $image, int $maxWidth): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        if ($width <= $maxWidth || $width < 1) {
            return $image;
        }

        $newWidth = $maxWidth;
        $newHeight = max(1, (int) round($height * ($maxWidth / $width)));
        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $newWidth, $newHeight, $transparent);
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);

        return $canvas;
    }

    private function flatten(\GdImage $image): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $canvas = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $white);
        imagecopy($canvas, $image, 0, 0, 0, 0, $width, $height);

        return $canvas;
    }

    private function kb(int $bytes): string
    {
        return number_format($bytes / 1024, 1, '.', ',').' KB';
    }
}
