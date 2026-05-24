<?php

namespace Database\Seeders;

use App\Models\LeadSource;
use Illuminate\Database\Seeder;

class LeadSourcesSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            ['code' => 'facebook', 'name_ar' => 'فيسبوك', 'name_en' => 'Facebook', 'icon' => 'heroicon-m-chat-bubble-left-right', 'color' => 'primary', 'sort_order' => 1],
            ['code' => 'whatsapp', 'name_ar' => 'واتساب', 'name_en' => 'WhatsApp', 'icon' => 'heroicon-m-chat-bubble-oval-left-ellipsis', 'color' => 'success', 'sort_order' => 2],
            ['code' => 'instagram', 'name_ar' => 'إنستجرام', 'name_en' => 'Instagram', 'icon' => 'heroicon-m-camera', 'color' => 'danger', 'sort_order' => 3],
            ['code' => 'website', 'name_ar' => 'الموقع الإلكتروني', 'name_en' => 'Website', 'icon' => 'heroicon-m-globe-alt', 'color' => 'info', 'sort_order' => 4],
            ['code' => 'referral', 'name_ar' => 'ترشيح', 'name_en' => 'Referral', 'icon' => 'heroicon-m-user-group', 'color' => 'warning', 'sort_order' => 5],
            ['code' => 'walk_in', 'name_ar' => 'زيارة مباشرة', 'name_en' => 'Walk-in', 'icon' => 'heroicon-m-building-storefront', 'color' => 'secondary', 'sort_order' => 6],
            ['code' => 'phone_call', 'name_ar' => 'مكالمة هاتفية', 'name_en' => 'Phone Call', 'icon' => 'heroicon-m-phone', 'color' => 'primary', 'sort_order' => 7],
            ['code' => 'exhibition', 'name_ar' => 'معرض', 'name_en' => 'Exhibition', 'icon' => 'heroicon-m-flag', 'color' => 'danger', 'sort_order' => 8],
            ['code' => 'cold_call', 'name_ar' => 'مكالمة باردة', 'name_en' => 'Cold Call', 'icon' => 'heroicon-m-snowflake', 'color' => 'info', 'sort_order' => 9],
            ['code' => 'other', 'name_ar' => 'أخرى', 'name_en' => 'Other', 'icon' => 'heroicon-m-question-mark-circle', 'color' => 'gray', 'sort_order' => 10],
        ];

        foreach ($sources as $source) {
            LeadSource::firstOrCreate(['code' => $source['code']], $source);
        }
    }
}
