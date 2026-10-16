<?php

namespace App\Quotations\Ventilation;

use App\Enums\PoultryProjectType;
use App\Models\PoultryQuotation;
use App\Services\Poultry\VentilationOffer;
use Mpdf\Mpdf;
use RuntimeException;

/**
 * يطبع بروشور المشتملات الأصلي ويبدّل خانات العنبر وبنود الفورمة فقط.
 */
class VentilationBrochurePdf
{
    public function render(PoultryQuotation $quotation): string
    {
        [$barnLabel, $offer] = app(VentilationOffer::class)->forQuotation($quotation);
        $document = is_array($offer['document'] ?? null) ? $offer['document'] : [];
        $layer = PoultryProjectType::tryFrom((string) $quotation->project_type)?->isLayer() ?? false;
        $template = resource_path($layer
            ? 'quotations/ventilation/template-layer.pdf'
            : 'quotations/ventilation/template.pdf');
        if (! is_file($template)) {
            throw new RuntimeException('قالب عرض المشتملات غير موجود.');
        }

        $mpdf = $this->mpdf();
        $pageCount = $mpdf->SetSourceFile($template);
        for ($page = 1; $page <= $pageCount; $page++) {
            $mpdf->AddPage();
            $mpdf->UseTemplate($mpdf->ImportPage($page), 0, 0, 210, 297);
            if ($page === 1) {
                $this->paintCover($mpdf, $quotation, $barnLabel);
            }
            if ($page === $pageCount) {
                $this->paintPrices($mpdf, $document, $layer);
            }
        }

        return $mpdf->Output('', 'S');
    }

    private function mpdf(): Mpdf
    {
        $tempDir = storage_path('app/mpdf-temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $fontDir = is_dir(public_path('fonts')) ? public_path('fonts/') : storage_path('fonts/');
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'tempDir' => $tempDir,
            'default_font' => 'cairo',
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
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
        $mpdf->SetAutoPageBreak(false);

        return $mpdf;
    }

    private function paintCover(Mpdf $mpdf, PoultryQuotation $quotation, string $barnLabel): void
    {
        $this->cell($mpdf, 98, 569, 200, 38, $barnLabel, [255, 0, 0], [255, 255, 255], 13);
        $this->cell($mpdf, 98, 611, 200, 21, $this->meters($quotation->length), [255, 255, 255], [0, 0, 0], 13);
        $this->cell($mpdf, 98, 635, 200, 20, $this->meters($quotation->width), [255, 255, 255], [0, 0, 0], 13);
        $this->cell($mpdf, 98, 658, 200, 21, $this->meters($quotation->height), [255, 255, 255], [0, 0, 0], 13);
    }

    /**
     * @param  array<string, mixed>  $document
     */
    private function paintPrices(Mpdf $mpdf, array $document, bool $layer): void
    {
        $pink = [228, 184, 183];
        $white = [255, 255, 255];
        $light = [241, 219, 219];
        $rows = $layer
            ? [
                ['key' => 'fans', 'y' => 262, 'h' => 43, 'fill' => $pink],
                ['key' => 'cooling', 'y' => 310, 'h' => 45, 'fill' => $white],
                ['key' => 'inlets', 'y' => 359, 'h' => 44, 'fill' => $pink],
                ['key' => 'monitor', 'y' => 407, 'h' => 44, 'fill' => $white],
                ['key' => 'electricity', 'y' => 455, 'h' => 43, 'fill' => $light],
            ]
            : [
                ['key' => 'fans', 'y' => 262, 'h' => 43, 'fill' => $pink],
                ['key' => 'cooling', 'y' => 310, 'h' => 45, 'fill' => $white],
                ['key' => 'heating', 'y' => 359, 'h' => 44, 'fill' => $pink],
                ['key' => 'circulation', 'y' => 407, 'h' => 44, 'fill' => $white],
                ['key' => 'inlets', 'y' => 455, 'h' => 43, 'fill' => $pink],
                ['key' => 'monitor', 'y' => 503, 'h' => 43, 'fill' => $white],
                ['key' => 'electricity', 'y' => 550, 'h' => 44, 'fill' => $light],
            ];

        foreach ($rows as $row) {
            $line = $document[$row['key']] ?? null;
            if ($row['key'] === 'heating' && is_array($line) && filled($line['label'] ?? null)) {
                $this->cell($mpdf, 48, $row['y'], 200, $row['h'], (string) $line['label'], $row['fill'], [120, 0, 0], 11);
            }
            $this->paintLine($mpdf, is_array($line) ? $line : null, $row['y'], $row['h'], $row['fill']);
        }

        $total = (string) ($document['total'] ?? '');
        if ($total !== '') {
            $this->cell($mpdf, 255, $layer ? 503 : 598, 300, $layer ? 40 : 36, $this->ltr($total), [192, 0, 0], [255, 255, 255], 12);
        }
    }

    /**
     * @param  array{qty: string, unit: string, amount: string}|null  $line
     * @param  array{0: int, 1: int, 2: int}  $fill
     */
    private function paintLine(Mpdf $mpdf, ?array $line, float $y, float $h, array $fill): void
    {
        if ($line === null) {
            $this->cell($mpdf, 46, $y, 506, $h, '', $fill, [20, 16, 16], 9);

            return;
        }

        $this->cell($mpdf, 253, $y, 70, $h, $this->ltr($line['unit']), $fill, [20, 16, 16], 8);
        $this->cell($mpdf, 366, $y, 52, $h, $this->ltr($line['qty']), $fill, [20, 16, 16], 11);
        $this->cell($mpdf, 464, $y, 92, $h, $this->ltr($line['amount']), $fill, [20, 16, 16], 9);
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $fill
     * @param  array{0: int, 1: int, 2: int}  $ink
     */
    private function cell(Mpdf $mpdf, float $x, float $y, float $w, float $h, string $text, array $fill, array $ink, int $size): void
    {
        $mpdf->SetFillColor($fill[0], $fill[1], $fill[2]);
        $mpdf->Rect($this->px($x), $this->py($y), $this->px($w), $this->py($h), 'F');
        if ($text === '') {
            return;
        }

        $mpdf->SetFont('cairo', 'B', $size);
        $mpdf->SetTextColor($ink[0], $ink[1], $ink[2]);
        $top = $this->py($y);
        $height = $this->py($h);
        $textHeight = $size * 25.4 / 72 * 1.35;
        $mpdf->SetXY($this->px($x), $top + max(0, ($height - $textHeight) / 2));
        $mpdf->WriteCell($this->px($w), $textHeight, $text, 0, 0, 'C');
    }

    private function ltr(string $text): string
    {
        return "\u{202A}".$text."\u{202C}";
    }

    private function meters(mixed $value): string
    {
        return VentilationOffer::plain((float) $value).' متر';
    }

    private function px(float $pt): float
    {
        return $pt * 210 / 595.32;
    }

    private function py(float $pt): float
    {
        return $pt * 297 / 841.92;
    }
}
