<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'quotation_id')) {
                $table->foreignId('quotation_id')->nullable()->after('contract_type_id')
                    ->constrained('quotations')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'quotation_id')) {
                $table->dropForeign(['quotation_id']);
                $table->dropColumn('quotation_id');
            }
        });
    }
};
