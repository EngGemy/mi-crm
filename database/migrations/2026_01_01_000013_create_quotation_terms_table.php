<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_terms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('title_ar');
            $table->string('title_en')->nullable();
            $table->longText('content_ar')->nullable();
            $table->longText('content_en')->nullable();
            $table->json('variables')->nullable()->comment('[{name, label, type, default}]');
            $table->boolean('is_required')->default(false);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_default', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_terms');
    }
};
