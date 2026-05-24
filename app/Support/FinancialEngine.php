<?php

namespace App\Support;

/**
 * محرك الحسابات المالية الموحّد.
 *
 * سياسة التقريب الموحّدة:
 * - bcmath بدقة منزلتين (scale=2) لكل عملية مالية.
 * - الترتيب: subtotal → discount → afterDiscount → vat → total.
 * - الخصم: النسبة تتقدم على المبلغ الثابت (كما في الكود الحالي).
 * - لا يُعاد حساب أي قيمة مالية بعد حفظها في snapshot.
 */
class FinancialEngine
{
    /**
     * حساب المجاميع المالية من subtotal + خصم + ضريبة.
     *
     * @param  float  $subtotal  المجموع قبل الخصم والضريبة
     * @param  float  $discountPercentage  نسبة الخصم (0–100)
     * @param  float  $discountAmount  مبلغ الخصم الثابت (يُستخدم فقط إذا كانت النسبة = 0)
     * @param  float  $vatPercentage  نسبة الضريبة (0–100)
     * @param  float|null  $exchangeRate  سعر الصرف للعملة الثانوية (optional)
     * @return array{
     *     subtotal: string,
     *     discount_amount: string,
     *     after_discount: string,
     *     vat_amount: string,
     *     total: string,
     *     total_secondary: string|null,
     * }
     */
    public static function calculateTotals(
        float $subtotal,
        float $discountPercentage = 0,
        float $discountAmount = 0,
        float $vatPercentage = 0,
        ?float $exchangeRate = null,
    ): array {
        $subtotalStr = self::format($subtotal);

        // الخصم: النسبة تتقدم على الثابت
        if ($discountPercentage > 0) {
            $discountStr = bcmul($subtotalStr, bcdiv((string) $discountPercentage, '100', 4), 2);
        } else {
            $discountStr = self::format($discountAmount);
        }

        $afterDiscountStr = bcsub($subtotalStr, $discountStr, 2);
        if (bccomp($afterDiscountStr, '0', 2) < 0) {
            $afterDiscountStr = '0.00';
        }

        $vatStr = bcmul($afterDiscountStr, bcdiv((string) $vatPercentage, '100', 4), 2);
        $totalStr = bcadd($afterDiscountStr, $vatStr, 2);

        $totalSecondary = null;
        if ($exchangeRate !== null && $exchangeRate > 0) {
            $totalSecondary = bcmul($totalStr, self::format($exchangeRate), 2);
        }

        return [
            'subtotal' => $subtotalStr,
            'discount_amount' => $discountStr,
            'after_discount' => $afterDiscountStr,
            'vat_amount' => $vatStr,
            'total' => $totalStr,
            'total_secondary' => $totalSecondary,
        ];
    }

    /**
     * حساب إجمالي بند واحد: سعر × كمية.
     */
    public static function calculateItemTotal(float $unitPrice, float $quantity): string
    {
        return bcmul(self::format($unitPrice), self::format($quantity), 2);
    }

    /**
     * تنسيق رقم إلى string بمنزلتين عشريتين.
     */
    public static function format(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    /**
     * قراءة string مالي إلى float.
     */
    public static function toFloat(string $value): float
    {
        return (float) $value;
    }
}
