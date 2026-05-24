<?php

namespace App\Services;

use App\Models\Quotation;
use App\Models\QuotationItem;
use Illuminate\Support\Collection;

/**
 * محرك حسابات عروض الأسعار (بنود + خصم + ضريبة + عملة ثانوية).
 */
class QuotationCalculator
{
    /**
     * إجمالي سطر بند واحد: سعر × كمية × (1 - خصم%/100)
     */
    public static function calculateItemTotal(array $item): float
    {
        $unitPrice = self::toFloat($item['unit_price'] ?? 0);
        $quantity = self::toFloat($item['quantity'] ?? 0);
        $discount = self::toFloat($item['discount_percentage'] ?? 0);

        $base = $unitPrice * $quantity;

        return round($base * (1 - ($discount / 100)), 2);
    }

    /**
     * حساب المجاميع من مصفوفة بيانات فورم أو نموذج.
     *
     * @param  array<string, mixed>  $data
     * @return array{items: array<int, array<string, mixed>>, subtotal: float, discount_amount: float, vat_amount: float, total_amount: float, total_amount_secondary: float|null}
     */
    public static function calculateQuotation(array $data): array
    {
        $rawItems = $data['items'] ?? [];
        $items = Collection::make($rawItems)->map(function ($item) {
            $item = is_array($item) ? $item : [];
            $item['total_price'] = self::calculateItemTotal($item);

            return $item;
        });

        $subtotal = round((float) $items->sum(fn ($item) => self::toFloat($item['total_price'] ?? 0)), 2);

        $discountPercentage = self::toFloat($data['discount_percentage'] ?? 0);
        // خصم على المجموع = نسبة من الـ subtotal (كما في مواصفة المحرك)
        $discountAmount = round($subtotal * ($discountPercentage / 100), 2);

        $afterDiscount = round($subtotal - $discountAmount, 2);
        if ($afterDiscount < 0) {
            $afterDiscount = 0;
        }

        $vatPercentage = self::toFloat($data['vat_percentage'] ?? 0);
        $vatAmount = round($afterDiscount * ($vatPercentage / 100), 2);
        $totalAmount = round($afterDiscount + $vatAmount, 2);

        $totalSecondary = null;
        $secondaryCurrency = $data['secondary_currency'] ?? null;
        $exchangeRate = self::toFloat($data['exchange_rate'] ?? 0);
        if (! empty($secondaryCurrency) && $exchangeRate > 0) {
            // العملة الثانوية = الإجمالي × سعر الصرف (مثال: تحويل من العملة الأساسية)
            $totalSecondary = round($totalAmount * $exchangeRate, 2);
        }

        return [
            'items' => $items->values()->toArray(),
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'vat_amount' => $vatAmount,
            'total_amount' => $totalAmount,
            'total_amount_secondary' => $totalSecondary,
        ];
    }

    /**
     * دمج نتيجة الحساب في بيانات الفورم (رؤوس + total_price لكل بند).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function mergeCalculatedTotalsIntoFormData(array $data): array
    {
        $result = self::calculateQuotation($data);

        $data['subtotal'] = $result['subtotal'];
        $data['discount_amount'] = $result['discount_amount'];
        $data['vat_amount'] = $result['vat_amount'];
        $data['total_amount'] = $result['total_amount'];

        if ($result['total_amount_secondary'] !== null) {
            $data['total_amount_secondary'] = $result['total_amount_secondary'];
        }

        $items = $data['items'] ?? [];
        $computedLines = array_values($result['items']);
        $i = 0;
        foreach ($items as $key => $row) {
            if (! is_array($row)) {
                continue;
            }
            if (! isset($computedLines[$i])) {
                break;
            }
            $items[$key]['total_price'] = number_format((float) ($computedLines[$i]['total_price'] ?? 0), 2, '.', '');
            $i++;
        }
        $data['items'] = $items;

        return $data;
    }

    /**
     * تحديث حقول المجاميع على سجل عرض السعر من بنوده (للـ Observer).
     */
    public static function applyQuotationHeaderTotals(Quotation $quotation): void
    {
        $quotation->load('items');

        $itemsPayload = $quotation->items->map(fn (QuotationItem $i) => [
            'unit_price' => $i->unit_price,
            'quantity' => $i->quantity,
            'discount_percentage' => $i->discount_percentage,
        ])->values()->toArray();

        $result = self::calculateQuotation([
            'items' => $itemsPayload,
            'discount_percentage' => $quotation->discount_percentage,
            'discount_amount' => $quotation->discount_amount,
            'vat_percentage' => $quotation->vat_percentage,
            'secondary_currency' => $quotation->secondary_currency,
            'exchange_rate' => $quotation->exchange_rate,
        ]);

        $quotation->withoutEvents(function () use ($quotation, $result) {
            $quotation->update([
                'subtotal' => self::formatDecimalString($result['subtotal']),
                'discount_amount' => self::formatDecimalString($result['discount_amount']),
                'vat_amount' => self::formatDecimalString($result['vat_amount']),
                'total_amount' => self::formatDecimalString($result['total_amount']),
                'total_amount_secondary' => $result['total_amount_secondary'] !== null
                    ? self::formatDecimalString($result['total_amount_secondary'])
                    : null,
            ]);
        });
    }

    public static function formatDecimalString(float $value): string
    {
        return number_format($value, 2, '.', '');
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

    public static function format(float $value, int $decimals = 2): string
    {
        return number_format($value, $decimals, '.', ',');
    }

    public static function toArabicWords(float $value): string
    {
        return app(ClauseRenderer::class)->numberToArabicWords($value);
    }

    public static function currencySymbol(string $currency): string
    {
        return match ($currency) {
            'EGP' => 'ج.م',
            'USD' => '$',
            'SAR' => 'ر.س',
            'AED' => 'د.إ',
            default => $currency,
        };
    }
}
