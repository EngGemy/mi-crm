<?php

namespace App\Services\Pricing;

use App\Models\PoultryQuotation;
use App\Services\Pricing\DTOs\QuotationResult;
use Illuminate\Support\Facades\View;
use Symfony\Component\Process\Process;

class QuotationImageGenerator
{
    public function generate(PoultryQuotation $quotation, QuotationResult $result): string
    {
        $html = $this->renderHtml($quotation, $result);

        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $htmlPath = $tempDir."/{$quotation->quote_number}.html";
        file_put_contents($htmlPath, $html);

        $outputDir = storage_path('app/public/quotations');
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $outputPath = $outputDir."/{$quotation->quote_number}.png";

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
            '1240',
            '1754',
        ], null, $env);

        $process->setTimeout(60);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(
                'Image generation failed: '.$process->getErrorOutput()
            );
        }

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
