<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poultry_quotation_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poultry_quotation_id')->constrained('poultry_quotations')->cascadeOnDelete();
            $table->json('parameters');
            $table->json('inputs');
            $table->json('results');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poultry_quotation_snapshots');
    }
};
