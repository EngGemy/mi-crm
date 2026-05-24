<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique()->comment('FATTENING_FULL, LAYING_FULL, ...');
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->text('description')->nullable();
            $table->string('icon', 30)->nullable()->comment('Heroicon name');
            $table->string('color', 20)->default('primary');
            $table->json('default_milestones')->nullable()->comment('Milestones افتراضية');
            $table->json('default_clauses')->nullable()->comment('IDs البنود الافتراضية');
            $table->json('payment_schedule_default')->nullable()->comment('جدول الدفعات الافتراضي');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_types');
    }
};
