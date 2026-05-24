<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('CONSTRUCTION_ONLY, CAGES_ONLY, ...');
            $table->string('name')->comment('الاسم بالعربي');
            $table->string('name_en')->nullable();
            $table->text('description')->nullable();
            $table->string('icon', 50)->default('heroicon-o-document-text')->comment('Heroicon name');
            $table->string('color', 20)->default('primary')->comment('Filament color');
            $table->json('default_sections')->nullable()->comment('IDs of quotation_sections');
            $table->json('default_terms')->nullable()->comment('IDs of quotation_terms');
            $table->json('default_payment_schedule')->nullable()->comment('[70, 25, 5]');
            $table->json('template_layout')->nullable()->comment('PDF page structure');
            $table->unsignedInteger('default_validity_days')->default(7);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_types');
    }
};
