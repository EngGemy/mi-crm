<?php

namespace App\Support;

use App\Services\SettingsService;

/**
 * تحويل مجاني بين الجنيه والدولار حسب سعر الصرف في الإعدادات.
 */
class CurrencyConverter
{
    public static function egpToUsdRate(): float
    {
        if (function_exists('app') && app()->bound(SettingsService::class)) {
            $rate = (float) settings(
                'poultry_pricing.egp_to_usd_rate',
                settings('defaults.exchange_rate', 48)
            );

            return $rate > 0 ? $rate : 48;
        }

        return 48;
    }

    public static function egpToUsd(float|string $egpAmount): float
    {
        return round((float) $egpAmount / self::egpToUsdRate(), 2);
    }

    public static function usdToEgp(float|string $usdAmount): float
    {
        return round((float) $usdAmount * self::egpToUsdRate(), 2);
    }

    /** @return array{rate: float, subtotal_usd: float, vat_usd: float, total_usd: float} */
    public static function quotationTotals(float $subtotal, float $vat, float $total): array
    {
        return [
            'rate' => self::egpToUsdRate(),
            'subtotal_usd' => self::egpToUsd($subtotal),
            'vat_usd' => self::egpToUsd($vat),
            'total_usd' => self::egpToUsd($total),
        ];
    }
}
