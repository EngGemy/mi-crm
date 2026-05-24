<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_section_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->foreignId('quotation_section_id')->constrained('quotation_sections')->restrictOnDelete();
            $table->longText('content_override_ar')->nullable();
            $table->longText('content_override_en')->nullable();
            $table->json('custom_images')->nullable()->comment('URLs أو image_library IDs');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->index(['quotation_id', 'sort_order']);
            $table->unique(['quotation_id', 'quotation_section_id'], 'idx_qsa_unique_section');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_section_attachments');
    }
};
