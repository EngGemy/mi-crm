<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->unsignedInteger('tiers')->nullable()->after('hall_count')->comment('عدد الأدوار');
            $table->unsignedInteger('lines')->nullable()->after('tiers')->comment('عدد الخطوط');
            $table->decimal('dead_zone_meters', 5, 2)->nullable()->after('lines')->comment('المنطقة الميتة');
            $table->unsignedInteger('side_fans_count')->nullable()->after('dead_zone_meters');
            $table->unsignedInteger('heaters_count')->nullable()->after('side_fans_count');
            $table->unsignedInteger('back_fans_count')->nullable()->after('heaters_count');
            $table->decimal('cooling_units', 8, 2)->nullable()->after('back_fans_count');
            $table->unsignedInteger('windows_count')->nullable()->after('cooling_units');
            $table->json('pricing_snapshot')->nullable()->after('windows_count')->comment('snapshot of pricing parameters used');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn([
                'tiers', 'lines', 'dead_zone_meters',
                'side_fans_count', 'heaters_count',
                'back_fans_count', 'cooling_units', 'windows_count',
                'pricing_snapshot',
            ]);
        });
    }
};
