<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('type', ['call', 'whatsapp', 'email', 'sms', 'visit', 'meeting', 'note', 'status_change', 'reminder']);
            $table->string('subject', 255)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->enum('outcome', ['positive', 'neutral', 'negative', 'no_answer', 'rescheduled'])->nullable();

            $table->json('attachments')->nullable();

            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->boolean('is_completed')->default(false);

            $table->timestamps();

            $table->index(['lead_id', 'created_at']);
            $table->index(['scheduled_at', 'is_completed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_activities');
    }
};
