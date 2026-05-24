<?php

namespace Database\Seeders;

use App\Models\PricingParameter;
use Illuminate\Database\Seeder;

class PricingParameterSeeder extends Seeder
{
    public function run(): void
    {
        $parameters = [
            [
                'key' => 'concrete_cost_per_m2',
                'label_ar' => 'تكلفة الخرسانة للمتر المربع',
                'label_en' => 'Concrete cost per m²',
                'value' => 350.00,
                'unit' => 'EGP/m²',
                'category' => 'construction',
            ],
            [
                'key' => 'steel_cost_per_m2',
                'label_ar' => 'تكلفة الاستيل للمتر المربع',
                'label_en' => 'Steel cost per m²',
                'value' => 280.00,
                'unit' => 'EGP/m²',
                'category' => 'construction',
            ],
            [
                'key' => 'wall_cost_per_m2',
                'label_ar' => 'تكلفة الحوائط للمتر المربع',
                'label_en' => 'Wall cost per m²',
                'value' => 180.00,
                'unit' => 'EGP/m²',
                'category' => 'construction',
            ],
            [
                'key' => 'tanks_fixed',
                'label_ar' => 'تكلفة الخزانات (ثابتة)',
                'label_en' => 'Tanks fixed cost',
                'value' => 25000.00,
                'unit' => 'EGP',
                'category' => 'construction',
            ],
            [
                'key' => 'price_per_bird',
                'label_ar' => 'سعر الطائر الواحد',
                'label_en' => 'Price per bird',
                'value' => 45.00,
                'unit' => 'EGP',
                'category' => 'battery',
            ],
            [
                'key' => 'birds_per_cage_unit',
                'label_ar' => 'عدد الطيور لكل وحدة قفص',
                'label_en' => 'Birds per cage unit',
                'value' => 16.00,
                'unit' => 'bird',
                'category' => 'battery',
            ],
            [
                'key' => 'dead_zone_meters',
                'label_ar' => 'منطقة النهاية (متر)',
                'label_en' => 'Dead zone meters',
                'value' => 6.00,
                'unit' => 'm',
                'category' => 'battery',
            ],
            [
                'key' => 'back_fan_bird_ratio',
                'label_ar' => 'نسبة الشفاطات الخلفية للطيور',
                'label_en' => 'Back fan bird ratio',
                'value' => 2.10,
                'unit' => 'm³/h/bird',
                'category' => 'accessories',
            ],
            [
                'key' => 'back_fan_capacity',
                'label_ar' => 'سعة الشفاط الخلفي',
                'label_en' => 'Back fan capacity',
                'value' => 5000.00,
                'unit' => 'm³/h',
                'category' => 'accessories',
            ],
            [
                'key' => 'back_fan_unit_price',
                'label_ar' => 'سعر الشفاط الخلفي',
                'label_en' => 'Back fan unit price',
                'value' => 4500.00,
                'unit' => 'EGP',
                'category' => 'accessories',
            ],
            [
                'key' => 'cooling_per_fan',
                'label_ar' => 'التبريد لكل شفاط',
                'label_en' => 'Cooling per fan',
                'value' => 5.50,
                'unit' => 'm',
                'category' => 'accessories',
            ],
            [
                'key' => 'cooling_unit_price',
                'label_ar' => 'سعر متر التبريد',
                'label_en' => 'Cooling unit price per meter',
                'value' => 1200.00,
                'unit' => 'EGP/m',
                'category' => 'accessories',
            ],
            [
                'key' => 'windows_offset',
                'label_ar' => 'إزاحة الشبابيك',
                'label_en' => 'Windows offset',
                'value' => 4.00,
                'unit' => 'm',
                'category' => 'accessories',
            ],
            [
                'key' => 'window_unit_price',
                'label_ar' => 'سعر الشباك',
                'label_en' => 'Window unit price',
                'value' => 800.00,
                'unit' => 'EGP',
                'category' => 'accessories',
            ],
            [
                'key' => 'control_fixed',
                'label_ar' => 'تكلفة نظام التحكم (ثابتة)',
                'label_en' => 'Control system fixed cost',
                'value' => 15000.00,
                'unit' => 'EGP',
                'category' => 'accessories',
            ],
            [
                'key' => 'side_fan_unit_price',
                'label_ar' => 'سعر الشفاط الجانبي',
                'label_en' => 'Side fan unit price',
                'value' => 3500.00,
                'unit' => 'EGP',
                'category' => 'accessories',
            ],
            [
                'key' => 'heater_unit_price',
                'label_ar' => 'سعر الدفاية',
                'label_en' => 'Heater unit price',
                'value' => 2500.00,
                'unit' => 'EGP',
                'category' => 'accessories',
            ],
        ];

        foreach ($parameters as $parameter) {
            PricingParameter::updateOrCreate(
                ['key' => $parameter['key']],
                $parameter
            );
        }
    }
}
