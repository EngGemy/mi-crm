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
            'heating' => self::baseHeating(),
            'electricity' => self::baseElectricity(),
            'monitor' => self::baseMonitor(),
        ];
    }

    public static function baseHeating(): float
    {
        return (float) settings(
            'poultry_pricing.ventilation_heating_price',
            settings('poultry_pricing.ventilation_heating_min', 750000),
        );
    }

    public static function baseElectricity(): float
    {
        return (float) settings(
            'poultry_pricing.ventilation_electricity_price',
            settings('poultry_pricing.ventilation_electricity_min', 750000),
        );
    }

    public static function baseMonitor(): float
    {
        return (float) settings('poultry_pricing.ventilation_monitor_price', 250000);
    }

    /**
     * @param  array<string, float>|null  $prices
     * @param  array{heating?: float|int|string|null, electricity?: float|int|string|null, monitor?: float|int|string|null}|null  $overrides
     * @return array{
     *     fans: int,
     *     cooling_m2: float,
     *     inlets: int,
     *     rows: list<array{0: string, 1: string, 2: bool}>,
     *     document: array<string, array{qty: string, unit: string, amount: string}|string|null>
     * }
     */
    public function calculate(int $birds, int $barnLength, bool $broiler, ?int $sideFans, ?array $prices = null, ?array $overrides = null): array
    {
        $prices ??= self::prices();
        $weight = $broiler ? (float) $prices['broiler_weight'] : (float) $prices['layer_weight'];
        $capacity = (float) $prices['fan_capacity'];
        $perFan = (float) $prices['cooling_per_fan'];
        $fans = $this->calculator->nearestEvenFanCount($birds, $weight, $capacity);
        $cooling = round($fans * $perFan, 2);
        $inlets = $this->calculator->ventilationAirInlets($barnLength);
        $sideFans = $broiler ? self::sideFanChoice($sideFans) : null;
        $overrides ??= [];

        $heating = self::picked($overrides['heating'] ?? null, (float) ($prices['heating'] ?? $prices['heating_min'] ?? 0));
        $electricity = self::picked($overrides['electricity'] ?? null, (float) ($prices['electricity'] ?? $prices['electricity_min'] ?? 0));
        $monitor = self::picked($overrides['monitor'] ?? null, (float) ($prices['monitor'] ?? 0));

        $fanAmount = $fans * (float) $prices['fan_unit'];
        $coolingAmount = $cooling * (float) $prices['cooling_m2'];
        $inletAmount = $inlets * (float) $prices['inlet_unit'];
        $circulationAmount = $sideFans === null ? 0.0 : $sideFans * (float) $prices['circulation_unit'];
        $heatingAmount = $broiler ? $heating : 0.0;
        $total = $fanAmount + $coolingAmount + $inletAmount + $circulationAmount + $heatingAmount + $electricity + $monitor;

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
            $rows[] = ['منظومة التدفئة بالمياه الساخنة', self::money($heating), false];
        }

        $rows[] = ['الكهرباء والكنترول والإنارة', self::money($electricity), false];
        $rows[] = ['جهاز المونيتور', self::money($monitor), false];
        $rows[] = ['إجمالي المشتملات', self::money($total), false];

        $lines = [
            ['شفاطات طرد 140×140', (string) $fans, self::money((float) $prices['fan_unit']), self::money($fanAmount)],
            ['التبريد', self::plain($cooling).' م²', self::money((float) $prices['cooling_m2']), self::money($coolingAmount)],
            ['شبابيك الأير إنلت', (string) $inlets, self::money((float) $prices['inlet_unit']), self::money($inletAmount)],
        ];
        if ($sideFans !== null) {
            $lines[] = ['الشفاطات الجانبية', (string) $sideFans, self::money((float) $prices['circulation_unit']), self::money($circulationAmount)];
        }
        if ($broiler) {
            $lines[] = ['منظومة التدفئة بالمياه الساخنة', '1', self::money($heating), self::money($heating)];
        }
        $lines[] = ['الكهرباء والكنترول والإنارة', '1', self::money($electricity), self::money($electricity)];
        $lines[] = ['جهاز المونيتور', '1', self::money($monitor), self::money($monitor)];

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
                    'unit' => self::money($heating),
                    'amount' => self::money($heating),
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
                    'unit' => self::money($electricity),
                    'amount' => self::money($electricity),
                ],
                'total' => self::money($total),
            ],
            'ventilation_pdf' => [
                'lines' => $lines,
                'total' => self::money($total),
            ],
            'accessories_pdf' => [
                'lines' => $lines,
                'total' => self::money($total),
            ],
        ];
    }

    public static function picked(mixed $override, float $base): float
    {
        if ($override === null || $override === '') {
            return $base;
        }

        return (float) $override;
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
            null,
            [
                'heating' => $quotation->ventilation_heating_price,
                'electricity' => $quotation->ventilation_electricity_price,
                'monitor' => $quotation->ventilation_monitor_price,
            ],
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
