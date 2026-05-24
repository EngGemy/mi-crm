<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number', 50)->unique()->comment('QT-2026-0001');
            $table->unsignedInteger('revision_number')->default(1);
            $table->foreignId('parent_quotation_id')->nullable()->constrained('quotations')->nullOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('quotation_type_id')->constrained('quotation_types')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('status', [
                'draft',
                'sent',
                'approved',
                'rejected',
                'expired',
                'converted',
            ])->default('draft');

            $table->date('quotation_date');
            $table->date('valid_until');
            $table->unsignedInteger('validity_period_days')->default(7);

            $table->string('project_name');
            $table->text('project_description')->nullable();
            $table->string('installation_location')->nullable();
            $table->enum('hall_type', ['تسمين', 'بياض', 'تربية', 'أمهات'])->nullable();
            $table->decimal('hall_length', 8, 2)->nullable();
            $table->decimal('hall_width', 8, 2)->nullable();
            $table->decimal('hall_height', 8, 2)->nullable();
            $table->integer('hall_count')->default(1);
            $table->integer('cage_count')->nullable();
            $table->integer('bird_capacity')->nullable();
            $table->decimal('average_weight_kg', 5, 2)->nullable()->comment('1.85, 2.10, 2.55');

            $table->enum('language', ['ar', 'en', 'both'])->default('both');
            $table->enum('currency', ['EGP', 'USD', 'SAR', 'AED'])->default('EGP');
            $table->decimal('exchange_rate', 10, 4)->default(1);

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('vat_percentage', 5, 2)->default(15);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('total_amount_secondary', 15, 2)->nullable();
            $table->string('secondary_currency', 3)->nullable();

            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();

            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            $table->json('attachments')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'quotation_date']);
            $table->index(['customer_id', 'status']);
            $table->index(['valid_until', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
