<h2>سياسة خدمة ما بعد البيع</h2>

<div class="section-box">
    <h3>♦ الضمان</h3>
    <ul>
        <li>ضمان عيوب التصنيع: <strong>{{ settings('defaults.warranty_months', 12) }} شهراً</strong> من تاريخ التركيب.</li>
        <li>ضمان الصاج والسلك المجلفن: <strong>{{ settings('defaults.warranty_years_steel', 12) }} سنة</strong> ضد الصدأ والتآكل.</li>
        <li>تشمل الأجزاء المستبدلة خلال فترة الضمان بالكامل.</li>
    </ul>

    <h3>♦ خدمات ما بعد البيع</h3>
    <ul>
        <li><strong>التركيب والتشغيل:</strong> إرسال فريق فني متخصص لتركيب البطاريات والتشغيل التجريبي.</li>
        <li><strong>التدريب:</strong> تدريب عملي لعمالة المزرعة على التشغيل والصيانة الأساسية.</li>
        <li><strong>الصيانة الدورية:</strong> زيارات صيانة دورية كل 3 أشهر (بعد انتهاء الضمان).</li>
        <li><strong>الدعم الفني:</strong> خط ساخن للاستشارات الفنية على مدار الساعة.</li>
        <li><strong>قطع الغيار:</strong> توفير قطع غيار أصلية لمدة {{ settings('defaults.spare_parts_years', 12) }} سنة بأسعار التكلفة.</li>
    </ul>

    <h3>♦ التواصل معنا</h3>
    <table class="info-table">
        <tr>
            <td class="label">العنوان</td>
            <td class="value">@setting('contact.address_ar')</td>
        </tr>
        <tr>
            <td class="label">الموبايل</td>
            <td class="value">{{ implode(' / ', settings('contact.phones', [])) }}</td>
        </tr>
        <tr>
            <td class="label">البريد</td>
            <td class="value">@setting('contact.email')</td>
        </tr>
        <tr>
            <td class="label">الموقع الإلكتروني</td>
            <td class="value">@setting('contact.website')</td>
        </tr>
    </table>

    <div style="margin-top:5mm; padding:3mm; background:#FFF5F5; border:2px solid {{ settings('branding.primary_color') }}; text-align:center;">
        <p style="font-size:12pt; font-weight:bold; color:{{ settings('branding.primary_color') }}; margin:0;">
            شكراً لاختياركم @setting('company.name_ar')
        </p>
        <p style="font-size:10pt; color:#666; margin:1mm 0 0 0;">
            نتمنى لكم كل التوفيق في مشروعكم
        </p>
    </div>
</div>
