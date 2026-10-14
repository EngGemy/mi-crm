<?php

namespace App\Services\Poultry;

use App\Models\PoultryQuotation;

/**
 * تكلفة الإنشاءات مبلغ ثابت من الإعدادات: ساندوتش بانل أو خرسانة.
 */
class ConstructionSummary
{
    /**
     * @param  array{sandwich: float, cement: float}|null  $prices
     * @return array{rows: list<array{0: string, 1: string, 2: bool}>, total: float, message: string, choice: string, lines: list<array{0: string, 1: string, 2: string, 3: string}>}
     */
    public function chosen(string $choice, ?array $prices = null, mixed $override = null): array
    {
        $prices ??= self::prices();
        $choice = $choice === 'cement' ? 'cement' : 'sandwich';
        $label = $choice === 'cement' ? 'خرسانة' : 'ساندوتش بانل';
        $amount = round(VentilationOffer::picked($override, (float) $prices[$choice]));
        $money = VentilationOffer::money($amount);

        return [
            'rows' => [
                ['نوع الإنشاءات', $label, false],
                ['تكلفة الإنشاءات', $money, false],
            ],
            'total' => $amount,
            'choice' => $choice,
            'message' => 'عرض الإنشاءات ('.$label.'): '.$money,
            'lines' => [
                ['إنشاءات '.$label, '1', $money, $money],
            ],
        ];
    }

    /**
     * @return array{sandwich: float, cement: float}
     */
    public static function prices(): array
    {
        return [
            'sandwich' => (float) settings('poultry_pricing.construction_sandwich_price', 0),
            'cement' => (float) settings('poultry_pricing.construction_cement_price', 0),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $prices = self::prices();

        return [
            'sandwich' => 'ساندوتش بانل — '.VentilationOffer::money($prices['sandwich']),
            'cement' => 'خرسانة — '.VentilationOffer::money($prices['cement']),
        ];
    }

    /**
     * @return array{rows: list<array{0: string, 1: string, 2: bool}>, total: float, message: string, choice: string}
     */
    public function forQuotation(PoultryQuotation $quotation): array
    {
        return $this->chosen(
            (string) ($quotation->construction_choice ?: 'sandwich'),
            null,
            $quotation->construction_price,
        );
    }

    /**
     * بنود جدول عرض الإنشاءات: المساحات من أبعاد العنبر، والأسعار من الإعدادات.
     * لو المندوب كتب رقماً داخل العرض يبقى هو الإجمالي، وفرق السعر يتسجل على بند الاستيل.
     *
     * @return array{
     *     concrete: array{qty: string, unit: string, amount: string},
     *     steel: array{qty: string, unit: string, amount: string},
     *     walls: array{qty: string, unit: string, amount: string},
     *     tanks: array{qty: string, unit: string, amount: string},
     *     total: string
     * }
     */
    public function brochure(PoultryQuotation $quotation): array
    {
        $choice = $quotation->construction_choice === 'cement' ? 'cement' : 'sandwich';

        return $this->lines(
            (float) $quotation->length,
            (float) $quotation->width,
            (float) $quotation->height,
            [
                'concrete' => (float) settings('poultry_pricing.concrete_cost_per_m2', 1200),
                'steel' => (float) settings('poultry_pricing.steel_cost_per_m2', 2450),
                'wall' => $this->wallRate($choice),
                'tanks' => (float) settings('poultry_pricing.tanks_fixed_cost', 480000),
            ],
            $quotation->construction_price,
        );
    }

    /**
     * @param  array{concrete: float, steel: float, wall: float, tanks: float}  $rates
     * @return array{
     *     concrete: array{qty: string, unit: string, amount: string},
     *     steel: array{qty: string, unit: string, amount: string},
     *     walls: array{qty: string, unit: string, amount: string},
     *     tanks: array{qty: string, unit: string, amount: string},
     *     total: string
     * }
     */
    public function lines(float $length, float $width, float $height, array $rates, mixed $override = null): array
    {
        $floor = max(0, (int) round($length * $width));
        $wallsQty = max(0, (int) round($length * $height * 2));
        $concreteAmount = $floor * (float) $rates['concrete'];
        $steelAmount = $floor * (float) $rates['steel'];
        $wallsAmount = $wallsQty * (float) $rates['wall'];
        $tanks = (float) $rates['tanks'];
        $sum = $concreteAmount + $steelAmount + $wallsAmount + $tanks;
        $target = ($override === null || $override === '' || (float) $override <= 0) ? $sum : (float) $override;
        $steelAmount += $target - $sum;
        $steelRate = $floor > 0 ? $steelAmount / $floor : $steelAmount;

        return [
            'concrete' => $this->moneyLine((string) $floor, (float) $rates['concrete'], $concreteAmount),
            'steel' => $this->moneyLine((string) $floor, $steelRate, $steelAmount),
            'walls' => $this->moneyLine((string) $wallsQty, (float) $rates['wall'], $wallsAmount),
            'tanks' => $this->moneyLine('7', $tanks, $tanks),
            'total' => VentilationOffer::money($target),
        ];
    }

    private function wallRate(string $choice): float
    {
        $raw = settings('poultry_pricing.wall_type_rates', null);
        $rates = is_array($raw) ? $raw : (is_string($raw) ? json_decode($raw, true) : null);
        if (! is_array($rates)) {
            $rates = ['sandwich' => 1200, 'cement' => 2000];
        }

        return (float) ($rates[$choice] ?? ($choice === 'cement' ? 2000 : 1200));
    }

    /**
     * @return array{qty: string, unit: string, amount: string}
     */
    private function moneyLine(string $qty, float $unit, float $amount): array
    {
        return [
            'qty' => $qty,
            'unit' => VentilationOffer::money($unit),
            'amount' => VentilationOffer::money($amount),
        ];
    }
}
