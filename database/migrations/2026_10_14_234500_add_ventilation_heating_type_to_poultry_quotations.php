<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poultry_quotations', function (Blueprint $table) {
            $table->string('ventilation_heating_type', 20)->default('hot_water')->after('ventilation_heating_price');
        });
    }

    public function down(): void
    {
        Schema::table('poultry_quotations', function (Blueprint $table) {
            $table->dropColumn('ventilation_heating_type');
        });
    }
};
