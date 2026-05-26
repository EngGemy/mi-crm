<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            'crm.notifications.bell_enabled' => true,
            'crm.notifications.email_enabled' => false,
            'crm.notifications.whatsapp_mode' => 'manual', // manual | api | hybrid
            'crm.notifications.whatsapp_api_url' => '',
            'crm.notifications.whatsapp_api_token' => '',
            'crm.alerts.no_contact_days' => 7,
            'crm.alerts.quotation_expiry_days' => 3,
            'crm.alerts.birthday_advance_days' => 1,
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(
                ['key' => $key],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value, 'category' => 'crm', 'type' => 'string']
            );
        }
    }

    public function down(): void
    {
        Setting::where('category', 'crm')->delete();
    }
};
