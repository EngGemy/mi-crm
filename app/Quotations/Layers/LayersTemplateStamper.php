<?php

namespace App\Quotations\Layers;

use App\Enums\PoultryProjectType;
use Mpdf\Mpdf;
use RuntimeException;

/**
 * يطبع صفحات ملف البياض الأصلية (14 صفحة) ويبدّل خانات البيانات فقط.
 */
class LayersTemplateStamper
{
    /** صفحة دولاب البيض في القالب. تظهر مع جمع البيض الآلي فقط. */
    private const EGG_COLLECTION_PAGE = 7;

    public function pdf(array $data): string
    {
        $template = resource_path('quotations/layers/template.pdf');
        if (! is_file($template)) {
            throw new RuntimeException('قالب صفحات البياض غير موجود.');
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
        $stamps = $this->stamps($data);

        $keepEggPage = ($data['project_type'] ?? '') === PoultryProjectType::LayerAutoCollect->value;

        for ($page = 1; $page <= $pageCount; $page++) {
            if ($page === self::EGG_COLLECTION_PAGE && ! $keepEggPage) {
                continue;
            }

            $mpdf->AddPage();
            $mpdf->UseTemplate($mpdf->ImportPage($page));

            if ($page === 2) {
                $this->paintCover($mpdf, $data);
            }

            foreach ($stamps as $stamp) {
                if ($stamp['page'] !== $page || $stamp['text'] === '') {
                    continue;
                }

                $mpdf->SetFillColor(255, 255, 255);
                $mpdf->Rect($stamp['x'], $stamp['y'], $stamp['w'], $stamp['h'], 'F');
                $mpdf->SetFont('cairo', 'B', $stamp['size']);
                $mpdf->SetTextColor(21, 21, 21);
                $mpdf->SetXY($stamp['x'], $stamp['y'] + 1.2);
                $mpdf->WriteCell($stamp['w'], $stamp['h'] - 1.2, $stamp['text'], 0, 0, 'C');
            }
        }

        return $mpdf->Output('', 'S');
    }

    /** @param  array<string, mixed>  $data
     * @return list<array{page: int, x: float, y: float, w: float, h: float, size: int, text: string}>
     */
    private function stamps(array $data): array
    {
        $financial = is_array($data['financial'] ?? null) ? $data['financial'] : [];

        return [
            $this->box(10, 37.7, 121.4, 28, 6, 12, $this->grouped($data['total_nests'] ?? null)),
            $this->box(11, 122.3, 112.6, 32, 6, 11, $this->grouped($financial['barn_usd'] ?? null)),
            $this->box(11, 161.9, 112.6, 36, 6, 11, $this->grouped($financial['barn_egp'] ?? null)),
        ];
    }

    /** @param  array<string, mixed>  $data */
    private function paintCover(Mpdf $mpdf, array $data): void
    {
        $client = trim((string) ($data['client_name'] ?? ''));
        if ($client !== '') {
            $mpdf->SetFillColor(138, 79, 68);
            $mpdf->Rect(18, 189.6, 148, 9.4, 'F');
            $mpdf->SetFont('cairo', 'B', 14);
            $mpdf->SetTextColor(255, 255, 255);
            $mpdf->SetXY(18, 190.8);
            $mpdf->WriteCell(148, 7, 'مقدم الى '.$client, 0, 0, 'C');
        }

        $rows = [
            ['نوع العنبر', $this->barnType((string) ($data['project_type'] ?? ''))],
            ['طول العنبر', $this->withUnit($data['length'] ?? null, 'متر')],
            ['عرض العنبر الداخلي', $this->withUnit($data['width'] ?? null, 'متر')],
            ['ارتفاع العنبر', $this->withUnit($data['height'] ?? null, 'متر')],
            ['مكان المشروع', trim((string) ($data['location'] ?? ''))],
        ];

        $x = 28.0;
        $y = 199.6;
        $width = 154.0;
        $headerH = 11.0;
        $rowH = 8.8;
        $valueW = 70.0;

        $mpdf->SetFillColor(176, 26, 34);
        $mpdf->Rect($x, $y, $width, $headerH, 'F');
        $mpdf->SetFont('cairo', 'B', 13);
        $mpdf->SetTextColor(255, 255, 255);
        $mpdf->SetXY($x, $y + 1.6);
        $mpdf->WriteCell($width, $headerH - 2, 'تفاصيل العنبر', 0, 0, 'C');

        $mpdf->SetLineWidth(0.25);
        $mpdf->SetDrawColor(214, 164, 162);
        $y += $headerH;
        foreach ($rows as $i => [$label, $value]) {
            $mpdf->SetFillColor($i % 2 === 0 ? 249 : 255, $i % 2 === 0 ? 229 : 246, $i % 2 === 0 ? 228 : 245);
            $mpdf->Rect($x, $y, $width, $rowH, 'F');
            $mpdf->Rect($x, $y, $width, $rowH, 'D');
            $mpdf->Line($x + $valueW, $y, $x + $valueW, $y + $rowH);
            $mpdf->SetTextColor(32, 32, 32);
            $mpdf->SetFont('cairo', 'B', 12);
            $mpdf->SetXY($x, $y + 1.3);
            $mpdf->WriteCell($valueW, $rowH - 1.6, $value, 0, 0, 'C');
            $mpdf->SetXY($x + $valueW, $y + 1.3);
            $mpdf->WriteCell($width - $valueW, $rowH - 1.6, $label, 0, 0, 'C');
            $y += $rowH;
        }
    }

    /** @return array{page: int, x: float, y: float, w: float, h: float, size: int, text: string} */
    private function box(int $page, float $x, float $y, float $w, float $h, int $size, string $text): array
    {
        return compact('page', 'x', 'y', 'w', 'h', 'size', 'text');
    }

    private function barnType(string $projectType): string
    {
        return match ($projectType) {
            PoultryProjectType::Layer->value => 'بياض',
            PoultryProjectType::LayerAutoCollect->value => 'بياض جمع آلي',
            PoultryProjectType::LayerRearing->value => 'تربية بياض',
            default => '',
        };
    }

    private function withUnit(mixed $value, string $unit): string
    {
        $number = $this->comma($value);

        return $number === '' ? '' : $number.' '.$unit;
    }

    private function comma(mixed $value): string
    {
        if (! is_numeric($value)) {
            return '';
        }

        $number = round((float) $value, 2);
        if (abs($number - round($number)) < 0.001) {
            return (string) (int) round($number);
        }

        $formatted = number_format($number, 2, ',', '');

        return rtrim(rtrim($formatted, '0'), ',');
    }

    private function grouped(mixed $value): string
    {
        if (! is_numeric($value)) {
            return '';
        }

        $number = (float) $value;
        $decimals = abs($number - round($number)) < 0.001 ? 0 : 2;

        return number_format($number, $decimals, '.', ',');
    }
}
