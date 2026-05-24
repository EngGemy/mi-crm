@php
$title = settings('pdf.disinfection_title', 'دليل التطهير');
$subtitle = settings('pdf.disinfection_subtitle', 'إرشادات عامة لتطهير العنبر بين الدورات:');
$steps = settings('pdf.disinfection_steps', [
    ['title' => 'التنظيف الجاف', 'desc' => 'إزالة كل الروث والغبار من الأقفاص والأرضية باستخدام المكنسة والمظلة.'],
    ['title' => 'الغسيل بالماء', 'desc' => 'غسيل جميع الأسطح بالماء تحت ضغط عالٍ للتأكد من إزالة الرواسب العضوية.'],
    ['title' => 'التطهير الكيميائي', 'desc' => 'رش محاليل مطهرة (مثل فورمالديهايد أو فينول) على جميع الأسطح وتركها 24 ساعة.'],
    ['title' => 'التعقيم بالحرارة', 'desc' => 'رفع درجة حرارة العنبر إلى 60 درجة مئوية لمدة 24 ساعة (إن أمكن).'],
    ['title' => 'الفترة الصحية', 'desc' => 'ترك العنبر فارغاً لمدة 7-14 يوماً قبل استقبال الدفعة الجديدة.'],
    ['title' => 'الفحص البكتيري', 'desc' => 'أخذ مسحات من الأسطح للتأكد من خلوها من البكتيريا الضارة.'],
]);
$warningTitle = settings('pdf.disinfection_warning_title', 'تنبيه:');
$warningText = settings('pdf.disinfection_warning_text', 'يوصى بتطهير العنبر بين كل دورة وأخرى. فشل التطهير السليم قد يؤدي إلى انتشار الأمراض وخسارة اقتصادية كبيرة.');
@endphp

<h2>{{ $title }}</h2>

<div class="section-box" style="border:2px solid {{ settings('branding.primary_color') }}; background:#FFFDF5;">
    <p style="font-size:11pt; font-weight:bold; color:{{ settings('branding.primary_color') }}; margin:0 0 3mm 0;">
        {{ $subtitle }}
    </p>

    <table class="info-table" style="margin:2mm 0;">
        @foreach($steps as $idx => $step)
        <tr>
            <td class="label" style="width:10%;">{{ $idx + 1 }}</td>
            <td class="value">
                <strong>{{ $step['title'] ?? $step[0] ?? '' }}:</strong> {{ $step['desc'] ?? $step[1] ?? '' }}
            </td>
        </tr>
        @endforeach
    </table>

    <div style="margin-top:3mm; padding:2mm; background:#E8F5E9; border-right:3px solid #4CAF50;">
        <p style="margin:0; font-size:10pt; color:#2E7D32;">
            <strong>{{ $warningTitle }}</strong> {{ $warningText }}
        </p>
    </div>
</div>
