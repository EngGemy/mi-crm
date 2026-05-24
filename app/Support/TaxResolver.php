<?php

namespace App\Support;

/**
 * مُحلِّل الضريبة المركزي — يقرأ من الإعدادات ويُلغي كل القيم المثبتة في الكود.
 */
class TaxResolver
{
    /**
     * نسبة الضريبة كنسبة مئوية (14.0, 15.0, 0.0).
     */
    public static function percentageFor(string $region = 'default'): float
    {
        if ($region === 'default') {
            $region = self::defaultRegion();
        }

        return match ($region) {
            'egypt' => self::readSetting('tax.vat_rate_egypt', 14),
            'ksa' => self::readSetting('tax.vat_rate_ksa', 15),
            'none' => 0.0,
            default => self::readSetting('tax.vat_rate_egypt', 14),
        };
    }

    /**
     * مُعامل الضريبة ككسر عشري (0.14, 0.15, 0.0).
     */
    public static function rateFor(string $region = 'default'): float
    {
        return self::percentageFor($region) / 100;
    }

    /**
     * المنطقة الضريبية الافتراضية.
     */
    public static function defaultRegion(): string
    {
        return self::readSetting('tax.default_vat_region', 'egypt');
    }

    /** Reads setting with fallback — safe in unit test environments without Laravel container. */
    private static function readSetting(string $key, mixed $default): mixed
    {
        try {
            return settings($key, $default);
        } catch (\Throwable) {
            return $default;
        }
    }
}
