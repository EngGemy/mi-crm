<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poultry_quotations', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('quote_number')->constrained('customers')->nullOnDelete();
            $table->string('client_company')->nullable()->after('client_address');
            $table->string('client_email')->nullable()->after('client_company');
            $table->string('client_country')->nullable()->after('client_email');
            $table->string('client_location')->nullable()->after('client_country');
            $table->text('client_notes')->nullable()->after('client_location');

            $table->foreignId('quote_type_id')->nullable()->after('pricing_scope')->constrained('lookups')->nullOnDelete();
            $table->unsignedInteger('barns_count')->default(1)->after('lines');
            $table->decimal('bird_price', 12, 2)->nullable()->after('bird_weight_kg');
            $table->decimal('exchange_rate', 12, 4)->nullable()->after('bird_price');

            $table->foreignId('manure_motor_count_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->foreignId('motor_power_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->foreignId('belts_per_line_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->foreignId('inner_belt_length_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->foreignId('outer_belt_length_id')->nullable()->constrained('lookups')->nullOnDelete();
            $table->foreignId('silo_capacity_id')->nullable()->constrained('lookups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('poultry_quotations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
            $table->dropConstrainedForeignId('quote_type_id');
            $table->dropConstrainedForeignId('manure_motor_count_id');
            $table->dropConstrainedForeignId('motor_power_id');
            $table->dropConstrainedForeignId('belts_per_line_id');
            $table->dropConstrainedForeignId('inner_belt_length_id');
            $table->dropConstrainedForeignId('outer_belt_length_id');
            $table->dropConstrainedForeignId('silo_capacity_id');
            $table->dropColumn([
                'client_company', 'client_email', 'client_country', 'client_location', 'client_notes',
                'barns_count', 'bird_price', 'exchange_rate',
            ]);
        });
    }
};
