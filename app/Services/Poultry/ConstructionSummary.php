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
     * ثلاثة بنود: أرضية خرسانية، جملون وحوائط، خزانات.
     * المساحة في البندين الأولين = الطول × العرض.
     *
     * @return array{
     *     concrete: array{qty: string, unit: string, amount: string},
     *     shell: array{qty: string, unit: string, amount: string},
     *     tanks: array{qty: string, unit: string, amount: string},
     *     total: string
     * }
     */
    public function brochure(PoultryQuotation $quotation): array
    {
        return $this->lines(
            (float) $quotation->length,
            (float) $quotation->width,
            [
                'concrete' => VentilationOffer::picked($quotation->construction_concrete_price, self::baseConcrete()),
                'shell' => $this->shellRate($quotation),
                'tanks' => (float) settings('poultry_pricing.tanks_fixed_cost', 400000),
            ],
        );
    }

    /**
     * @param  array{concrete: float, shell: float, tanks: float}  $rates
     * @return array{
     *     concrete: array{qty: string, unit: string, amount: string},
     *     shell: array{qty: string, unit: string, amount: string},
     *     tanks: array{qty: string, unit: string, amount: string},
     *     total: string
     * }
     */
    public function lines(float $length, float $width, array $rates): array
    {
        $floor = max(0, (int) round($length * $width));
        $concreteAmount = $floor * (float) $rates['concrete'];
        $shellAmount = $floor * (float) $rates['shell'];
        $tanks = (float) $rates['tanks'];
        $sum = $concreteAmount + $shellAmount + $tanks;

        return [
            'concrete' => $this->moneyLine((string) $floor, (float) $rates['concrete'], $concreteAmount),
            'shell' => $this->moneyLine((string) $floor, (float) $rates['shell'], $shellAmount),
            'tanks' => $this->moneyLine('1', $tanks, $tanks),
            'total' => VentilationOffer::money($sum),
        ];
    }

    public static function baseConcrete(): float
    {
        return (float) settings('poultry_pricing.concrete_cost_per_m2', 1200);
    }

    public static function baseShell(): float
    {
        return (float) settings('poultry_pricing.construction_shell_price', 3350);
    }

    private function shellRate(PoultryQuotation $quotation): float
    {
        $parsed = VentilationOffer::parseMoney($quotation->construction_price);
        if ($parsed === null || $parsed <= 0 || $parsed > 50000) {
            return self::baseShell();
        }

        return $parsed;
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
