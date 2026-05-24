<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->enum('category', [
                'cages',          // بطاريات
                'construction',   // إنشاءات
                'electricity',    // كهرباء
                'plumbing',       // سباكة
                'ventilation',    // تهوية
                'control',        // تحكم
                'cooling',        // تبريد
                'heating',        // تدفئة
                'feed_system',    // نظام التغذية
                'water_system',   // نظام المياه
                'manure_system',  // نظام السبلة
                'isolation',      // عزل
                'fire_system',    // إطفاء
                'generator',      // جنريتور
                'spare_parts',    // قطع غيار
                'services',       // خدمات
            ]);
            $table->string('unit', 20)->default('piece')->comment('قطعة/متر/م²/طن/...');
            $table->decimal('standard_price', 15, 2);
            $table->string('currency', 3)->default('EGP');
            $table->text('technical_specs')->nullable()->comment('المواصفات الفنية');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
