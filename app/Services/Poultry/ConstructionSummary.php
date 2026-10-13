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
     * @return array{rows: list<array{0: string, 1: string, 2: bool}>, total: float, message: string, choice: string}
     */
    public function chosen(string $choice, ?array $prices = null): array
    {
        $prices ??= self::prices();
        $choice = $choice === 'cement' ? 'cement' : 'sandwich';
        $label = $choice === 'cement' ? 'خرسانة' : 'ساندوتش بانل';
        $amount = round((float) $prices[$choice]);
        $money = VentilationOffer::money($amount);

        return [
            'rows' => [
                ['نوع الإنشاءات', $label, false],
                ['تكلفة الإنشاءات', $money, false],
            ],
            'total' => $amount,
            'choice' => $choice,
            'message' => 'عرض الإنشاءات ('.$label.'): '.$money,
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
        return $this->chosen((string) ($quotation->construction_choice ?: 'sandwich'));
    }
}
