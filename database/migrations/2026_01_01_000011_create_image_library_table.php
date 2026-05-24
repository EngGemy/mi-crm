<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('image_library', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('title_ar');
            $table->string('title_en')->nullable();
            $table->enum('category', [
                'steel_work',
                'cooling',
                'ventilation',
                'feeding',
                'water',
                'cages',
                'cleaning',
                'civil',
                'electrical',
            ])->default('steel_work');
            $table->string('file_path')->comment('storage path');
            $table->unsignedInteger('file_size')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('alt_text_ar')->nullable();
            $table->string('alt_text_en')->nullable();
            $table->json('tags')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['category', 'usage_count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_library');
    }
};
