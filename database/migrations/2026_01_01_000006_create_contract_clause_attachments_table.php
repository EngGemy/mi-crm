<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_clause_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_clause_id')->constrained()->restrictOnDelete();

            $table->longText('content_override')->nullable()->comment('نص مخصص لهذا العقد');
            $table->json('variables_values')->nullable()->comment('قيم المتغيرات لهذا العقد');
            // مثال: {"warranty_years": 12, "training_days": 5}

            $table->json('items')->nullable()->comment('بنود الجدول لهذا العقد');
            // مثال: [{"item": "خرسانة", "qty": 972, "unit": "م²", "price": 1200, "total": 1166400}]

            $table->integer('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['contract_id', 'contract_clause_id'], 'unique_contract_clause');
            $table->index(['contract_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_clause_attachments');
    }
};
