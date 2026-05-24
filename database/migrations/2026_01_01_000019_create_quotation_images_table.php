<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->foreignId('image_library_id')->nullable()->constrained('image_library')->nullOnDelete();
            $table->string('file_path')->nullable()->comment('لو upload خاص');
            $table->enum('position', ['cover', 'section', 'terms', 'footer'])->default('section');
            $table->foreignId('section_id')->nullable()->constrained('quotation_sections')->nullOnDelete();
            $table->string('caption_ar')->nullable();
            $table->string('caption_en')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['quotation_id', 'position', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_images');
    }
};
