<?php

namespace App\Services\Pricing;

use App\Models\PoultryQuotation;
use App\Services\Pricing\DTOs\QuotationResult;
use Illuminate\Support\Facades\View;

class QuotationImageGenerator
{
    public function __construct(
        protected HtmlScreenshotService $screenshotService,
    ) {}

    public function generate(PoultryQuotation $quotation, QuotationResult $result): string
    {
        $html = $this->renderHtml($quotation, $result);

        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $htmlPath = $tempDir."/{$quotation->quote_number}.html";
        file_put_contents($htmlPath, $html);

        $outputPath = storage_path("app/public/quotations/{$quotation->quote_number}.png");

        $this->screenshotService->capture($htmlPath, $outputPath, 1240, 1754);

        @unlink($htmlPath);

        return "public/quotations/{$quotation->quote_number}.png";
    }

    public function renderHtml(PoultryQuotation $quotation, QuotationResult $result): string
    {
        return View::make('sales.quotations.image-template', [
            'quotation' => $quotation,
            'result' => $result,
            'breakdown' => $result->breakdown,
            'managerName' => env('SALES_MANAGER_NAME', 'م. كريم العش'),
            'companyName' => settings('company.name_ar', 'MI Metal Industries'),
        ])->render();
    }
}
