<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poultry_quotations', function (Blueprint $table) {
            $table->decimal('construction_concrete_price', 12, 2)->nullable()->after('construction_price');
        });

        Setting::query()
            ->where('key', 'poultry_pricing.concrete_cost_per_m2')
            ->whereIn('value', ['850', '850.00', '850.0'])
            ->update(['value' => '1200']);

        Cache::forget('app_settings');
    }

    public function down(): void
    {
        Schema::table('poultry_quotations', function (Blueprint $table) {
            $table->dropColumn('construction_concrete_price');
        });
    }
};
