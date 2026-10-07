<?php

namespace App\Support;

use App\Models\Lookup;

/**
 * مواصفات البياض: القيمة الأساسية من قوائم الخيارات، والمعدل = نصفها.
 * الصفوف تُزرع من migration حتى تصل مع النشر، ومن الـ seeder للاختبارات.
 */
class LayerBaseLookups
{
    /** @return array<string, array{type: string, label: string, rate: bool, calc: string|null}> */
    public static function fields(): array
    {
        return [
            'service_id' => [
                'type' => Lookup::TYPE_LAYER_SERVICE,
                'label' => 'مساحة الخدمة',
                'rate' => false,
                'calc' => 'service',
            ],
            'birds_id' => [
                'type' => Lookup::TYPE_LAYER_BIRDS,
                'label' => 'عدد الطيور في العش',
                'rate' => false,
                'calc' => 'birds',
            ],
            'silo_side_id' => [
                'type' => Lookup::TYPE_LAYER_SILO_SIDE,
                'label' => 'جهة السايلو',
                'rate' => true,
                'calc' => null,
            ],
            'inner_belt_id' => [
                'type' => Lookup::TYPE_LAYER_INNER_BELT,
                'label' => 'طول السير الداخلي',
                'rate' => true,
                'calc' => null,
            ],
            'work_belt_id' => [
                'type' => Lookup::TYPE_LAYER_WORK_BELT,
                'label' => 'طول سير الشغل',
                'rate' => true,
                'calc' => null,
            ],
            'pedal_belt_id' => [
                'type' => Lookup::TYPE_LAYER_PEDAL_BELTS,
                'label' => 'عدد سير الدواسة',
                'rate' => true,
                'calc' => null,
            ],
            'cage_power_id' => [
                'type' => Lookup::TYPE_LAYER_CAGE_POWER,
                'label' => 'قدرة القفص',
                'rate' => true,
                'calc' => null,
            ],
            'egg_wheel_id' => [
                'type' => Lookup::TYPE_LAYER_EGG_WHEEL,
                'label' => 'عدد دورات دولاب البيض',
                'rate' => true,
                'calc' => null,
            ],
        ];
    }

    /**
     * @return array<string, list<array{code: string, label_ar: string, value: string, meta?: array<string, mixed>}>>
     */
    public static function groups(): array
    {
        return [
            Lookup::TYPE_LAYER_SERVICE => [
                self::row('8', '8 م²', '8', true, 'م²'),
            ],
            Lookup::TYPE_LAYER_BIRDS => [
                self::row('10', '10 طيور', '10', true, 'طائر'),
                self::row('9', '9 طيور', '9', false, 'طائر'),
            ],
            Lookup::TYPE_LAYER_SILO_SIDE => [
                self::row('17', '17 م', '17', false, 'م'),
                self::row('31', '31 م', '31', true, 'م'),
            ],
            Lookup::TYPE_LAYER_INNER_BELT => [
                self::row('11', '11 م', '11', true, 'م'),
                self::row('12', '12 م', '12', false, 'م'),
            ],
            Lookup::TYPE_LAYER_WORK_BELT => [
                self::row('12', '12 م', '12', true, 'م'),
                self::row('17', '17 م', '17', false, 'م'),
            ],
            Lookup::TYPE_LAYER_PEDAL_BELTS => [
                self::row('3', '3 سيور', '3', false, 'سير'),
                self::row('4', '4 سيور', '4', true, 'سير'),
                self::row('5', '5 سيور', '5', false, 'سير'),
            ],
            Lookup::TYPE_LAYER_CAGE_POWER => [
                self::row('1', '1', '1', true, null),
                self::row('3', '3', '3', false, null),
                self::row('5', '5', '5', false, null),
            ],
            Lookup::TYPE_LAYER_EGG_WHEEL => [
                self::row('50', '50 طائر', '50', true, 'طائر'),
            ],
        ];
    }

    /** @return array<string, int|null> */
    public static function defaultState(): array
    {
        $state = [];
        foreach (self::fields() as $key => $field) {
            $state[$key] = Lookup::defaultId($field['type']);
        }

        return $state;
    }

    public static function numericOf(mixed $id): ?float
    {
        if ($id === null || $id === '') {
            return null;
        }

        $value = Lookup::query()->whereKey($id)->value('value');
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    public static function defaultNumeric(string $type): ?float
    {
        return self::numericOf(Lookup::defaultId($type));
    }

    public static function rateFromBase(?float $base): ?float
    {
        if ($base === null) {
            return null;
        }

        return round($base / 2, 2);
    }

    public static function formatNumber(?float $number): string
    {
        if ($number === null) {
            return '—';
        }

        $formatted = number_format($number, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }

    /**
     * @param  array<string, mixed>|null  $specs
     * @return list<array{label: string, base: string, rate: string}>
     */
    public static function rateRows(?array $specs): array
    {
        $rows = [];
        foreach (self::fields() as $key => $field) {
            if (! $field['rate']) {
                continue;
            }

            $base = self::numericOf(is_array($specs) ? ($specs[$key] ?? null) : null);
            if ($base === null) {
                $base = self::defaultNumeric($field['type']);
            }

            $rows[] = [
                'label' => $field['label'],
                'base' => self::formatNumber($base),
                'rate' => self::formatNumber(self::rateFromBase($base)),
            ];
        }

        return $rows;
    }

    /** @return array{code: string, label_ar: string, value: string, meta: array<string, mixed>} */
    private static function row(string $code, string $label, string $value, bool $default, ?string $unit): array
    {
        return [
            'code' => $code,
            'label_ar' => $label,
            'value' => $value,
            'meta' => array_filter([
                'is_default' => $default,
                'unit' => $unit,
            ], fn ($item) => $item !== null && $item !== false),
        ];
    }
}
