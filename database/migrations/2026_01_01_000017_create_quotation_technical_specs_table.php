<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_technical_specs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->enum('spec_type', [
                'hall_dimensions',
                'battery_specs',
                'cage_specs',
                'care_per_weight',
                'custom',
            ])->default('custom');
            $table->string('title_ar');
            $table->string('title_en')->nullable();
            $table->json('data')->comment('array of key-value pairs');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['quotation_id', 'spec_type', 'sort_order'], 'idx_qts_quotation_spec');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_technical_specs');
    }
};
