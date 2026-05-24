<div class="section-box" style="border:none; padding:0;">
    <h2 style="text-align:center; border:none; background:none; font-size:20pt;">@setting('company.name_ar')</h2>
    <h3 style="text-align:center; color:#666; font-size:12pt; margin-top:0;">@setting('company.tagline_ar')</h3>

    <div style="margin:5mm 0; padding:4mm; background:#FAFAFA; border-right:4px solid {{ settings('branding.primary_color') }};">
        <p style="font-size:11pt; line-height:1.8;">
            {!! nl2br(e(settings('company.about_ar'))) !!}
        </p>
    </div>

    <h3>♦ رسالتنا</h3>
    <p>توفير حلول متكاملة لمزارع الدواجن بأعلى معايير الجودة العالمية وبأسعار تنافسية،
    مع ضمان استمرارية الدعم الفني ما بعد البيع.</p>

    <h3>♦ رؤيتنا</h3>
    <p>أن نكون الشريك الأول لكل مستثمر في مجال إنتاج الدواجن في مصر والشرق الأوسط وإفريقيا.</p>

    <h3>♦ منتجاتنا الرئيسية</h3>
    <ul>
        <li>بطاريات تسمين الدواجن (3 و 4 أدوار)</li>
        <li>بطاريات بياض الدواجن (شكل H)</li>
        <li>منظومات التغذية والشرب الأوتوماتيكية</li>
        <li>منظومات التهوية والتبريد والتدفئة</li>
        <li>الأعمال المعدنية والإنشائية للعنابر</li>
        <li>لوحات الكهرباء وأنظمة التحكم الآلي</li>
    </ul>

    <h3>♦ لماذا نحن؟</h3>
    <ul>
        <li><strong>جودة عالمية:</strong> خامات أوروبية وصاج مجلفن معالج ضد الصدأ</li>
        <li><strong>ضمان طويل:</strong> {{ settings('defaults.warranty_years_steel', 12) }} سنة للصاج والسلك ضد الصدأ</li>
        <li><strong>دعم فني:</strong> فريق متخصص للتركيب والتدريب والصيانة</li>
        <li><strong>سرعة التنفيذ:</strong> مدة تصنيع تبدأ من {{ settings('defaults.manufacturing_days', 105) }} يوم عمل</li>
        <li><strong>تجربة عملاء:</strong> مئات المشاريع المنفذة في مصر والسعودية والعراق والسودان</li>
    </ul>

    <div style="margin-top:6mm; padding:3mm; background:#FFF5F5; border:1px solid {{ settings('branding.primary_color') }}; text-align:center;">
        <p style="font-size:11pt; font-weight:bold; color:{{ settings('branding.primary_color') }}; margin:0;">
            @setting('company.name_ar') — شريكك الموثوق في نجاح مشروعك
        </p>
    </div>
</div>
