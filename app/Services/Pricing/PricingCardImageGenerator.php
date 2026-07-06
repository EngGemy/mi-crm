<?php

namespace App\Services\Pricing;

use App\Models\PoultryQuotation;
use Illuminate\Support\Facades\View;

class PricingCardImageGenerator
{
    public function __construct(
        protected HtmlScreenshotService $screenshotService,
    ) {}

    public function generate(PoultryQuotation $quotation): string
    {
        $html = $this->renderHtml($quotation);

        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $htmlPath = $tempDir."/card-{$quotation->quote_number}.html";
        file_put_contents($htmlPath, $html);

        $outputPath = storage_path("app/public/pricing-cards/{$quotation->quote_number}.png");

        $this->screenshotService->capture($htmlPath, $outputPath, 1080, 1080);

        @unlink($htmlPath);

        return "public/pricing-cards/{$quotation->quote_number}.png";
    }

    public function renderHtml(PoultryQuotation $quotation): string
    {
        $snap = $quotation->pricing_snapshot ?? [];
        $financial = $snap['financial'] ?? [];

        $displayTotal = (float) $quotation->total;
        if ($displayTotal <= 0) {
            $displayTotal = (float) (
                $financial['total']
                ?? $financial['grand_total']
                ?? $quotation->subtotal
                ?? 0
            );
        }

        return View::make('pricing-calculator.card', [
            'quotation' => $quotation,
            'displayTotal' => $displayTotal,
            'companyName' => settings('company.name_ar', 'MI Metal Industries'),
            'managerName' => env('SALES_MANAGER_NAME', 'م. كريم العش'),
        ])->render();
    }
}
