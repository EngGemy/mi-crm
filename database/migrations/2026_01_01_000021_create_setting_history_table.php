<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setting_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('setting_id')->constrained('settings')->cascadeOnDelete();
            $table->longText('old_value')->nullable();
            $table->longText('new_value')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['setting_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setting_history');
    }
};
