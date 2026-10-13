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

            if ($page === 6) {
                $this->paintBelts($mpdf, $data);
                $this->paintManureBeltCount($mpdf, $data);
            }

            if ($page === 8) {
                $this->paintSiloCapacityLine($mpdf, $data);
            }

            if ($page === 10) {
                $this->paintTiers($mpdf, $data);
                $this->paintNestCount($mpdf, $data);
            }

            if ($page === 11) {
                $this->paintCustomer($mpdf, $data);
                $this->paintBarnAmounts($mpdf, $data);
            }
        }

        return $mpdf->Output('', 'S');
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

    /** @param  array<string, mixed>  $data */
    private function paintBelts(Mpdf $mpdf, array $data): void
    {
        $this->paintBeltNumber($mpdf, 63.8, 214.6, 6.4, 6.2, (string) ($data['inner_belt'] ?? ''), 14, 'C');
        $this->paintBeltNumber($mpdf, 25.05, 214.6, 4.3, 6.2, (string) ($data['outer_belt'] ?? ''), 12, 'R');
    }

    private function paintBeltNumber(Mpdf $mpdf, float $x, float $y, float $w, float $h, string $label, int $size, string $align): void
    {
        if (! preg_match('/(\d+(?:[.,]\d+)?)/u', $label, $match)) {
            return;
        }

        $this->paintRedNumber($mpdf, $x, $y, $w, $h, $match[1], [247, 237, 236], $size, $align);
    }

    /** عدد سيور السبلة يساوي عدد الأدوار. */
    private function paintManureBeltCount(Mpdf $mpdf, array $data): void
    {
        $tiers = $data['tiers'] ?? null;
        if (! is_numeric($tiers) || (int) $tiers <= 0) {
            return;
        }

        $this->paintRedNumber($mpdf, 185.7, 146.7, 3.5, 6.1, (string) (int) $tiers, [246, 228, 228], 13, 'C');
    }

    /** يستبدل 11 في «سعة السايلو 11 طن» بالسعة المختارة. */
    private function paintSiloCapacityLine(Mpdf $mpdf, array $data): void
    {
        $label = trim((string) ($data['silo_capacity'] ?? ''));
        if (! preg_match('/(\d+)/u', $label, $match)) {
            return;
        }

        $this->paintRedNumber($mpdf, 157.3, 64.0, 6.9, 6.5, $match[1], [247, 237, 236], 14, 'C');
    }

    /** @param  array{0: int, 1: int, 2: int}  $fill */
    private function paintRedNumber(Mpdf $mpdf, float $x, float $y, float $w, float $h, string $text, array $fill, int $size, string $align): void
    {
        if ($text === '') {
            return;
        }

        $mpdf->SetFillColor($fill[0], $fill[1], $fill[2]);
        $mpdf->Rect($x, $y, $w, $h, 'F');
        $mpdf->SetFont('cairo', '', $size);
        $mpdf->SetTextColor(255, 0, 0);
        $mpdf->SetXY($x, $y + 0.55);
        $mpdf->WriteCell($w, $h - 0.9, $text, 0, 0, $align);
    }

    /** @param  array<string, mixed>  $data */
    private function paintTiers(Mpdf $mpdf, array $data): void
    {
        $tiers = $data['tiers'] ?? null;
        if (! is_numeric($tiers) || (int) $tiers <= 0) {
            return;
        }

        $this->paintValue($mpdf, 46.2, 85.0, 8.5, 6.0, 12, (string) (int) $tiers, [247, 247, 247]);
    }

    /** @param  array<string, mixed>  $data */
    private function paintNestCount(Mpdf $mpdf, array $data): void
    {
        $this->paintValue($mpdf, 41.8, 122.4, 16.5, 6.3, 12, $this->grouped($data['total_nests'] ?? null), [255, 255, 255]);
    }

    /** @param  array{0: int, 1: int, 2: int}  $fill */
    private function paintValue(Mpdf $mpdf, float $x, float $y, float $w, float $h, int $size, string $text, array $fill): void
    {
        if ($text === '') {
            return;
        }

        $mpdf->SetFillColor($fill[0], $fill[1], $fill[2]);
        $mpdf->Rect($x, $y, $w, $h, 'F');
        $mpdf->SetFont('cairo', 'B', $size);
        $mpdf->SetTextColor(21, 21, 21);
        $mpdf->SetXY($x, $y + 0.55);
        $mpdf->WriteCell($w, $h - 1.0, $text, 0, 0, 'L');
    }

    /** @param  array<string, mixed>  $data */
    private function paintBarnAmounts(Mpdf $mpdf, array $data): void
    {
        $financial = is_array($data['financial'] ?? null) ? $data['financial'] : [];
        $this->paintAmount($mpdf, 128.2, 114.7, 24.0, 4.0, $this->grouped($financial['barn_usd'] ?? null));
        $this->paintAmount($mpdf, 166.8, 114.7, 26.0, 4.0, $this->grouped($financial['barn_egp'] ?? null));
    }

    private function paintAmount(Mpdf $mpdf, float $x, float $y, float $w, float $h, string $text): void
    {
        if ($text === '') {
            return;
        }

        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($x, $y, $w, $h, 'F');
        $mpdf->SetFont('cairo', 'B', 11);
        $mpdf->SetTextColor(21, 21, 21);
        $mpdf->SetXY($x, $y + 0.15);
        $mpdf->WriteCell($w, $h - 0.55, $text, 0, 0, 'C');
    }

    /** @param  array<string, mixed>  $data */
    private function paintCustomer(Mpdf $mpdf, array $data): void
    {
        $client = trim((string) ($data['client_name'] ?? ''));
        $this->paintRedValue($mpdf, 81.4, 56.4, 37.2, 10.4, 22, $client, 108.0);

        $date = trim((string) ($data['issued_at'] ?? ''));
        if ($date !== '') {
            $this->paintRedValue($mpdf, 47.5, 66.7, 42.0, 10.4, 22, $date, 90.0);
        }
    }

    /**
     * يغطي قيمة حمراء ثابتة في القالب ويكتب القيمة الفعلية بنفس المقاس والمحاذاة.
     */
    private function paintRedValue(Mpdf $mpdf, float $x, float $y, float $w, float $h, int $size, string $text, float $maxW): void
    {
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($x, $y, max($w, 4), $h, 'F');
        if ($text === '') {
            return;
        }

        $mpdf->SetTextColor(255, 0, 0);
        $mpdf->SetFont('cairo', 'B', $size);
        $width = $mpdf->GetStringWidth($text);
        while ($size > 12 && $width > $maxW) {
            $size--;
            $mpdf->SetFont('cairo', 'B', $size);
            $width = $mpdf->GetStringWidth($text);
        }

        $mpdf->SetXY($x + 0.4, $y + 1.15);
        $mpdf->WriteCell(min($maxW, max($width + 1.2, 8)), $h - 2.2, $text, 0, 0, 'L');
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
