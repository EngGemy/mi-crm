<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            ['key' => 'poultry_pricing.default_service_length', 'value' => '10', 'type' => 'decimal', 'category' => 'poultry_pricing', 'label_ar' => 'طول منطقة الخدمات الافتراضي (تسمين)', 'label_en' => 'Default service length (broiler)', 'sort_order' => 18],
            ['key' => 'poultry_pricing.fan_capacity_kg', 'value' => '5000', 'type' => 'decimal', 'category' => 'poultry_pricing', 'label_ar' => 'سعة المروحة (كجم طيور حية)', 'label_en' => 'Fan capacity kg', 'sort_order' => 19],
            ['key' => 'poultry_pricing.cooling_pad_meters_per_fan', 'value' => '5.5', 'type' => 'decimal', 'category' => 'poultry_pricing', 'label_ar' => 'أمتار التبريد لكل مروحة', 'label_en' => 'Cooling pad m per fan', 'sort_order' => 20],
            ['key' => 'poultry_pricing.layer_nest_module_m', 'value' => '0.60', 'type' => 'decimal', 'category' => 'poultry_pricing', 'label_ar' => 'وحدة العش الطولية (بياض) م', 'label_en' => 'Layer nest module m', 'sort_order' => 21],
            ['key' => 'poultry_pricing.layer_birds_per_nest', 'value' => '10', 'type' => 'integer', 'category' => 'poultry_pricing', 'label_ar' => 'طيور لكل عش (بياض)', 'label_en' => 'Layer birds per nest', 'sort_order' => 22],
            ['key' => 'poultry_pricing.layer_max_bird_weight_kg', 'value' => '1.7', 'type' => 'decimal', 'category' => 'poultry_pricing', 'label_ar' => 'وزن الطائر الأقصى للتهوية (بياض)', 'label_en' => 'Layer max bird weight', 'sort_order' => 23],
            ['key' => 'poultry_pricing.broiler_weight_birds_map', 'value' => json_encode(['1.600' => 21, '1.850' => 18, '2.100' => 16, '2.650' => 13, '2.800' => 12], JSON_UNESCAPED_UNICODE), 'type' => 'json', 'category' => 'poultry_pricing', 'label_ar' => 'تعيين الوزن → طيور/عش (تسمين)', 'label_en' => 'Broiler weight map', 'sort_order' => 24],
            ['key' => 'poultry_pricing.width_lines_map', 'value' => json_encode(['12' => 4, '15' => 5, '16.5' => 6], JSON_UNESCAPED_UNICODE), 'type' => 'json', 'category' => 'poultry_pricing', 'label_ar' => 'تعيين العرض → خطوط', 'label_en' => 'Width to lines map', 'sort_order' => 25],
            ['key' => 'poultry_pricing.side_fan_rules', 'value' => json_encode([
                ['min_barn_length' => 0, 'max_barn_length' => 90, 'min_birds' => 0, 'max_birds' => 30000, 'count' => 4],
                ['min_barn_length' => 90, 'max_barn_length' => 110, 'min_birds' => 0, 'max_birds' => 50000, 'count' => 6],
                ['min_barn_length' => 110, 'max_barn_length' => 999, 'min_birds' => 0, 'max_birds' => 999999, 'count' => 10],
            ], JSON_UNESCAPED_UNICODE), 'type' => 'json', 'category' => 'poultry_pricing', 'label_ar' => 'قواعد الشفاطات الجانبية', 'label_en' => 'Side fan rules', 'sort_order' => 26],
            ['key' => 'poultry_pricing.heater_rules', 'value' => json_encode([
                ['min_birds' => 0, 'max_birds' => 30000, 'count' => 2],
                ['min_birds' => 30001, 'max_birds' => 45000, 'count' => 3],
                ['min_birds' => 45001, 'max_birds' => 55000, 'count' => 4],
                ['min_birds' => 55001, 'max_birds' => 70000, 'count' => 5],
                ['min_birds' => 70001, 'max_birds' => 999999, 'count' => 6],
            ], JSON_UNESCAPED_UNICODE), 'type' => 'json', 'category' => 'poultry_pricing', 'label_ar' => 'قواعد الدفايات', 'label_en' => 'Heater rules', 'sort_order' => 27],
            ['key' => 'poultry_pricing.wall_type_rates', 'value' => json_encode(['sandwich' => 1200, 'cement' => 2000], JSON_UNESCAPED_UNICODE), 'type' => 'json', 'category' => 'poultry_pricing', 'label_ar' => 'أسعار أنواع الحوائط', 'label_en' => 'Wall type rates', 'sort_order' => 28],
            ['key' => 'poultry_pricing.default_wall_type', 'value' => 'sandwich', 'type' => 'string', 'category' => 'poultry_pricing', 'label_ar' => 'نوع الحائط الافتراضي', 'label_en' => 'Default wall type', 'sort_order' => 29],
            ['key' => 'poultry_pricing.include_tanks_default', 'value' => '1', 'type' => 'boolean', 'category' => 'poultry_pricing', 'label_ar' => 'تضمين الخزانات في التسعير', 'label_en' => 'Include tanks in pricing', 'sort_order' => 30],
            ['key' => 'poultry_pricing.electricity_fixed_cost', 'value' => '0', 'type' => 'decimal', 'category' => 'poultry_pricing', 'label_ar' => 'تكلفة الكهرباء الثابتة', 'label_en' => 'Electricity fixed cost', 'sort_order' => 31],
            ['key' => 'poultry_pricing.layer_air_windows_formula', 'value' => '', 'type' => 'string', 'category' => 'poultry_pricing', 'label_ar' => 'صيغة شبابيك البياض (فارغ=يدوي)', 'label_en' => 'Layer air windows formula', 'sort_order' => 32],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                array_merge([
                    'is_public' => false,
                    'is_required' => false,
                    'description' => '',
                    'validation_rules' => [],
                ], $setting)
            );
        }
    }

    public function down(): void
    {
        $keys = [
            'poultry_pricing.default_service_length',
            'poultry_pricing.fan_capacity_kg',
            'poultry_pricing.cooling_pad_meters_per_fan',
            'poultry_pricing.layer_nest_module_m',
            'poultry_pricing.layer_birds_per_nest',
            'poultry_pricing.layer_max_bird_weight_kg',
            'poultry_pricing.broiler_weight_birds_map',
            'poultry_pricing.width_lines_map',
            'poultry_pricing.side_fan_rules',
            'poultry_pricing.heater_rules',
            'poultry_pricing.wall_type_rates',
            'poultry_pricing.default_wall_type',
            'poultry_pricing.include_tanks_default',
            'poultry_pricing.electricity_fixed_cost',
            'poultry_pricing.layer_air_windows_formula',
        ];
        Setting::whereIn('key', $keys)->delete();
    }
};
