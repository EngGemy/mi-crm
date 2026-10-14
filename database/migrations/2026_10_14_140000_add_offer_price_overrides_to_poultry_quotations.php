<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poultry_quotations', function (Blueprint $table) {
            $table->decimal('construction_price', 12, 2)->nullable()->after('construction_choice');
            $table->decimal('ventilation_heating_price', 12, 2)->nullable()->after('ventilation_side_fans');
            $table->decimal('ventilation_electricity_price', 12, 2)->nullable()->after('ventilation_heating_price');
            $table->decimal('ventilation_monitor_price', 12, 2)->nullable()->after('ventilation_electricity_price');
        });
    }

    public function down(): void
    {
        Schema::table('poultry_quotations', function (Blueprint $table) {
            $table->dropColumn([
                'construction_price',
                'ventilation_heating_price',
                'ventilation_electricity_price',
                'ventilation_monitor_price',
            ]);
        });
    }
};
