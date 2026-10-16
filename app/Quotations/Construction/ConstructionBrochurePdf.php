<?php

namespace App\Quotations\Construction;

use App\Enums\PoultryProjectType;
use App\Models\PoultryQuotation;
use App\Services\Poultry\ConstructionSummary;
use App\Services\Poultry\VentilationOffer;
use Mpdf\Mpdf;
use RuntimeException;

/**
 * يطبع بروشور الإنشاءات الأصلي (5 صفحات) ويبدّل خانات العنبر والأسعار فقط.
 */
class ConstructionBrochurePdf
{
    public function render(PoultryQuotation $quotation): string
    {
        $template = resource_path('quotations/construction/template.pdf');
        if (! is_file($template)) {
            throw new RuntimeException('قالب عرض الإنشاءات غير موجود.');
        }

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

        $pageCount = $mpdf->SetSourceFile($template);
        for ($page = 1; $page <= $pageCount; $page++) {
            $mpdf->AddPage();
            $mpdf->UseTemplate($mpdf->ImportPage($page));
            if ($page === 1) {
                $this->paintCover($mpdf, $quotation);
            }
            if ($page === 5) {
                $this->paintPrices($mpdf, $quotation);
            }
        }

        return $mpdf->Output('', 'S');
    }

    private function paintCover(Mpdf $mpdf, PoultryQuotation $quotation): void
    {
        $barn = app(VentilationOffer::class)->barnLabel(
            PoultryProjectType::tryFrom((string) $quotation->project_type)
        );

        $this->cell($mpdf, 102, 502, 192, 37, $barn, [255, 0, 0], [255, 255, 255], 13);
        $this->cell($mpdf, 102, 545, 192, 21, VentilationOffer::plain((float) $quotation->length).' متر', [255, 255, 255], [0, 0, 0], 13);
        $this->cell($mpdf, 102, 570, 192, 21, VentilationOffer::plain((float) $quotation->width).' متر', [255, 255, 255], [0, 0, 0], 13);
        $this->cell($mpdf, 102, 596, 192, 21, VentilationOffer::plain((float) $quotation->height).' متر', [255, 255, 255], [0, 0, 0], 13);
    }

    private function paintPrices(Mpdf $mpdf, PoultryQuotation $quotation): void
    {
        $lines = app(ConstructionSummary::class)->brochure($quotation);
        $rows = [
            ['line' => $lines['concrete'], 'label' => 'الأرضية الخرسانية', 'y' => 273, 'h' => 61, 'fill' => [241, 219, 219]],
            ['line' => $lines['shell'], 'label' => 'الجملون والحوائط', 'y' => 338, 'h' => 61, 'fill' => [228, 184, 183]],
            ['line' => $lines['tanks'], 'label' => 'الخزانات', 'y' => 403, 'h' => 63, 'fill' => [241, 219, 219]],
        ];

        foreach ($rows as $row) {
            $this->cell($mpdf, 36, $row['y'], 196, $row['h'], $row['label'], $row['fill'], [120, 0, 0], 9);
            $this->cell($mpdf, 238, $row['y'], 62, $row['h'], $row['line']['unit'], $row['fill'], [20, 16, 16], 8);
            $this->cell($mpdf, 354, $row['y'], 40, $row['h'], $row['line']['qty'], $row['fill'], [20, 16, 16], 9);
            $this->cell($mpdf, 451, $row['y'], 93, $row['h'], $row['line']['amount'], $row['fill'], [20, 16, 16], 9);
        }

        $this->cell($mpdf, 36, 470, 520, 61, '', [255, 255, 255], [255, 255, 255], 8);

        $this->cell($mpdf, 310, 538, 234, 54, $lines['total'], [192, 0, 0], [255, 255, 255], 12);
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $fill
     * @param  array{0: int, 1: int, 2: int}  $ink
     */
    private function cell(Mpdf $mpdf, float $x, float $y, float $w, float $h, string $text, array $fill, array $ink, int $size): void
    {
        $mpdf->SetFillColor($fill[0], $fill[1], $fill[2]);
        $mpdf->Rect($this->px($x), $this->py($y), $this->px($w), $this->py($h), 'F');
        $mpdf->SetFont('cairo', 'B', $size);
        $mpdf->SetTextColor($ink[0], $ink[1], $ink[2]);
        $top = $this->py($y);
        $height = $this->py($h);
        $textHeight = $size * 25.4 / 72 * 1.35;
        $mpdf->SetXY($this->px($x), $top + max(0, ($height - $textHeight) / 2));
        $mpdf->WriteCell($this->px($w), $textHeight, $text, 0, 0, 'C');
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
