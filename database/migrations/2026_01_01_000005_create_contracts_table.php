<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number', 50)->unique()->comment('CTR-2026-001');
            $table->string('project_code', 50)->unique()->comment('PRJ-AN-2026');

            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('contract_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            // البيانات الأساسية
            $table->date('contract_date');
            $table->string('project_name');
            $table->text('project_description')->nullable();
            $table->string('installation_location');

            // أبعاد المشروع
            $table->decimal('hall_length', 8, 2)->nullable()->comment('م');
            $table->decimal('hall_width', 8, 2)->nullable();
            $table->decimal('hall_height', 8, 2)->nullable();
            $table->integer('hall_count')->default(1);
            $table->integer('cage_count')->nullable();
            $table->integer('bird_capacity')->nullable();
            $table->json('technical_specs')->nullable()->comment('مواصفات إضافية ديناميكية');

            // الأرقام المالية
            $table->decimal('cages_cost', 15, 2)->default(0);
            $table->decimal('construction_cost', 15, 2)->default(0);
            $table->decimal('electricity_cost', 15, 2)->default(0);
            $table->decimal('plumbing_cost', 15, 2)->default(0);
            $table->decimal('accessories_cost', 15, 2)->default(0);
            $table->decimal('other_cost', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('vat_percentage', 5, 2)->default(0);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('total_value', 15, 2)->default(0);
            $table->string('currency', 3)->default('EGP');
            $table->decimal('exchange_rate', 10, 4)->default(1);

            // الجدول الزمني
            $table->integer('manufacturing_days')->default(105);
            $table->date('manufacturing_start_date')->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->date('warranty_start_date')->nullable();
            $table->date('warranty_end_date')->nullable();
            $table->integer('warranty_months')->default(12);
            $table->integer('manufacturing_warranty_years')->default(12);

            // حالة العقد
            $table->enum('status', [
                'draft',                  // مسودة
                'pending_approval',       // قيد الموافقة
                'approved',               // معتمد
                'signed',                 // موقّع
                'manufacturing',          // قيد التصنيع
                'shipping',               // قيد الشحن
                'installing',             // قيد التركيب
                'testing',                // قيد التشغيل التجريبي
                'completed',              // مكتمل
                'on_hold',                // معلّق
                'cancelled',              // ملغي
                'archived',               // مؤرشف
            ])->default('draft');

            $table->enum('payment_status', [
                'unpaid',
                'partially_paid',
                'paid',
                'overdue',
            ])->default('unpaid');

            // محتوى العقد
            $table->longText('preamble_content')->nullable()->comment('نص الديباجة المخصص');
            $table->longText('custom_terms')->nullable();
            $table->json('additional_data')->nullable()->comment('بيانات إضافية للعقد');

            // ملفات
            $table->string('signed_pdf_path')->nullable();
            $table->json('attachments')->nullable();

            $table->text('internal_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'contract_date']);
            $table->index(['payment_status', 'expected_delivery_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
