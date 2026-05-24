<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->enum('type', ['call', 'visit', 'email', 'whatsapp', 'meeting', 'other']);

            $table->timestamp('remind_at');

            $table->enum('status', ['pending', 'completed', 'snoozed', 'cancelled'])->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('snoozed_until')->nullable();
            $table->unsignedInteger('snooze_count')->default(0);

            $table->boolean('notified')->default(false);
            $table->timestamp('notified_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status', 'remind_at']);
            $table->index(['lead_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_reminders');
    }
};
