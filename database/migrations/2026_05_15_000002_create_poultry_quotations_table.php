<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poultry_quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quote_number')->unique();
            $table->string('client_name');
            $table->string('client_phone')->nullable();
            $table->string('client_address')->nullable();
            $table->decimal('length', 8, 2);
            $table->decimal('width', 8, 2);
            $table->decimal('height', 8, 2);
            $table->unsignedInteger('tiers');
            $table->unsignedInteger('lines');
            $table->decimal('dead_zone', 5, 2)->default(6);
            $table->unsignedInteger('side_fans_count')->default(8);
            $table->unsignedInteger('heaters_count')->default(2);
            $table->unsignedInteger('bird_count')->default(0);
            $table->unsignedInteger('back_fans_count')->default(0);
            $table->decimal('cooling_units', 8, 2)->default(0);
            $table->unsignedInteger('windows_count')->default(0);
            $table->decimal('concrete_cost', 15, 2)->default(0);
            $table->decimal('steel_cost', 15, 2)->default(0);
            $table->decimal('walls_cost', 15, 2)->default(0);
            $table->decimal('tanks_cost', 15, 2)->default(0);
            $table->decimal('battery_cost', 15, 2)->default(0);
            $table->decimal('back_fans_cost', 15, 2)->default(0);
            $table->decimal('cooling_cost', 15, 2)->default(0);
            $table->decimal('windows_cost', 15, 2)->default(0);
            $table->decimal('side_fans_cost', 15, 2)->default(0);
            $table->decimal('heaters_cost', 15, 2)->default(0);
            $table->decimal('control_cost', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('vat_percentage', 5, 2)->default(0);
            $table->string('status')->default('draft');
            $table->string('image_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poultry_quotations');
    }
};
