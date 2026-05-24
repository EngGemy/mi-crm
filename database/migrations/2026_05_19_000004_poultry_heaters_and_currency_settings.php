<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Setting::updateOrCreate(
            ['key' => 'poultry_pricing.heater_lot_prices'],
            [
                'value' => json_encode([
                    '3' => 45000,
                    '4' => 55000,
                    '5' => 65000,
                    '6' => 75000,
                    '8' => 95000,
                ], JSON_UNESCAPED_UNICODE),
                'type' => 'json',
                'category' => 'poultry_pricing',
                'label_ar' => 'أسعار الدفايات (مقطوعة حسب العدد)',
                'label_en' => 'Heater lot prices by count',
                'description' => 'مفاتيح: 3، 4، 5، 6، 8 دفايات',
                'is_public' => false,
                'sort_order' => 33,
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'poultry_pricing.egp_to_usd_rate'],
            [
                'value' => (string) (settings('defaults.exchange_rate', 48) ?: 48),
                'type' => 'decimal',
                'category' => 'poultry_pricing',
                'label_ar' => 'سعر صرف الجنيه → دولار',
                'label_en' => 'EGP to USD rate',
                'description' => 'للعرض التقريبي: المبلغ بالدولار = المبلغ ÷ السعر',
                'is_public' => false,
                'sort_order' => 34,
            ]
        );
    }

    public function down(): void
    {
        Setting::whereIn('key', [
            'poultry_pricing.heater_lot_prices',
            'poultry_pricing.egp_to_usd_rate',
        ])->delete();
    }
};
