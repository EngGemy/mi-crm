<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class TaxAndFinanceSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaultSchedule = json_encode([
            [
                'description' => 'الدفعة المقدمة (70%) - عند التوقيع',
                'percentage' => 70,
                'milestone_code' => 'CONTRACT_SIGN',
                'offset_days' => 0,
            ],
            [
                'description' => 'الدفعة الثانية (25%) - عند بدء الشحن',
                'percentage' => 25,
                'milestone_code' => 'SHIPPING_START',
                'offset_days' => 0,
            ],
            [
                'description' => 'الدفعة الأخيرة (5%) - عند التسليم النهائي',
                'percentage' => 5,
                'milestone_code' => 'FINAL_DELIVERY',
                'offset_days' => 0,
            ],
        ], JSON_UNESCAPED_UNICODE);

        $settings = [
            // ===== Tax =====
            [
                'key' => 'tax.vat_rate_egypt',
                'value' => '14',
                'type' => 'decimal',
                'category' => 'tax',
                'label_ar' => 'نسبة ضريبة القيمة المضافة - مصر (%)',
                'label_en' => 'VAT Rate Egypt (%)',
                'description' => 'نسبة ضريبة القيمة المضافة المطبّقة على العقود المصرية',
                'is_public' => false,
                'is_required' => true,
                'sort_order' => 1,
                'validation_rules' => ['numeric', 'min:0', 'max:100'],
            ],
            [
                'key' => 'tax.vat_rate_ksa',
                'value' => '15',
                'type' => 'decimal',
                'category' => 'tax',
                'label_ar' => 'نسبة ضريبة القيمة المضافة - السعودية (%)',
                'label_en' => 'VAT Rate KSA (%)',
                'description' => 'نسبة ضريبة القيمة المضافة المطبّقة على العقود السعودية',
                'is_public' => false,
                'is_required' => true,
                'sort_order' => 2,
                'validation_rules' => ['numeric', 'min:0', 'max:100'],
            ],
            [
                'key' => 'tax.default_vat_region',
                'value' => 'egypt',
                'type' => 'string',
                'category' => 'tax',
                'label_ar' => 'المنطقة الضريبية الافتراضية',
                'label_en' => 'Default VAT Region',
                'description' => 'تُستخدم عند إنشاء عرض سعر جديد بدون تحديد المنطقة (egypt/ksa/none)',
                'is_public' => false,
                'is_required' => true,
                'sort_order' => 3,
                'validation_rules' => ['in:egypt,ksa,none'],
            ],

            // ===== Finance =====
            [
                'key' => 'finance.default_discount_percentage',
                'value' => '0',
                'type' => 'decimal',
                'category' => 'finance',
                'label_ar' => 'نسبة الخصم الافتراضية (%)',
                'label_en' => 'Default Discount Percentage (%)',
                'description' => 'نسبة الخصم الافتراضية على العروض الجديدة',
                'is_public' => false,
                'is_required' => false,
                'sort_order' => 10,
                'validation_rules' => ['numeric', 'min:0', 'max:100'],
            ],
            [
                'key' => 'finance.default_exchange_rate',
                'value' => '50',
                'type' => 'decimal',
                'category' => 'finance',
                'label_ar' => 'سعر صرف الجنيه مقابل الدولار',
                'label_en' => 'EGP / USD Exchange Rate',
                'description' => 'يُستخدم لعرض المبالغ التقريبية بالدولار في عروض الأسعار',
                'is_public' => false,
                'is_required' => false,
                'sort_order' => 11,
                'validation_rules' => ['numeric', 'min:0.01'],
            ],
            [
                'key' => 'finance.payment_schedule',
                'value' => $defaultSchedule,
                'type' => 'json',
                'category' => 'finance',
                'label_ar' => 'جدول الدفعات الافتراضي',
                'label_en' => 'Default Payment Schedule',
                'description' => 'مصفوفة JSON تحدد نسب وأوصاف الدفعات الافتراضية (percentage + description + milestone_code)',
                'is_public' => false,
                'is_required' => false,
                'sort_order' => 12,
                'validation_rules' => ['json'],
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
