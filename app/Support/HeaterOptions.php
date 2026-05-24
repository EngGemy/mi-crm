<?php

namespace App\Support;

use App\Services\SettingsService;

class HeaterOptions
{
    public const ALLOWED_COUNTS = [3, 4, 5, 6, 8];

    /** @return array<int, string> */
    public static function selectOptions(): array
    {
        $options = [0 => 'بدون دفايات'];
        $prices = self::lotPricesFromSettings();

        foreach (self::ALLOWED_COUNTS as $count) {
            $price = $prices[(string) $count] ?? null;
            $options[$count] = $count.' دفاية'
                .($price !== null ? ' — '.number_format((float) $price).' ج.م' : '');
        }

        return $options;
    }

    /** @return array<string, float> */
    public static function lotPricesFromSettings(): array
    {
        if (! function_exists('app') || ! app()->bound(SettingsService::class)) {
            return self::defaultLotPrices();
        }

        $raw = settings('poultry_pricing.heater_lot_prices');
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);

            return is_array($decoded) ? array_map('floatval', $decoded) : self::defaultLotPrices();
        }

        return is_array($raw) ? array_map('floatval', $raw) : self::defaultLotPrices();
    }

    /** @return array<string, float> */
    public static function defaultLotPrices(): array
    {
        return [
            '3' => 45000,
            '4' => 55000,
            '5' => 65000,
            '6' => 75000,
            '8' => 95000,
        ];
    }

    public static function isAllowedCount(int $count): bool
    {
        return $count === 0 || in_array($count, self::ALLOWED_COUNTS, true);
    }
}
