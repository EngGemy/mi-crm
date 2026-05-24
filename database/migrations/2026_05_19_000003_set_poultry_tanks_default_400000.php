<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Setting::updateOrCreate(
            ['key' => 'poultry_pricing.tanks_fixed_cost'],
            [
                'value' => '400000',
                'type' => 'decimal',
                'category' => 'poultry_pricing',
                'label_ar' => 'تكلفة الخزانات الثابتة',
                'label_en' => 'Tanks fixed cost',
                'description' => 'بند ثابت لمجموعة خزانات المياه — يُضاف كمقطوعة واحدة',
                'is_public' => false,
                'is_required' => true,
                'sort_order' => 4,
                'validation_rules' => ['numeric', 'min:0'],
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'poultry_pricing.include_tanks_default'],
            [
                'value' => '1',
                'type' => 'boolean',
                'category' => 'poultry_pricing',
                'label_ar' => 'تضمين الخزانات في التسعير',
                'label_en' => 'Include tanks in pricing',
                'description' => 'عند التفعيل يُضاف بند الخزانات الثابت تلقائياً',
                'is_public' => false,
                'is_required' => false,
                'sort_order' => 30,
                'validation_rules' => [],
            ]
        );

        // ترقية القيم القديمة إن وُجدت
        Setting::where('key', 'poultry_pricing.tanks_fixed_cost')
            ->whereIn('value', ['0', '25000', '25000.00', '25000.0'])
            ->update(['value' => '400000']);
    }

    public function down(): void
    {
        // لا نرجع القيمة القديمة تلقائياً — قد تكون مُعدّلة يدوياً
    }
};
