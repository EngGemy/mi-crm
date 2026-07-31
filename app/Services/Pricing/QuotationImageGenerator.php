<?php

namespace App\Services\Pricing;

use App\Models\PoultryQuotation;
use App\Services\Pricing\DTOs\QuotationResult;
use Illuminate\Support\Facades\View;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;
use RuntimeException;
use Throwable;

/**
 * Poultry quotation share image via mPDF (no Playwright).
 */
class QuotationImageGenerator
{
    public function generate(PoultryQuotation $quotation, QuotationResult $result): string
    {
        $html = $this->renderHtml($quotation, $result);

        $outputDir = storage_path('app/public/quotations');
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $tempDir = storage_path('app/mpdf-temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $fontDir = is_dir(public_path('fonts')) ? public_path('fonts/') : storage_path('fonts/');

        try {
            $pdf = PDF::loadHTML($html, [
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

            $pdfPath = $outputDir.'/'.$quotation->quote_number.'.pdf';
            file_put_contents($pdfPath, $pdf->output());

            $pngPath = $outputDir.'/'.$quotation->quote_number.'.png';
            if ($this->pdfToPng($pdfPath, $pngPath)) {
                @unlink($pdfPath);

                return 'public/quotations/'.$quotation->quote_number.'.png';
            }

            return 'public/quotations/'.$quotation->quote_number.'.pdf';
        } catch (Throwable $e) {
            report($e);

            throw new RuntimeException('تعذر توليد صورة العرض: '.$e->getMessage(), 0, $e);
        }
    }

    public function renderHtml(PoultryQuotation $quotation, QuotationResult $result): string
    {
        return View::make('sales.quotations.image-template', [
            'quotation' => $quotation,
            'result' => $result,
            'breakdown' => $result->breakdown,
            'managerName' => env('SALES_MANAGER_NAME', 'م. كريم العش'),
            'companyName' => settings('company.name_ar', 'إم آي للصناعات المعدنية'),
        ])->render();
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
