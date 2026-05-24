<?php

namespace App\Support;

/**
 * تسميات عربية موحّدة لمخرجات PDF لعرض السعر.
 */
final class QuotationPdfLabels
{
    public static function currency(?string $code): string
    {
        return match (strtoupper((string) $code)) {
            'EGP' => 'ج.م',
            'USD' => 'دولار',
            'EUR' => 'يورو',
            'SAR' => 'ر.س',
            'AED' => 'د.إ',
            default => $code !== '' && $code !== '0' ? (string) $code : 'ج.م',
        };
    }

    public static function unit(?string $unit): string
    {
        $u = strtolower(trim((string) $unit));

        return match ($u) {
            'piece', 'pc', 'pcs', 'qty', 'unit' => 'قطعة',
            'meter', 'm' => 'متر',
            'sqm', 'm2', 'm²' => 'متر مربع',
            'kg', 'kilogram' => 'كجم',
            'ton', 't' => 'طن',
            'set', 'lot' => 'طقم',
            'hour', 'hr' => 'ساعة',
            'day' => 'يوم',
            'roll' => 'لفة',
            '' => '—',
            default => $unit ?? '—',
        };
    }
}
