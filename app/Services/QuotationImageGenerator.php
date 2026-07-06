<?php

namespace App\Services;

use App\Models\Quotation;
use App\Services\Pricing\HtmlScreenshotService;
use Illuminate\Support\Facades\Storage;

/**
 * Convert quotation HTML to a shareable PNG image (WhatsApp-friendly).
 * Uses Playwright (Node.js + Chromium) to screenshot the rendered HTML.
 * Falls back gracefully if Playwright is unavailable.
 */
class QuotationImageGenerator
{
    public function __construct(
        protected QuotationGenerator $pdf,
        protected HtmlScreenshotService $screenshotService,
    ) {}

    public function generate(Quotation $quotation, int $width = 1240, int $height = 1754): string
    {
        $html = $this->pdf->renderHtml($quotation);

        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $htmlPath = $tempDir."/{$quotation->quotation_number}.html";
        file_put_contents($htmlPath, $html);

        $outputPath = storage_path("app/public/quotations/images/{$quotation->quotation_number}.png");

        $this->screenshotService->capture($htmlPath, $outputPath, $width, $height);

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
