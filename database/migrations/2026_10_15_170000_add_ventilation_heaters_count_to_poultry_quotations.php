<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poultry_quotations', function (Blueprint $table) {
            $table->unsignedSmallInteger('ventilation_heaters_count')->nullable()->after('ventilation_heating_type');
        });
    }

    public function down(): void
    {
        Schema::table('poultry_quotations', function (Blueprint $table) {
            $table->dropColumn('ventilation_heaters_count');
        });
    }
};
