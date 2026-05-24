<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poultry_quotations', function (Blueprint $table) {
            $table->string('project_type')->default('broiler')->after('client_address');
            $table->string('pricing_scope')->default('full_project')->after('project_type');
            $table->decimal('service_length', 5, 2)->nullable()->after('dead_zone');
            $table->decimal('bird_weight_kg', 5, 3)->nullable()->after('service_length');
            $table->unsignedInteger('birds_per_nest')->nullable()->after('bird_weight_kg');
            $table->unsignedInteger('total_nests')->default(0)->after('bird_count');
            $table->unsignedInteger('nests_per_line')->default(0)->after('total_nests');
            $table->string('wall_type')->nullable()->after('height');
            $table->foreignId('contract_id')->nullable()->after('status')->constrained('contracts')->nullOnDelete();
            $table->json('pricing_snapshot')->nullable()->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('poultry_quotations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contract_id');
            $table->dropColumn([
                'project_type', 'pricing_scope', 'service_length', 'bird_weight_kg',
                'birds_per_nest', 'total_nests', 'nests_per_line', 'wall_type', 'pricing_snapshot',
            ]);
        });
    }
};
