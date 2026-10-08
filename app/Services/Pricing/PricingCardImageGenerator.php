<?php

namespace App\Services\Pricing;

use App\Models\PoultryQuotation;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;
use RuntimeException;
use Throwable;

/**
 * Share card via mPDF — no Node/Playwright required on the server.
 * Produces a square PDF card; converts to PNG when Imagick is available.
 */
class PricingCardImageGenerator
{
    public function generate(PoultryQuotation $quotation): string
    {
        $outputDir = storage_path('app/public/pricing-cards');
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $name = preg_replace('/[^A-Za-z0-9\-_]/', '-', (string) ($quotation->quote_number ?: 'quote')) ?: 'quote';

        try {
            $pngPath = $outputDir.'/'.$name.'.png';
            file_put_contents($pngPath, app(ShareCardPainter::class)->png($quotation));

            return 'public/pricing-cards/'.$name.'.png';
        } catch (Throwable $e) {
            report($e);
        }

        return $this->generatePdfCard($quotation, $outputDir, $name);
    }

    private function generatePdfCard(PoultryQuotation $quotation, string $outputDir, string $name): string
    {
        $html = $this->renderHtml($quotation);

        $tempDir = storage_path('app/mpdf-temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $fontDir = is_dir(public_path('fonts')) ? public_path('fonts/') : storage_path('fonts/');
        $sizeMm = 190;

        try {
            $pdf = PDF::loadHTML($html, [
                'mode' => 'utf-8',
                'format' => [$sizeMm, $sizeMm],
                'default_font' => 'cairo',
                'default_font_size' => 12,
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_top' => 0,
                'margin_bottom' => 0,
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

            $pdfPath = $outputDir.'/'.$name.'.pdf';
            file_put_contents($pdfPath, $pdf->output());

            $pngPath = $outputDir.'/'.$name.'.png';
            if ($this->pdfToPng($pdfPath, $pngPath)) {
                @unlink($pdfPath);

                return 'public/pricing-cards/'.$name.'.png';
            }

            return 'public/pricing-cards/'.$name.'.pdf';
        } catch (Throwable $e) {
            report($e);

            throw new RuntimeException('تعذر توليد كارت المشاركة: '.$e->getMessage(), 0, $e);
        }
    }

    public function renderHtml(PoultryQuotation $quotation): string
    {
        $displayTotal = (float) $quotation->total;
        if ($displayTotal <= 0) {
            $fin = $quotation->pricing_snapshot['financial'] ?? [];
            $displayTotal = (float) ($fin['total'] ?? $fin['grand_total'] ?? $quotation->subtotal ?? 0);
        }

        $profile = app(ShareCardPainter::class)->profile();

        return view('pricing-calculator.card', [
            'quotation' => $quotation,
            'displayTotal' => $displayTotal,
            'companyName' => $profile['name'],
            'profile' => $profile,
            'managerName' => env('SALES_MANAGER_NAME', 'م. كريم العش'),
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
