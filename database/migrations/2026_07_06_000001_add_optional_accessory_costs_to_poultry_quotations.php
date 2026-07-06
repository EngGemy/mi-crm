<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poultry_quotations', function (Blueprint $table) {
            $table->boolean('include_monitor')->default(false)->after('control_cost');
            $table->decimal('monitor_cost', 15, 2)->nullable()->after('include_monitor');
            $table->boolean('include_electricity')->default(false)->after('monitor_cost');
            $table->decimal('electricity_cost', 15, 2)->nullable()->after('include_electricity');
        });
    }

    public function down(): void
    {
        Schema::table('poultry_quotations', function (Blueprint $table) {
            $table->dropColumn([
                'include_monitor',
                'monitor_cost',
                'include_electricity',
                'electricity_cost',
            ]);
        });
    }
};
