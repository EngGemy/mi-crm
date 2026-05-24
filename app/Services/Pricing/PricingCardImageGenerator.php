<?php

namespace App\Services\Pricing;

use App\Models\PoultryQuotation;
use Illuminate\Support\Facades\View;
use Symfony\Component\Process\Process;

class PricingCardImageGenerator
{
    public function generate(PoultryQuotation $quotation): string
    {
        $html = $this->renderHtml($quotation);

        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $htmlPath = $tempDir."/card-{$quotation->quote_number}.html";
        file_put_contents($htmlPath, $html);

        $outputDir = storage_path('app/public/pricing-cards');
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
            '1080',
            '1080',
        ], null, $env);

        $process->setTimeout(60);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(
                'Card generation failed: '.$process->getErrorOutput()
            );
        }

        @unlink($htmlPath);

        return "public/pricing-cards/{$quotation->quote_number}.png";
    }

    public function renderHtml(PoultryQuotation $quotation): string
    {
        return View::make('pricing-calculator.card', [
            'quotation' => $quotation,
            'companyName' => settings('company.name_ar', 'MI Metal Industries'),
            'managerName' => env('SALES_MANAGER_NAME', 'م. كريم العش'),
        ])->render();
    }
}
