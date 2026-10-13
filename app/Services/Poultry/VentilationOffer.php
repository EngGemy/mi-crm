<?php

namespace App\Services\Poultry;

use App\Enums\PoultryProjectType;
use App\Models\PoultryQuotation;

/**
 * عرض التهوية: الشفاطات لأقرب رقم زوجي، التبريد = الشفاطات × 5.5،
 * وشبابيك الأير إنلت = الطول − 3 إذا كان فردياً أو − 4 إذا كان زوجياً.
 */
class VentilationOffer
{
    /** @var list<int> */
    public const SIDE_FAN_CHOICES = [4, 6, 8, 10];

    public function __construct(private PoultryTechnicalCalculator $calculator) {}

    public static function sideFanChoice(mixed $value): ?int
    {
        $count = (int) $value;

        return in_array($count, self::SIDE_FAN_CHOICES, true) ? $count : null;
    }

    /**
     * @return array<string, float>
     */
    public static function prices(): array
    {
        return [
            'broiler_weight' => (float) settings('poultry_pricing.ventilation_broiler_max_weight_kg', 2.1),
            'layer_weight' => (float) settings('poultry_pricing.layer_max_bird_weight_kg', 1.7),
            'fan_capacity' => (float) settings('poultry_pricing.fan_capacity_kg', 5000),
            'cooling_per_fan' => (float) settings('poultry_pricing.cooling_pad_meters_per_fan', 5.5),
            'fan_unit' => (float) settings('poultry_pricing.ventilation_fan_unit_price', 34000),
            'cooling_m2' => (float) settings('poultry_pricing.ventilation_cooling_m2_price', 4800),
            'circulation_unit' => (float) settings('poultry_pricing.ventilation_circulation_unit_price', 18500),
            'inlet_unit' => (float) settings('poultry_pricing.ventilation_inlet_unit_price', 2300),
            'heating_min' => (float) settings('poultry_pricing.ventilation_heating_min', 750000),
            'heating_max' => (float) settings('poultry_pricing.ventilation_heating_max', 1100000),
            'electricity_min' => (float) settings('poultry_pricing.ventilation_electricity_min', 750000),
            'electricity_max' => (float) settings('poultry_pricing.ventilation_electricity_max', 1000000),
            'monitor' => (float) settings('poultry_pricing.ventilation_monitor_price', 250000),
        ];
    }

    /**
     * @param  array<string, float>|null  $prices
     * @return array{
     *     fans: int,
     *     cooling_m2: float,
     *     inlets: int,
     *     rows: list<array{0: string, 1: string, 2: bool}>,
     *     document: array<string, array{qty: string, unit: string, amount: string}|string|null>
     * }
     */
    public function calculate(int $birds, int $barnLength, bool $broiler, ?int $sideFans, ?array $prices = null): array
    {
        $prices ??= self::prices();
        $weight = $broiler ? (float) $prices['broiler_weight'] : (float) $prices['layer_weight'];
        $capacity = (float) $prices['fan_capacity'];
        $perFan = (float) $prices['cooling_per_fan'];
        $fans = $this->calculator->nearestEvenFanCount($birds, $weight, $capacity);
        $cooling = round($fans * $perFan, 2);
        $inlets = $this->calculator->ventilationAirInlets($barnLength);
        $sideFans = $broiler ? self::sideFanChoice($sideFans) : null;

        $heatingMin = min((float) $prices['heating_min'], (float) $prices['heating_max']);
        $heatingMax = max((float) $prices['heating_min'], (float) $prices['heating_max']);
        $electricMin = min((float) $prices['electricity_min'], (float) $prices['electricity_max']);
        $electricMax = max((float) $prices['electricity_min'], (float) $prices['electricity_max']);

        $fanAmount = $fans * (float) $prices['fan_unit'];
        $coolingAmount = $cooling * (float) $prices['cooling_m2'];
        $inletAmount = $inlets * (float) $prices['inlet_unit'];
        $monitor = (float) $prices['monitor'];
        $circulationAmount = $sideFans === null ? 0.0 : $sideFans * (float) $prices['circulation_unit'];

        $fixed = $fanAmount + $coolingAmount + $inletAmount + $monitor + $circulationAmount;
        $totalMin = $fixed + $electricMin + ($broiler ? $heatingMin : 0);
        $totalMax = $fixed + $electricMax + ($broiler ? $heatingMax : 0);

        $weightText = self::plain($weight);
        $odd = $barnLength % 2 === 1;
        $rows = [
            ['شفاطات خلفية 140×140', $fans.' شفاط', false],
            ['المعادلة', number_format($birds).' × '.$weightText.' ÷ '.number_format($capacity, 0).' → '.$fans, false],
            ['التبريد', self::plain($cooling).' م²', false],
            ['شبابيك الأير إنلت', $inlets.' شباك — '.($odd ? 'الطول فردي، يُخصم 3' : 'الطول زوجي، يُخصم 4'), false],
        ];

        if ($broiler) {
            $rows[] = ['الشفاطات الجانبية', $sideFans === null ? 'اختار 4 أو 6 أو 8 أو 10' : $sideFans.' شفاط', false];
            $rows[] = ['منظومة التدفئة بالمياه الساخنة', self::range($heatingMin, $heatingMax), false];
        }

        $rows[] = ['الكهرباء والكنترول والإنارة', self::range($electricMin, $electricMax), false];
        $rows[] = ['جهاز المونيتور', self::money($monitor), false];
        $rows[] = ['إجمالي عرض التهوية', self::range($totalMin, $totalMax), false];

        return [
            'fans' => $fans,
            'cooling_m2' => $cooling,
            'inlets' => $inlets,
            'rows' => $rows,
            'document' => [
                'fans' => [
                    'qty' => (string) $fans,
                    'unit' => self::money((float) $prices['fan_unit']),
                    'amount' => self::money($fanAmount),
                ],
                'cooling' => [
                    'qty' => self::plain($cooling),
                    'unit' => self::money((float) $prices['cooling_m2']),
                    'amount' => self::money($coolingAmount),
                ],
                'heating' => $broiler ? [
                    'qty' => '1',
                    'unit' => self::range($heatingMin, $heatingMax),
                    'amount' => self::range($heatingMin, $heatingMax),
                ] : null,
                'circulation' => $sideFans === null ? null : [
                    'qty' => (string) $sideFans,
                    'unit' => self::money((float) $prices['circulation_unit']),
                    'amount' => self::money($circulationAmount),
                ],
                'inlets' => [
                    'qty' => (string) $inlets,
                    'unit' => self::money((float) $prices['inlet_unit']),
                    'amount' => self::money($inletAmount),
                ],
                'monitor' => [
                    'qty' => '1',
                    'unit' => self::money($monitor),
                    'amount' => self::money($monitor),
                ],
                'electricity' => [
                    'qty' => '1',
                    'unit' => self::range($electricMin, $electricMax),
                    'amount' => self::range($electricMin, $electricMax),
                ],
                'total' => self::range($totalMin, $totalMax),
            ],
        ];
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    public function forQuotation(PoultryQuotation $quotation): array
    {
        $type = PoultryProjectType::tryFrom((string) $quotation->project_type);
        $broiler = $type?->isBroiler() ?? true;
        $offer = $this->calculate(
            (int) $quotation->bird_count,
            (int) round((float) $quotation->length),
            $broiler,
            $quotation->ventilation_side_fans,
        );

        return [$this->barnLabel($type), $offer];
    }

    public function barnLabel(?PoultryProjectType $type): string
    {
        return match ($type) {
            PoultryProjectType::BroilerAutoExit => 'تسمين تخريج آلي',
            PoultryProjectType::Layer, PoultryProjectType::LayerAutoCollect, PoultryProjectType::LayerRearing => 'بياض',
            default => 'تسمين',
        };
    }

    public static function money(float $amount): string
    {
        return number_format(round($amount), 0, '.', ',').' ج.م';
    }

    public static function range(float $min, float $max): string
    {
        if (abs($min - $max) < 0.5) {
            return self::money($min);
        }

        return 'من '.number_format(round($min), 0, '.', ',').' إلى '.number_format(round($max), 0, '.', ',').' ج.م';
    }

    public static function plain(float $value): string
    {
        if (abs($value - round($value)) < 0.001) {
            return (string) (int) round($value);
        }

        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
