<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();

            $table->string('code', 30)->comment('SIGN, MANUFACTURING_START, SHIPPING, ...');
            $table->string('title');
            $table->text('description')->nullable();

            $table->date('expected_date')->nullable();
            $table->date('actual_date')->nullable();

            $table->enum('status', ['pending', 'in_progress', 'completed', 'delayed', 'skipped'])
                ->default('pending');

            $table->integer('sort_order')->default(0);
            $table->boolean('triggers_payment')->default(false)->comment('يفعّل دفعة');
            $table->text('notes')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['contract_id', 'sort_order']);
            $table->index(['status', 'expected_date']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 30)->unique()->comment('PAY-2026-0001');

            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('milestone_id')->nullable()->constrained('contract_milestones')->nullOnDelete();

            $table->string('description');
            $table->decimal('percentage', 5, 2)->comment('% من قيمة العقد');
            $table->decimal('expected_amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->string('currency', 3)->default('EGP');

            $table->date('due_date');
            $table->date('paid_date')->nullable();

            $table->enum('status', [
                'pending',     // قيد الانتظار
                'paid',        // مدفوعة
                'partial',     // مدفوعة جزئياً
                'overdue',     // متأخرة
                'cancelled',   // ملغية
                'refunded',    // مستردة
            ])->default('pending');

            $table->enum('payment_method', [
                'bank_transfer',
                'cash',
                'cheque',
                'credit_card',
                'other',
            ])->nullable();

            $table->string('reference_number', 100)->nullable()->comment('رقم التحويل/الشيك');
            $table->string('bank_name', 100)->nullable();
            $table->json('attachments')->nullable()->comment('إيصالات، صور التحويل');
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();

            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['contract_id', 'sort_order']);
            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('contract_milestones');
    }
};
