<?php

namespace App\Services;

use App\Support\FinancialEngine;

/**
 * حساب مجاميع العقد من التكاليف + الخصم + الضريبة (متطابق مع Contract::saving).
 */
class ContractCalculator
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{subtotal: float, discount_amount: float, vat_amount: float, total_value: float}
     */
    public static function calculateContract(array $data): array
    {
        $subtotal = self::toFloat($data['cages_cost'] ?? 0)
            + self::toFloat($data['construction_cost'] ?? 0)
            + self::toFloat($data['electricity_cost'] ?? 0)
            + self::toFloat($data['plumbing_cost'] ?? 0)
            + self::toFloat($data['accessories_cost'] ?? 0)
            + self::toFloat($data['other_cost'] ?? 0);

        $discountPct = self::toFloat($data['discount_percentage'] ?? 0);
        $discountAmount = self::toFloat($data['discount_amount'] ?? 0);
        $vatPct = self::toFloat($data['vat_percentage'] ?? 0);

        $financial = FinancialEngine::calculateTotals($subtotal, $discountPct, $discountAmount, $vatPct);

        return [
            'subtotal' => FinancialEngine::toFloat($financial['subtotal']),
            'discount_amount' => FinancialEngine::toFloat($financial['discount_amount']),
            'vat_amount' => FinancialEngine::toFloat($financial['vat_amount']),
            'total_value' => FinancialEngine::toFloat($financial['total']),
        ];
    }

    public static function toFloat(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $cleaned = preg_replace('/[^\d.\-]/', '', str_replace(',', '', (string) $value));
        if ($cleaned === '' || $cleaned === '-' || $cleaned === '.') {
            return 0.0;
        }

        return (float) $cleaned;
    }
}
