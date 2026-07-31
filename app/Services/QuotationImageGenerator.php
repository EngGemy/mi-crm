<?php

namespace App\Services;

use App\Models\Quotation;
use Illuminate\Support\Facades\Storage;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;
use RuntimeException;
use Throwable;

/**
 * Generate a shareable quotation file without Node/Playwright.
 * Prefers PNG when Imagick is available; otherwise saves a PDF.
 */
class QuotationImageGenerator
{
    public function __construct(
        protected QuotationGenerator $pdf,
    ) {}

    public function generate(Quotation $quotation, int $width = 1240, int $height = 1754): string
    {
        unset($width, $height);

        $quotation = $this->pdf->loadRelations($quotation);
        $html = $this->pdf->renderHtml($quotation);

        $dir = storage_path('app/public/quotations/images');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $tempDir = storage_path('app/mpdf-temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $fontDir = is_dir(public_path('fonts')) ? public_path('fonts/') : storage_path('fonts/');

        try {
            $mpdf = PDF::loadHTML($html, [
                'mode' => 'utf-8',
                'format' => 'A4',
                'default_font' => 'cairo',
                'autoLangToFont' => true,
                'autoScriptToLang' => true,
                'tempDir' => $tempDir,
                'custom_font_dir' => $fontDir,
                'custom_font_data' => [
                    'cairo' => [
                        'R' => 'Cairo-Regular.ttf',
                        'B' => 'Cairo-Bold.ttf',
                        'useOTL' => 0xFF,
                        'useKashida' => 75,
                    ],
                ],
            ]);

            $base = $quotation->quotation_number;
            $pdfRelative = "quotations/images/{$base}.pdf";
            $pngRelative = "quotations/images/{$base}.png";

            Storage::disk('public')->put($pdfRelative, $mpdf->output());

            $pdfFull = Storage::disk('public')->path($pdfRelative);
            $pngFull = Storage::disk('public')->path($pngRelative);

            if ($this->pdfToPng($pdfFull, $pngFull)) {
                Storage::disk('public')->delete($pdfRelative);

                return $pngRelative;
            }

            return $pdfRelative;
        } catch (Throwable $e) {
            report($e);

            throw new RuntimeException('تعذر توليد صورة/ملف العرض: '.$e->getMessage(), 0, $e);
        }
    }

    public function getPublicUrl(Quotation $quotation): string
    {
        $relative = $this->resolveRelativePath($quotation);

        return Storage::disk('public')->url($relative);
    }

    public function getPublicPath(Quotation $quotation): string
    {
        $relative = $this->resolveRelativePath($quotation);

        return Storage::disk('public')->path($relative);
    }

    private function resolveRelativePath(Quotation $quotation): string
    {
        $base = $quotation->quotation_number;
        $png = "quotations/images/{$base}.png";
        $pdf = "quotations/images/{$base}.pdf";

        if (Storage::disk('public')->exists($png)) {
            return $png;
        }
        if (Storage::disk('public')->exists($pdf)) {
            return $pdf;
        }

        return $this->generate($quotation);
    }

    private function pdfToPng(string $pdfPath, string $pngPath): bool
    {
        if (! extension_loaded('imagick')) {
            return false;
        }

        try {
            $im = new \Imagick;
            $im->setResolution(144, 144);
            $im->readImage($pdfPath.'[0]');
            $im->setImageFormat('png');
            $im->setImageBackgroundColor('white');
            $im = $im->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
            $im->writeImage($pngPath);
            $im->clear();
            $im->destroy();

            return is_file($pngPath);
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
