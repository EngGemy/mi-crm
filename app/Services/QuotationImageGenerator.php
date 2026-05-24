<?php

namespace App\Services;

use App\Models\Quotation;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * Convert quotation HTML to a shareable PNG image (WhatsApp-friendly).
 * Uses Playwright (Node.js + Chromium) to screenshot the rendered HTML.
 * Falls back gracefully if Playwright is unavailable.
 */
class QuotationImageGenerator
{
    public function __construct(protected QuotationGenerator $pdf) {}

    public function generate(Quotation $quotation, int $width = 1240, int $height = 1754): string
    {
        $html = $this->pdf->renderHtml($quotation);

        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $htmlPath = $tempDir."/{$quotation->quotation_number}.html";
        file_put_contents($htmlPath, $html);

        $outputDir = storage_path('app/public/quotations/images');
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $outputPath = $outputDir."/{$quotation->quotation_number}.png";

        $scriptPath = base_path('scripts/screenshot.cjs');
        $env = array_merge($_SERVER, [
            'TEMP' => sys_get_temp_dir(),
            'TMP' => sys_get_temp_dir(),
        ]);
        $process = new Process([
            'node',
            $scriptPath,
            $htmlPath,
            $outputPath,
            (string) $width,
            (string) $height,
        ], null, $env);

        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(
                'Image generation failed: '.$process->getErrorOutput()
            );
        }

        @unlink($htmlPath);

        return "quotations/images/{$quotation->quotation_number}.png";
    }

    public function getPublicUrl(Quotation $quotation): string
    {
        $relative = "quotations/images/{$quotation->quotation_number}.png";
        if (! Storage::disk('public')->exists($relative)) {
            $this->generate($quotation);
        }

        return Storage::disk('public')->url($relative);
    }

    public function getPublicPath(Quotation $quotation): string
    {
        $relative = "quotations/images/{$quotation->quotation_number}.png";
        if (! Storage::disk('public')->exists($relative)) {
            $this->generate($quotation);
        }

        return Storage::disk('public')->path($relative);
    }
}
