<?php

namespace App\Services;

use App\Models\PoultryQuotation;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

class PoultryQuotationPdfGenerator
{
    protected array $mpdfConfig;

    public function __construct()
    {
        $this->mpdfConfig = [
            'default_font'         => 'cairo',
            'default_font_size'    => '10',
            'autoLangToFont'       => true,
            'autoScriptToLang'     => true,
            'setAutoTopMargin'     => 'pad',
            'setAutoBottomMargin'  => 'pad',
            'margin_top'           => 10,
            'margin_bottom'        => 10,
            'margin_left'          => 14,
            'margin_right'         => 14,
            'margin_header'        => 8,
            'margin_footer'        => 8,
            'custom_font_dir'      => public_path('fonts/'),
            'custom_font_data'     => [
                'cairo' => [
                    'R'          => 'Cairo-Regular.ttf',
                    'B'          => 'Cairo-Bold.ttf',
                    'useOTL'     => 0xFF,
                    'useKashida' => 75,
                ],
            ],
        ];
    }

    public function download(PoultryQuotation $q)
    {
        $html = view('poultry.quotation-pdf', $this->viewData($q))->render();

        return PDF::loadHTML($html, $this->mpdfConfig)
            ->download("Poultry-Quote-{$q->quote_number}.pdf");
    }

    public function stream(PoultryQuotation $q)
    {
        $html = view('poultry.quotation-pdf', $this->viewData($q))->render();

        return PDF::loadHTML($html, $this->mpdfConfig)
            ->stream("Poultry-Quote-{$q->quote_number}.pdf");
    }

    protected function viewData(PoultryQuotation $q): array
    {
        $snap     = $q->pricing_snapshot ?? [];
        $tech     = $snap['technical']  ?? [];
        $computed = $snap['computed']   ?? [];
        $items    = $snap['items']      ?? [];
        $fin      = $snap['financial']  ?? [];

        // Group items by section
        $grouped = [];
        foreach ($items as $item) {
            if (($item['qty'] ?? 0) <= 0) continue;
            $sec = $item['section'] ?? 'other';
            $grouped[$sec][] = $item;
        }

        return compact('q', 'snap', 'tech', 'computed', 'items', 'fin', 'grouped');
    }
}
