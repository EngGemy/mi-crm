<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            [
                'key' => 'pdf.disinfection_title',
                'value' => 'دليل التطهير',
                'type' => 'string',
                'category' => 'pdf',
                'label_ar' => 'عنوان دليل التطهير',
                'sort_order' => 10,
            ],
            [
                'key' => 'pdf.disinfection_subtitle',
                'value' => 'إرشادات عامة لتطهير العنبر بين الدورات:',
                'type' => 'string',
                'category' => 'pdf',
                'label_ar' => 'العنوان الفرعي للتطهير',
                'sort_order' => 11,
            ],
            [
                'key' => 'pdf.disinfection_steps',
                'value' => json_encode([
                    ['title' => 'التنظيف الجاف', 'desc' => 'إزالة كل الروث والغبار من الأقفاص والأرضية باستخدام المكنسة والمظلة.'],
                    ['title' => 'الغسيل بالماء', 'desc' => 'غسيل جميع الأسطح بالماء تحت ضغط عالٍ للتأكد من إزالة الرواسب العضوية.'],
                    ['title' => 'التطهير الكيميائي', 'desc' => 'رش محاليل مطهرة (مثل فورمالديهايد أو فينول) على جميع الأسطح وتركها 24 ساعة.'],
                    ['title' => 'التعقيم بالحرارة', 'desc' => 'رفع درجة حرارة العنبر إلى 60 درجة مئوية لمدة 24 ساعة (إن أمكن).'],
                    ['title' => 'الفترة الصحية', 'desc' => 'ترك العنبر فارغاً لمدة 7-14 يوماً قبل استقبال الدفعة الجديدة.'],
                    ['title' => 'الفحص البكتيري', 'desc' => 'أخذ مسحات من الأسطح للتأكد من خلوها من البكتيريا الضارة.'],
                ], JSON_UNESCAPED_UNICODE),
                'type' => 'json',
                'category' => 'pdf',
                'label_ar' => 'خطوات التطهير (JSON)',
                'sort_order' => 12,
            ],
            [
                'key' => 'pdf.disinfection_warning_title',
                'value' => 'تنبيه:',
                'type' => 'string',
                'category' => 'pdf',
                'label_ar' => 'عنوان التنبيه',
                'sort_order' => 13,
            ],
            [
                'key' => 'pdf.disinfection_warning_text',
                'value' => 'يوصى بتطهير العنبر بين كل دورة وأخرى. فشل التطهير السليم قد يؤدي إلى انتشار الأمراض وخسارة اقتصادية كبيرة.',
                'type' => 'text',
                'category' => 'pdf',
                'label_ar' => 'نص التنبيه',
                'sort_order' => 14,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                array_merge($setting, ['is_public' => true])
            );
        }
    }

    public function down(): void
    {
        $keys = [
            'pdf.disinfection_title',
            'pdf.disinfection_subtitle',
            'pdf.disinfection_steps',
            'pdf.disinfection_warning_title',
            'pdf.disinfection_warning_text',
        ];

        Setting::whereIn('key', $keys)->delete();
    }
};
