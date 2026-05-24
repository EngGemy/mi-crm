<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_sections', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('title_ar');
            $table->string('title_en')->nullable();
            $table->enum('category', [
                'technical',
                'civil',
                'electrical',
                'cooling',
                'ventilation',
                'feeding',
                'water',
                'cages',
                'cleaning',
            ])->default('technical');
            $table->longText('content_ar')->nullable();
            $table->longText('content_en')->nullable();
            $table->json('default_images')->nullable()->comment('image_library IDs');
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('applicable_quotation_types')->nullable()->comment('quotation_type IDs');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_sections');
    }
};
