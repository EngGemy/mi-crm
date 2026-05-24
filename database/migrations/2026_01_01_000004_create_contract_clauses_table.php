<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_clauses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('CL-CONSTRUCTION, CL-ELECTRICITY,...');
            $table->string('title');
            $table->string('title_en')->nullable();
            $table->enum('category', [
                'preamble',        // ديباجة
                'subject',         // موضوع العقد
                'financial',       // البند المالي
                'schedule',        // الجدول الزمني
                'technical',       // المواصفات الفنية
                'construction',    // الإنشاءات
                'electricity',     // الكهرباء
                'plumbing',        // السباكة
                'isolation',       // العزل
                'fire_safety',     // السلامة والإطفاء
                'generator',       // الجنريتور
                'security',        // نظام الأمن
                'installation',    // التركيب
                'training',        // التدريب
                'warranty',        // الضمان
                'maintenance',     // الصيانة
                'spare_parts',     // قطع الغيار
                'force_majeure',   // القوة القاهرة
                'penalties',       // الشروط الجزائية
                'confidentiality', // السرية
                'jurisdiction',    // الاختصاص القضائي
                'general',         // أحكام عامة
                'custom',          // مخصص
            ])->index();

            $table->longText('content')->comment('النص الأساسي مع متغيرات {{var}}');
            $table->longText('content_en')->nullable();

            $table->json('variables')->nullable()->comment('قائمة المتغيرات وأنواعها');
            // مثال: [{"name": "warranty_years", "label": "سنوات الضمان", "type": "number", "default": 12}]

            $table->json('items_schema')->nullable()->comment('schema للجدول لو البند فيه جدول');
            // مثال: [{"key": "item", "label": "البند"}, {"key": "qty", "label": "الكمية", "type": "number"}]

            $table->boolean('is_required')->default(false)->comment('بند إلزامي لا يحذف');
            $table->boolean('is_default')->default(false)->comment('يضاف افتراضياً للعقود');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('applicable_contract_types')->nullable()->comment('IDs أنواع العقود اللي يصلح فيها');
            $table->text('description')->nullable()->comment('وصف للموظف');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_clauses');
    }
};
