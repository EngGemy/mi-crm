<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('lead_number', 30)->unique()->comment('LEAD-2026-0001');

            // Contact info
            $table->string('name', 200)->index();
            $table->string('phone', 30);
            $table->string('whatsapp', 30)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('company', 200)->nullable();
            $table->string('position', 100)->nullable();

            // Location
            $table->string('country', 50)->default('Egypt');
            $table->string('city', 100)->nullable();
            $table->text('address')->nullable();

            // Expected project
            $table->string('project_type', 100)->nullable();
            $table->string('project_size', 100)->nullable();
            $table->decimal('estimated_budget', 15, 2)->nullable();
            $table->date('expected_close_date')->nullable();

            // Classification
            $table->string('source', 50)->index();
            $table->string('source_details', 255)->nullable();
            $table->enum('status', ['new', 'contacted', 'qualified', 'opportunity', 'won', 'lost'])->default('new')->index();
            $table->unsignedTinyInteger('score')->default(50);
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');

            // Responsibility
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Conversion
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained('quotations')->nullOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();

            // If lost
            $table->string('lost_reason', 100)->nullable();
            $table->text('lost_notes')->nullable();
            $table->timestamp('lost_at')->nullable();

            // Last activity tracking
            $table->timestamp('last_contact_at')->nullable();
            $table->timestamp('next_followup_at')->nullable()->index();

            // Extra data
            $table->json('tags')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->index(['assigned_to', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
