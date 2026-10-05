<?php

namespace App\Services\Poultry;

use App\Enums\PoultryProjectType;
use App\Models\PoultryQuotation;

/**
 * Page 9 of the branded proposal: battery specs, cage specs, and care scenarios.
 *
 * Quantities come from the quotation snapshot (frozen at save time). Cage
 * geometry and feeding-zone overrides stay brochure constants.
 */
class ProposalPage9Data
{
    public function stylesheet(string $primary): string
    {
        return <<<CSS
    .p9 { font-family: 'cairo', sans-serif; direction: rtl; color: #1a1a1a; }
    .p9 table { width: 100%; border-collapse: collapse; margin: 0 0 2.2mm 0; }
    .p9 .bar td {
        background: {$primary};
        color: #fff;
        font-weight: bold;
        font-size: 10.5pt;
        text-align: center;
        padding: 1.8mm 0;
        border: 0.4pt solid {$primary};
    }
    .p9 .bar-sel td { background: #8B0000; border-color: #8B0000; }
    .p9 .r td { border: 0.5pt solid #e0b4b4; padding: 1.55mm 3.5mm; font-size: 9.2pt; }
    .p9 .lbl { width: 62%; text-align: right; font-weight: bold; color: #1a1a1a; }
    .p9 .val { width: 38%; text-align: center; direction: ltr; color: #222; font-weight: bold; }
    .p9 .odd td { background: #faf1f1; }
    .p9 .even td { background: #fdf8f8; }
    .p9 .sel-row td { background: #fff0f0; border-color: {$primary}; }
    .p9 .note { text-align: center; font-size: 8pt; color: #666; margin-top: 1mm; }
CSS;
    }

    /** @return array{primary: string, sections: list<array<string, mixed>>} */
    public function from(PoultryQuotation $quotation): array
    {
        $snapshot = $quotation->pricing_snapshot ?? [];
        $technical = $snapshot['technical'] ?? [];
        $computed = $snapshot['computed'] ?? [];
        $specs = (array) config('mi_proposal.specs', []);

        $effectiveLength = (float) ($technical['effective_length'] ?? $computed['effective_length'] ?? 0);
        $tiers = (int) ($technical['tiers'] ?? $quotation->tiers ?? 0);
        $lines = (int) ($technical['lines'] ?? $quotation->lines ?? 0);
        $cagesTotal = (int) ($computed['total_nests'] ?? $technical['total_nests'] ?? $quotation->total_nests ?? 0);
        $cagesPerRow = $tiers > 0
            ? intdiv($cagesTotal, $tiers)
            : (int) round($effectiveLength * 2 * max($lines, 1));

        $cageLength = (float) ($specs['cage_length_cm'] ?? 100);
        $cageArea = (float) ($specs['cage_area_cm2'] ?? 6500);

        $sections = [
            $this->section('المواصفات الفنية للبطاريات', [
                ['الطول الفعال للبطارية', $this->amount($effectiveLength, 'متر')],
                ['عدد الأدوار', $this->count($tiers, 'أدوار')],
                ['عدد الخطوط', $this->count($lines, 'خطوط')],
                ['مسافة الانتقال بين الخطوط', $this->count((int) ($specs['transfer_distance_cm'] ?? 102), 'سم')],
                ['ارتفاع البطارية', $this->fixed((float) ($specs['battery_height_m'] ?? 3.4), 2, 'متر')],
                ['عدد الأقفاص بالصف الواحد', $this->count($cagesPerRow, 'قفص')],
                ['عدد الأقفاص بالعنبر', $this->count($cagesTotal, 'قفص')],
            ], 'battery'),
            $this->section('المواصفات الفنية للقفص', [
                ['طول القفص', $this->count((int) $cageLength, 'سم')],
                ['عمق القفص', $this->count((int) ($specs['cage_depth_cm'] ?? 65), 'سم')],
                ['ارتفاع القفص', $this->count((int) ($specs['cage_height_cm'] ?? 45), 'سم')],
                ['مساحة القفص', $this->count((int) $cageArea, 'سم²')],
            ], 'cage'),
        ];

        foreach ($this->careSections($quotation, $snapshot, $cagesTotal, $cageArea, $cageLength) as $care) {
            $sections[] = $care;
        }

        return [
            'primary' => (string) config('mi_proposal.primary_color', '#C00000'),
            'sections' => $sections,
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return list<array<string, mixed>>
     */
    private function careSections(
        PoultryQuotation $quotation,
        array $snapshot,
        int $cagesTotal,
        float $cageArea,
        float $cageLength,
    ): array {
        $technical = $snapshot['technical'] ?? [];
        $computed = $snapshot['computed'] ?? [];
        $projectType = (string) ($snapshot['project_type'] ?? $technical['project_type'] ?? $quotation->project_type ?? PoultryProjectType::Broiler->value);

        $snapshotBirds = (int) ($technical['birds_per_nest'] ?? $quotation->birds_per_nest ?? 0);
        $snapshotTotal = (int) ($computed['bird_count'] ?? $quotation->bird_count ?? 0);

        if (! PoultryProjectType::tryFrom($projectType)?->isBroiler()) {
            $weight = (float) ($technical['layer_max_bird_weight_kg'] ?? $technical['bird_weight_kg'] ?? 0);

            return $this->singleCare($weight, $snapshotBirds, $snapshotTotal, $cagesTotal, $cageArea, $cageLength);
        }

        $selectedWeight = (float) ($technical['bird_weight_kg'] ?? $quotation->bird_weight_kg ?? 0);
        $rows = $this->weightRows($snapshot);
        if ($rows === []) {
            return $this->singleCare($selectedWeight, $snapshotBirds, $snapshotTotal, $cagesTotal, $cageArea, $cageLength);
        }

        $rows = $this->withSelectedWeight($rows, $selectedWeight, $snapshotBirds);

        $selectedIndex = 0;
        $nearest = PHP_FLOAT_MAX;
        foreach ($rows as $i => $row) {
            $diff = abs($row['weight'] - $selectedWeight);
            if ($diff < $nearest) {
                $nearest = $diff;
                $selectedIndex = $i;
            }
        }

        $start = max(0, min($selectedIndex - 1, count($rows) - 3));
        $window = array_slice($rows, $start, 3);
        $feedingMap = (array) config('mi_proposal.feeding_zone_cm', []);

        $sections = [];
        foreach ($window as $row) {
            $isSelected = abs($row['weight'] - $selectedWeight) < 0.001;

            $birds = (int) $row['birds'];
            $total = $cagesTotal * $birds;
            if ($isSelected && $snapshotBirds > 0) {
                $birds = $snapshotBirds;
                $total = $snapshotTotal > 0 ? $snapshotTotal : $cagesTotal * $birds;
            }
            if ($birds <= 0) {
                continue;
            }

            $sections[] = $this->careSection($row['weight'], $birds, $total, $cageArea, $cageLength, $feedingMap, $isSelected);
        }

        return $sections;
    }

    /** @return list<array<string, mixed>> */
    private function singleCare(
        float $weight,
        int $birds,
        int $total,
        int $cagesTotal,
        float $cageArea,
        float $cageLength,
    ): array {
        if ($birds <= 0) {
            return [];
        }

        $total = $total > 0 ? $total : $cagesTotal * $birds;

        return [
            $this->careSection(
                $weight,
                $birds,
                $total,
                $cageArea,
                $cageLength,
                (array) config('mi_proposal.feeding_zone_cm', []),
                true,
            ),
        ];
    }

    /**
     * @param  array<int|string, int|float|string>  $feedingMap
     * @return array<string, mixed>
     */
    private function careSection(
        float $weight,
        int $birds,
        int $total,
        float $cageArea,
        float $cageLength,
        array $feedingMap,
        bool $selected,
    ): array {
        $weightLabel = $weight > 0 ? $this->fixed($weight, 3, 'كجم') : '';
        $title = $weight > 0
            ? 'الرعاية لكل طائر على متوسط وزن '.$weightLabel
            : 'الرعاية لكل طائر';
        if ($selected) {
            $title .= ' — الوزن المختار';
        }

        $feeding = isset($feedingMap[$birds])
            ? (float) $feedingMap[$birds]
            : round($cageLength / $birds, 2);

        $housingLabel = $weight > 0
            ? 'عدد القطع بالعنبر لوزن '.$weightLabel
            : 'عدد القطع بالعنبر';

        return $this->section($title, [
            ['عدد القطع في القفص الواحد', $this->count($birds, 'طائر')],
            [$housingLabel, $this->count($total, 'طائر')],
            ['مساحة التسكين لكل دجاجة', $this->count((int) round($cageArea / $birds), 'سم²')],
            ['منطقة التغذية لكل دجاجة', $this->amount($feeding, 'سم')],
        ], 'care', $selected);
    }

    /**
     * @param  list<array{weight: float, birds: int}>  $rows
     * @return list<array{weight: float, birds: int}>
     */
    private function withSelectedWeight(array $rows, float $selectedWeight, int $snapshotBirds): array
    {
        if ($selectedWeight <= 0 || $snapshotBirds <= 0) {
            return $rows;
        }

        foreach ($rows as $row) {
            if (abs($row['weight'] - $selectedWeight) < 0.001) {
                return $rows;
            }
        }

        $rows[] = ['weight' => $selectedWeight, 'birds' => $snapshotBirds];
        usort($rows, fn (array $a, array $b) => $a['weight'] <=> $b['weight']);

        return $rows;
    }

    /**
     * Frozen weight map from the snapshot, otherwise the calculator default.
     *
     * @param  array<string, mixed>  $snapshot
     * @return list<array{weight: float, birds: int}>
     */
    private function weightRows(array $snapshot): array
    {
        $map = $snapshot['parameters']['broiler_weight_birds_map'] ?? null;
        if (! is_array($map) || $map === []) {
            $map = PoultryTechnicalCalculator::DEFAULT_BROILER_WEIGHT_MAP;
        }

        $rows = [];
        foreach ($map as $weight => $birds) {
            $birds = (int) $birds;
            if ($birds <= 0) {
                continue;
            }
            $rows[] = [
                'weight' => (float) $weight,
                'birds' => $birds,
            ];
        }

        usort($rows, fn (array $a, array $b) => $a['weight'] <=> $b['weight']);

        return $rows;
    }

    /**
     * @param  list<array{0: string, 1: string}>  $pairs
     * @return array<string, mixed>
     */
    private function section(string $title, array $pairs, string $kind, bool $highlight = false): array
    {
        return [
            'title' => $title,
            'kind' => $kind,
            'highlight' => $highlight,
            'rows' => array_map(
                fn (array $pair) => ['label' => $pair[0], 'value' => $pair[1]],
                $pairs,
            ),
        ];
    }

    private function count(int|float $n, string $unit): string
    {
        return number_format((float) $n, 0).' '.$unit;
    }

    private function fixed(float $n, int $decimals, string $unit): string
    {
        return number_format($n, $decimals, '.', '').' '.$unit;
    }

    private function amount(float $n, string $unit): string
    {
        $formatted = rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');

        return ($formatted === '' ? '0' : $formatted).' '.$unit;
    }
}
