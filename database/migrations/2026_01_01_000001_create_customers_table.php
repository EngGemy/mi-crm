<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->comment('CUST-001');
            $table->string('name')->index();
            $table->string('name_en')->nullable();
            $table->string('national_id', 50)->nullable()->index();
            $table->string('nationality', 50)->default('سعودي');
            $table->string('phone', 30);
            $table->string('phone_alt', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->text('address');
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->default('SA');
            $table->string('tax_number', 50)->nullable()->comment('الرقم الضريبي');
            $table->string('commercial_register', 50)->nullable();
            $table->enum('type', ['individual', 'company'])->default('individual');
            $table->enum('status', ['active', 'inactive', 'blacklisted'])->default('active');
            $table->json('attachments')->nullable()->comment('صور الهوية والمستندات');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
