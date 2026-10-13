<?php

namespace App\Quotations\Pdf;

use App\Models\PoultryQuotation;
use App\Services\Poultry\VentilationOffer;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

class OfferPdf
{
    public function ventilation(PoultryQuotation $quotation): string
    {
        [, $offer] = app(VentilationOffer::class)->forQuotation($quotation);

        return $this->render($quotation, 'عرض سعر التهوية', $offer['ventilation_pdf']['lines'], $offer['ventilation_pdf']['total']);
    }

    public function accessories(PoultryQuotation $quotation): string
    {
        [, $offer] = app(VentilationOffer::class)->forQuotation($quotation);

        return $this->render($quotation, 'عرض سعر المشتملات', $offer['accessories_pdf']['lines'], $offer['accessories_pdf']['total']);
    }

    /**
     * @param  list<array{0: string, 1: string, 2: string, 3: string}>  $lines
     */
    private function render(PoultryQuotation $quotation, string $title, array $lines, string $total): string
    {
        $html = view('poultry.offer-pdf', [
            'quotation' => $quotation,
            'title' => $title,
            'lines' => $lines,
            'total' => $total,
            'barn' => app(VentilationOffer::class)->barnLabel(
                \App\Enums\PoultryProjectType::tryFrom((string) $quotation->project_type)
            ),
        ])->render();

        return PDF::loadHTML($html, [
            'default_font' => 'cairo',
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
            'custom_font_dir' => public_path('fonts/'),
            'custom_font_data' => [
                'cairo' => [
                    'R' => 'Cairo-Regular.ttf',
                    'B' => 'Cairo-Bold.ttf',
                    'useOTL' => 0xFF,
                    'useKashida' => 75,
                ],
            ],
        ])->output();
    }
}
