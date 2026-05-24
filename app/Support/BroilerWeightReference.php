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

        $html = '<div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse;font-size:13px;text-align:center">';
        $html .= '<thead><tr style="background:#C00000;color:#fff">';
        $html .= '<th style="padding:8px;border:1px solid #fecaca">وزن الطائر (كجم)</th>';
        $html .= '<th style="padding:8px;border:1px solid #fecaca">عدد الطيور / عش</th>';
        if ($showTotal) {
            $html .= '<th style="padding:8px;border:1px solid #fecaca">إجمالي الطيور</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $isSelected = $selectedWeight !== null && abs($row['weight_float'] - $selectedWeight) < 0.001;
            $style = $isSelected ? 'background:#fef2f2;font-weight:700;color:#991b1b' : '';
            $html .= '<tr style="'.$style.'">';
            $html .= '<td style="padding:8px;border:1px solid #e2e8f0;direction:ltr">'.$row['weight_kg'].'</td>';
            $html .= '<td style="padding:8px;border:1px solid #e2e8f0">'.number_format($row['birds_per_nest']).' طائر</td>';
            if ($showTotal) {
                $html .= '<td style="padding:8px;border:1px solid #e2e8f0;direction:ltr;font-weight:600">'
                    .number_format($totalNests * $row['birds_per_nest']).'</td>';
            }
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
}
