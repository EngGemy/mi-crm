<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name_ar', 100)->nullable();
            $table->string('bank_name_en', 100)->nullable();
            $table->string('account_name_ar', 255)->nullable();
            $table->string('account_name_en', 255)->nullable();
            $table->string('account_number', 50);
            $table->string('iban', 50)->nullable();
            $table->string('swift_code', 20)->nullable();
            $table->string('currency', 3)->default('EGP');
            $table->string('branch', 100)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'is_default', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_bank_accounts');
    }
};
