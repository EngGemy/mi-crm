<?php

namespace App\Support;

use App\Services\Poultry\PoultryConfigLoader;
use App\Services\Poultry\PoultryTechnicalCalculator;
use Illuminate\Support\HtmlString;

/**
 * جدول الوزن المعتمد → عدد الطيور لكل عش (تسمين).
 */
class BroilerWeightReference
{
    /**
     * @return list<array{weight_kg: string, weight_float: float, birds_per_nest: int}>
     */
    public static function rows(?array $map = null): array
    {
        $map = $map ?? self::mapFromSettings();

        $rows = [];
        foreach ($map as $weight => $birds) {
            $rows[] = [
                'weight_kg' => (string) $weight,
                'weight_float' => (float) $weight,
                'birds_per_nest' => (int) $birds,
            ];
        }

        usort($rows, fn ($a, $b) => $a['weight_float'] <=> $b['weight_float']);

        return $rows;
    }

    public static function birdsPerNest(float $weightKg, ?array $map = null): int
    {
        return (new PoultryTechnicalCalculator)->birdsPerNestFromWeight(
            $weightKg,
            ['broiler_weight_birds_map' => $map ?? self::mapFromSettings()]
        );
    }

    /** @return array<string, int|float> */
    public static function mapFromSettings(): array
    {
        return (new PoultryConfigLoader)->loadTechnicalConfig()['broiler_weight_birds_map']
            ?? PoultryTechnicalCalculator::DEFAULT_BROILER_WEIGHT_MAP;
    }

    public static function htmlTable(?float $selectedWeight = null, ?int $totalNests = null): HtmlString
    {
        $rows = self::rows();
        $showTotal = $totalNests !== null && $totalNests > 0;
        $selectedBirds = $selectedWeight !== null ? self::birdsPerNest($selectedWeight) : null;

        $html = '<div style="overflow-x:auto;margin-top:4px">';
        $html .= '<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:10px 12px;margin-bottom:10px;font-size:12px;color:#1e3a8a;line-height:1.6">';
        $html .= '<strong>ملاحظة السعة:</strong> عدد الطيور يعتمد على وزن الطائر المستهدف. ';
        if ($selectedWeight !== null && $selectedBirds) {
            $html .= 'الوزن الحالي <strong dir="ltr">'.e(number_format($selectedWeight, 3)).' كجم</strong> = ';
            $html .= '<strong>'.e(number_format($selectedBirds)).' طائر/عش</strong>';
            if ($showTotal) {
                $html .= ' ← إجمالي السعة <strong>'.e(number_format($totalNests * $selectedBirds)).' طائر</strong>';
                $html .= ' <span dir="ltr">('.e(number_format($totalNests)).' × '.e(number_format($selectedBirds)).')</span>';
            }
        }
        $html .= '</div>';

        $html .= '<table style="width:100%;border-collapse:collapse;font-size:13px;text-align:center">';
        $html .= '<thead><tr style="background:#1e40af;color:#fff">';
        $html .= '<th style="padding:10px;border:1px solid #93c5fd">وزن الطائر (كجم)</th>';
        $html .= '<th style="padding:10px;border:1px solid #93c5fd">عدد الطيور / عش</th>';
        if ($showTotal) {
            $html .= '<th style="padding:10px;border:1px solid #93c5fd">إجمالي الطيور ('.e(number_format($totalNests)).' عش)</th>';
        }
        $html .= '<th style="padding:10px;border:1px solid #93c5fd">الحالة</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $isSelected = $selectedWeight !== null && abs($row['weight_float'] - $selectedWeight) < 0.001;
            $style = $isSelected ? 'background:#dbeafe;font-weight:700;color:#1e3a8a' : '';
            $html .= '<tr style="'.$style.'">';
            $html .= '<td style="padding:8px;border:1px solid #e2e8f0;direction:ltr">'.e($row['weight_kg']).'</td>';
            $html .= '<td style="padding:8px;border:1px solid #e2e8f0">'.number_format($row['birds_per_nest']).' طائر</td>';
            if ($showTotal) {
                $html .= '<td style="padding:8px;border:1px solid #e2e8f0;direction:ltr;font-weight:600">'
                    .number_format($totalNests * $row['birds_per_nest']).'</td>';
            }
            $html .= '<td style="padding:8px;border:1px solid #e2e8f0;font-size:12px">'
                .($isSelected ? '<span style="background:#1e40af;color:#fff;padding:3px 8px;border-radius:999px">المُطبَّق</span>' : 'بديل')
                .'</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';

        return new HtmlString($html);
    }

    /** @return array<string, string> */
    public static function selectOptions(): array
    {
        $options = [];
        foreach (self::rows() as $row) {
            $options[$row['weight_kg']] = $row['weight_kg'].' كجم — '.$row['birds_per_nest'].' طائر/عش';
        }

        return $options;
    }

    /** HTML table for PDF (mPDF-safe). */
    public static function pdfTableHtml(?float $selectedWeight = null, ?int $totalNests = null): string
    {
        $rows = self::rows();
        $showTotal = $totalNests !== null && $totalNests > 0;
        $primary = '#1e40af';

        $html = '<table style="width:100%;border-collapse:collapse;font-size:9pt;margin:3mm 0;">';
        $html .= '<thead><tr>';
        $html .= '<th style="background:'.$primary.';color:#fff;padding:2.2mm;border:0.5pt solid '.$primary.';">وزن الطائر (كجم)</th>';
        $html .= '<th style="background:'.$primary.';color:#fff;padding:2.2mm;border:0.5pt solid '.$primary.';">عدد الطيور / عش</th>';
        if ($showTotal) {
            $html .= '<th style="background:'.$primary.';color:#fff;padding:2.2mm;border:0.5pt solid '.$primary.';">إجمالي الطيور</th>';
        }
        $html .= '<th style="background:'.$primary.';color:#fff;padding:2.2mm;border:0.5pt solid '.$primary.';">الحالة</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $sel = $selectedWeight !== null && abs($row['weight_float'] - $selectedWeight) < 0.001;
            $bg = $sel ? 'background:#dbeafe;font-weight:bold;color:#1e3a8a;' : '';
            $html .= '<tr>';
            $html .= '<td style="padding:2mm;border:0.5pt solid #ddd;text-align:center;direction:ltr;'.$bg.'">'.$row['weight_kg'].'</td>';
            $html .= '<td style="padding:2mm;border:0.5pt solid #ddd;text-align:center;'.$bg.'">'.number_format($row['birds_per_nest']).' طائر</td>';
            if ($showTotal) {
                $html .= '<td style="padding:2mm;border:0.5pt solid #ddd;text-align:center;direction:ltr;'.$bg.'">'
                    .number_format($totalNests * $row['birds_per_nest']).'</td>';
            }
            $html .= '<td style="padding:2mm;border:0.5pt solid #ddd;text-align:center;'.$bg.'">'.($sel ? 'المُطبَّق' : '—').'</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        return $html;
    }
}
