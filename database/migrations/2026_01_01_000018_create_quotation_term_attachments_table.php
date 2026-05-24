<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_term_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->foreignId('quotation_term_id')->constrained('quotation_terms')->restrictOnDelete();
            $table->longText('content_override')->nullable();
            $table->json('variables_values')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->index(['quotation_id', 'sort_order']);
            $table->unique(['quotation_id', 'quotation_term_id'], 'idx_qta_unique_term');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_term_attachments');
    }
};
