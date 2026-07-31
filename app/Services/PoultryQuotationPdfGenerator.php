<?php

namespace App\Services;

use App\Models\PoultryQuotation;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;
use RuntimeException;
use Throwable;

class PoultryQuotationPdfGenerator
{
    protected array $mpdfConfig;

    public function __construct()
    {
        $fontDir = is_dir(public_path('fonts')) ? public_path('fonts/') : storage_path('fonts/');

        $this->mpdfConfig = [
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'cairo',
            'default_font_size' => '10',
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
            'setAutoTopMargin' => 'pad',
            'setAutoBottomMargin' => 'pad',
            'margin_top' => 18,
            'margin_bottom' => 14,
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_header' => 8,
            'margin_footer' => 8,
            'tempDir' => storage_path('app/mpdf-temp'),
            'custom_font_dir' => $fontDir,
            'custom_font_data' => [
                'cairo' => [
                    'R' => 'Cairo-Regular.ttf',
                    'B' => 'Cairo-Bold.ttf',
                    'useOTL' => 0xFF,
                    'useKashida' => 75,
                ],
            ],
        ];
    }

    public function download(PoultryQuotation $q)
    {
        try {
            $this->ensureTempDir();
            $html = view('poultry.quotation-pdf', $this->viewData($q))->render();

            return PDF::loadHTML($html, $this->mpdfConfig)
                ->download("Poultry-Quote-{$q->quote_number}.pdf");
        } catch (Throwable $e) {
            report($e);

            throw new RuntimeException('تعذر إنشاء ملف PDF: '.$e->getMessage(), 0, $e);
        }
    }

    public function stream(PoultryQuotation $q)
    {
        try {
            $this->ensureTempDir();
            $html = view('poultry.quotation-pdf', $this->viewData($q))->render();

            return PDF::loadHTML($html, $this->mpdfConfig)
                ->stream("Poultry-Quote-{$q->quote_number}.pdf");
        } catch (Throwable $e) {
            report($e);

            throw new RuntimeException('تعذر عرض ملف PDF: '.$e->getMessage(), 0, $e);
        }
    }

    protected function ensureTempDir(): void
    {
        $dir = storage_path('app/mpdf-temp');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    protected function viewData(PoultryQuotation $q): array
    {
        $snap = $q->pricing_snapshot ?? [];
        $tech = $snap['technical'] ?? [];
        $computed = $snap['computed'] ?? [];
        $items = $snap['items'] ?? [];
        $fin = $snap['financial'] ?? [];

        $excludedKeys = ['main_fans', 'cooling', 'windows', 'side_fans'];

        $grouped = [];
        foreach ($items as $item) {
            if (($item['qty'] ?? 0) <= 0) {
                continue;
            }
            if (in_array($item['key'] ?? '', $excludedKeys, true)) {
                continue;
            }
            $sec = $item['section'] ?? 'other';
            if (in_array($sec, ['ventilation', 'cooling'], true)) {
                continue;
            }
            $grouped[$sec][] = $item;
        }

        return compact('q', 'snap', 'tech', 'computed', 'items', 'fin', 'grouped');
    }
}
