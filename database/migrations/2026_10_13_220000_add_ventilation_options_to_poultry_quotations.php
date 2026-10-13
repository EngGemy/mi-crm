<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poultry_quotations', function (Blueprint $table) {
            $table->boolean('include_ventilation')->default(false)->after('include_electricity');
            $table->boolean('include_construction')->default(false)->after('include_ventilation');
            $table->unsignedSmallInteger('ventilation_side_fans')->nullable()->after('include_construction');
        });
    }

    public function down(): void
    {
        Schema::table('poultry_quotations', function (Blueprint $table) {
            $table->dropColumn(['include_ventilation', 'include_construction', 'ventilation_side_fans']);
        });
    }
};
