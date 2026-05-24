<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();

            $table->string('section', 50)->comment('cages/construction/electricity/...');
            $table->string('description');
            $table->text('technical_specs')->nullable();

            $table->decimal('quantity', 12, 3)->default(1);
            $table->string('unit', 20)->default('piece');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('total_price', 15, 2);

            $table->boolean('is_taxable')->default(true);
            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['contract_id', 'section', 'sort_order'], 'idx_ci_contract_section');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_items');
    }
};
