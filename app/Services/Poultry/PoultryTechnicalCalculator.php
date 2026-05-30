<?php

namespace App\Services\Poultry;

use App\Enums\PoultryProjectType;
use InvalidArgumentException;

/**
 * Single source of truth for broiler/layer technical quantities (nests, birds, fans, cooling, inlets).
 */
class PoultryTechnicalCalculator
{
    public const DEFAULT_BROILER_WEIGHT_MAP = [
        '1.600' => 21,
        '1.850' => 18,
        '2.100' => 16,
        '2.650' => 13,
        '2.800' => 12,
    ];

    public const DEFAULT_WIDTH_LINES_MAP = [
        '12' => 4,
        '15' => 5,
        '16.5' => 6,
    ];

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function compute(array $input, array $config): array
    {
        $projectType = PoultryProjectType::from($input['project_type'] ?? PoultryProjectType::Broiler->value);

        $barnLength = (float) $input['barn_length'];
        $serviceLength = (float) ($input['service_length'] ?? $config['default_service_length'] ?? 0);

        if ($barnLength <= $serviceLength) {
            throw new InvalidArgumentException(
                "طول العنبر ({$barnLength}م) يجب أن يكون أكبر من منطقة الخدمات ({$serviceLength}م)"
            );
        }

        // result must be even — round up by 1 if odd (e.g. 81-10=71 → 72)
        $raw = $barnLength - $serviceLength;
        $effectiveLength = fmod($raw, 2) == 0 ? $raw : $raw + 1;
        $lines = (int) ($input['lines'] ?? $this->resolveLinesFromWidth((float) ($input['barn_width'] ?? 0), $config));
        $tiers = (int) $input['tiers'];

        if ($lines <= 0 || $tiers <= 0) {
            throw new InvalidArgumentException('عدد الخطوط والأدوار يجب أن يكون أكبر من صفر');
        }

        return match ($projectType) {
            PoultryProjectType::Broiler => $this->computeBroiler($input, $config, $barnLength, $effectiveLength, $lines, $tiers),
            PoultryProjectType::Layer => $this->computeLayer($input, $config, $barnLength, $effectiveLength, $lines, $tiers),
            PoultryProjectType::LayerRearing => throw new InvalidArgumentException('حاسبة تربية البياض غير مفعّلة بعد'),
        };
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $config
     */
    protected function computeBroiler(
        array $input,
        array $config,
        float $barnLength,
        float $effectiveLength,
        int $lines,
        int $tiers,
    ): array {
        $birdWeight = (float) ($input['bird_weight_kg'] ?? 2.1);
        $birdsPerNest = isset($input['birds_per_nest'])
            ? (int) $input['birds_per_nest']
            : $this->birdsPerNestFromWeight($birdWeight, $config);

        $nestsPerLine = (int) ($effectiveLength * 2 * $tiers);
        $totalNests = $nestsPerLine * $lines;
        $totalBirds = $totalNests * $birdsPerNest;

        $fanCapacity = (float) ($config['fan_capacity_kg'] ?? 5000);
        $ventilationWeight = $this->resolveBroilerVentilationMaxWeightKg($birdWeight, $config);
        $fanCalc = $this->exhaustFansFromBirds($totalBirds, $ventilationWeight, $fanCapacity);
        $mainFans = $fanCalc['fans_count'];
        $coolingPerFan = (float) ($config['cooling_pad_meters_per_fan'] ?? 5.5);
        $coolingPadLength = (int) ceil($mainFans * $coolingPerFan);

        $airWindows = $this->broilerAirWindows((int) round($barnLength));

        $sideFans = isset($input['side_fans_count'])
            ? (int) $input['side_fans_count']
            : $this->lookupRuleCount($config['side_fan_rules'] ?? [], $barnLength, $totalBirds);

        $heaters = isset($input['heaters_count']) && $input['heaters_count'] !== ''
            ? (int) $input['heaters_count']
            : 0;

        return [
            'project_type' => PoultryProjectType::Broiler->value,
            'barn_length' => $barnLength,
            'effective_length' => $effectiveLength,
            'lines' => $lines,
            'tiers' => $tiers,
            'bird_weight_kg' => $birdWeight,
            'birds_per_nest' => $birdsPerNest,
            'nests_per_line' => $nestsPerLine,
            'total_nests' => $totalNests,
            'total_birds' => $totalBirds,
            'main_fans_count' => $mainFans,
            'rear_fans_count' => $mainFans,
            'cooling_pad_length_m' => $coolingPadLength,
            'cooling_units' => $coolingPadLength,
            'cooling_formula' => sprintf('ceil(%d × %s) = %d م', $mainFans, rtrim(rtrim(number_format($coolingPerFan, 1, '.', ''), '0'), '.'), $coolingPadLength),
            'air_windows_count' => $airWindows,
            'side_fans_count' => $sideFans,
            'heaters_count' => $heaters,
            'include_side_fans' => true,
            'include_heaters' => true,
            'ventilation_max_weight_kg' => $ventilationWeight,
            'fan_capacity_kg' => $fanCapacity,
            'fan_load_kg' => $fanCalc['fan_load_kg'],
            'raw_main_fan_load' => $fanCalc['raw_load'],
            'fan_formula' => $fanCalc['formula'],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $config
     */
    protected function computeLayer(
        array $input,
        array $config,
        float $barnLength,
        float $effectiveLength,
        int $lines,
        int $tiers,
    ): array {
        $nestModule = (float) ($config['layer_nest_module_m'] ?? 0.60);
        $birdsPerNest = (int) ($input['birds_per_nest'] ?? $config['layer_birds_per_nest'] ?? 10);
        $layerMaxWeight = (float) ($config['layer_max_bird_weight_kg'] ?? 1.7);

        if ($nestModule <= 0) {
            throw new InvalidArgumentException('وحدة العش الطولية يجب أن تكون أكبر من صفر');
        }

        $nestsOneSide = (int) ($effectiveLength / $nestModule);
        $nestsPerLine = $nestsOneSide * 2 * $tiers;
        $totalNests = $nestsPerLine * $lines;
        $totalBirds = $totalNests * $birdsPerNest;

        $fanCapacity = (float) ($config['fan_capacity_kg'] ?? 5000);
        $fanCalc = $this->exhaustFansFromBirds($totalBirds, $layerMaxWeight, $fanCapacity);
        $rearFans = $fanCalc['fans_count'];
        $coolingPerFan = (float) ($config['cooling_pad_meters_per_fan'] ?? 5.5);
        $coolingPadLength = (int) ceil($rearFans * $coolingPerFan);

        $airWindows = null;
        if (isset($input['air_windows_count'])) {
            $airWindows = (int) $input['air_windows_count'];
        } elseif (! empty($config['layer_air_windows_formula']) && $config['layer_air_windows_formula'] === 'broiler_odd_even') {
            $airWindows = $this->broilerAirWindows((int) round($barnLength));
        }

        return [
            'project_type' => PoultryProjectType::Layer->value,
            'barn_length' => $barnLength,
            'effective_length' => $effectiveLength,
            'lines' => $lines,
            'tiers' => $tiers,
            'layer_nest_module_m' => $nestModule,
            'birds_per_nest' => $birdsPerNest,
            'nests_one_side' => $nestsOneSide,
            'nests_per_line' => $nestsPerLine,
            'total_nests' => $totalNests,
            'total_birds' => $totalBirds,
            'main_fans_count' => $rearFans,
            'rear_fans_count' => $rearFans,
            'cooling_pad_length_m' => $coolingPadLength,
            'cooling_units' => $coolingPadLength,
            'cooling_formula' => sprintf('ceil(%d × %s) = %d م', $rearFans, rtrim(rtrim(number_format($coolingPerFan, 1, '.', ''), '0'), '.'), $coolingPadLength),
            'air_windows_count' => $airWindows,
            'side_fans_count' => 0,
            'heaters_count' => 0,
            'include_side_fans' => false,
            'include_heaters' => false,
            'layer_max_bird_weight_kg' => $layerMaxWeight,
            'ventilation_max_weight_kg' => $layerMaxWeight,
            'fan_capacity_kg' => $fanCapacity,
            'fan_load_kg' => $fanCalc['fan_load_kg'],
            'raw_rear_fan_load' => $fanCalc['raw_load'],
            'fan_formula' => $fanCalc['formula'],
        ];
    }

    public function broilerAirWindows(int $barnLength): int
    {
        $adjusted = ($barnLength % 2 === 1)
            ? $barnLength - 3
            : $barnLength - 4;

        return (int) ($adjusted / 2);
    }

    /**
     * @param  array<string, int|float>  $weightMap
     */
    public function birdsPerNestFromWeight(float $weightKg, array $config): int
    {
        $map = $config['broiler_weight_birds_map'] ?? self::DEFAULT_BROILER_WEIGHT_MAP;
        $key = number_format($weightKg, 3, '.', '');
        $key = rtrim(rtrim($key, '0'), '.');

        foreach ($map as $w => $birds) {
            if (abs((float) $w - $weightKg) < 0.001) {
                return (int) $birds;
            }
        }

        $formatted = number_format($weightKg, 1, '.', '');
        if (isset($map[$formatted])) {
            return (int) $map[$formatted];
        }

        throw new InvalidArgumentException("لا يوجد تعيين معتمد لعدد الطيور عند الوزن {$weightKg} كجم");
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function resolveLinesFromWidth(float $width, array $config): int
    {
        $map = $config['width_lines_map'] ?? self::DEFAULT_WIDTH_LINES_MAP;
        $key = (string) $width;
        if (isset($map[$key])) {
            return (int) $map[$key];
        }

        $formatted = number_format($width, 1, '.', '');
        if (isset($map[$formatted])) {
            return (int) $map[$formatted];
        }

        return 0;
    }

    /**
     * @param  list<array<string, mixed>>  $rules
     */
    public function lookupRuleCount(array $rules, float $barnLength, int $totalBirds): int
    {
        foreach ($rules as $rule) {
            $minLen = isset($rule['min_barn_length']) ? (float) $rule['min_barn_length'] : null;
            $maxLen = isset($rule['max_barn_length']) ? (float) $rule['max_barn_length'] : null;
            $minBirds = isset($rule['min_birds']) ? (int) $rule['min_birds'] : null;
            $maxBirds = isset($rule['max_birds']) ? (int) $rule['max_birds'] : null;

            if ($minLen !== null && $barnLength < $minLen) {
                continue;
            }
            if ($maxLen !== null && $barnLength > $maxLen) {
                continue;
            }
            if ($minBirds !== null && $totalBirds < $minBirds) {
                continue;
            }
            if ($maxBirds !== null && $totalBirds > $maxBirds) {
                continue;
            }

            return (int) ($rule['count'] ?? 0);
        }

        return 0;
    }

    /**
     * إجمالي الشفاطات (رئيسية/خلفية) = تقريب لأعلى (عدد الطيور × أقصى وزن / سعة المروحة).
     *
     * @return array{fans_count: int, fan_load_kg: float, raw_load: float, formula: string}
     */
    public function exhaustFansFromBirds(int $totalBirds, float $maxBirdWeightKg, float $fanCapacityKg = 5000): array
    {
        if ($fanCapacityKg <= 0) {
            throw new InvalidArgumentException('سعة المروحة يجب أن تكون أكبر من صفر');
        }

        $fanLoadKg = $totalBirds * $maxBirdWeightKg;
        $raw = $fanLoadKg / $fanCapacityKg;
        $fansCount = (int) ceil($raw);

        return [
            'fans_count' => $fansCount,
            'fan_load_kg' => $fanLoadKg,
            'raw_load' => $raw,
            'formula' => sprintf(
                'ceil(%s × %s ÷ %s) = %d',
                number_format($totalBirds),
                rtrim(rtrim(number_format($maxBirdWeightKg, 3, '.', ''), '0'), '.'),
                number_format($fanCapacityKg, 0, '.', ''),
                $fansCount
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function resolveBroilerVentilationMaxWeightKg(float $selectedWeightKg, array $config): float
    {
        if (isset($config['broiler_max_bird_weight_kg'])) {
            return (float) $config['broiler_max_bird_weight_kg'];
        }

        return $selectedWeightKg;
    }

    /** @deprecated Use exhaustFansFromBirds() */
    public function broilerMainFansFromBirds(int $totalBirds, float $birdWeightKg, float $fanCapacityKg = 5000): array
    {
        $calc = $this->exhaustFansFromBirds($totalBirds, $birdWeightKg, $fanCapacityKg);

        return [
            'raw_main_fan_load' => $calc['raw_load'],
            'main_fans_count' => $calc['fans_count'],
        ];
    }

    /** @deprecated Use exhaustFansFromBirds() */
    public function layerRearFansFromBirds(int $totalBirds, float $layerMaxWeightKg = 1.7, float $fanCapacityKg = 5000): array
    {
        $calc = $this->exhaustFansFromBirds($totalBirds, $layerMaxWeightKg, $fanCapacityKg);

        return [
            'raw_rear_fan_load' => $calc['raw_load'],
            'rear_fans_count' => $calc['fans_count'],
            'cooling_pad_length_m' => $calc['fans_count'] * 5.5,
        ];
    }
}
