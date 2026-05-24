<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('quotation_sections')->nullOnDelete();
            $table->string('description_ar');
            $table->string('description_en')->nullable();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->string('unit', 50)->default('piece')->comment('Meter Square, شفاط, دفاية, دجاجة, etc');
            $table->decimal('quantity', 12, 3)->default(1);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);
            $table->boolean('is_taxable')->default(true);
            $table->string('tax_label', 50)->default('VAT');
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['quotation_id', 'section_id', 'sort_order'], 'idx_qi_quotation_section');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
    }
};
