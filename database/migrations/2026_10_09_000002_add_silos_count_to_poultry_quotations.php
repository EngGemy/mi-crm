<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poultry_quotations', function (Blueprint $table) {
            if (! Schema::hasColumn('poultry_quotations', 'silos_count')) {
                $table->unsignedTinyInteger('silos_count')->default(1)->after('silo_capacity_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('poultry_quotations', function (Blueprint $table) {
            if (Schema::hasColumn('poultry_quotations', 'silos_count')) {
                $table->dropColumn('silos_count');
            }
        });
    }
};
