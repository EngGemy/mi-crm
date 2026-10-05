<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poultry_quotations', function (Blueprint $table) {
            $table->unsignedTinyInteger('internal_columns')->default(0)->after('barns_count');
            $table->decimal('bird_price_usd', 8, 2)->nullable()->after('bird_price');
        });
    }

    public function down(): void
    {
        Schema::table('poultry_quotations', function (Blueprint $table) {
            $table->dropColumn(['internal_columns', 'bird_price_usd']);
        });
    }
};
