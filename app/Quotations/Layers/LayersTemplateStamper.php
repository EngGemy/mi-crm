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

        for ($page = 1; $page <= $pageCount; $page++) {
            $mpdf->AddPage();
            $mpdf->UseTemplate($mpdf->ImportPage($page));

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
            $this->box(2, 31.3, 184.2, 70, 8, 13, $this->barnType((string) ($data['project_type'] ?? ''))),
            $this->box(2, 31.5, 193.6, 70, 8, 13, $this->withUnit($data['length'] ?? null, 'متر')),
            $this->box(2, 33.6, 200.6, 70, 8, 13, $this->withUnit($data['width'] ?? null, 'متر')),
            $this->box(2, 33.0, 208.3, 70, 8, 13, $this->withUnit($data['height'] ?? null, 'متر')),
            $this->box(2, 34.4, 215.6, 70, 8, 13, trim((string) ($data['location'] ?? ''))),
            $this->box(10, 37.7, 121.4, 28, 6, 12, $this->grouped($data['total_nests'] ?? null)),
            $this->box(11, 122.3, 112.6, 32, 6, 11, $this->grouped($financial['barn_usd'] ?? null)),
            $this->box(11, 161.9, 112.6, 36, 6, 11, $this->grouped($financial['barn_egp'] ?? null)),
        ];
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
