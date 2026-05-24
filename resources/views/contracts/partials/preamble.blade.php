<h2 style="font-size: 14pt; border-bottom: 2px solid #1a1a1a; padding-bottom: 3mm; margin-top: 10mm;">التمهيد</h2>

<div style="margin: 5mm 0; text-align: justify;">
    <p style="margin: 2mm 0;">
        حيث أن الطرف الأول شركة متخصصة في تصنيع وتوريد وتركيب
        {{ settings('company.tagline_ar', 'بطاريات الدواجن الأوتوماتيكية') }} والمنشآت المعدنية لمزارع الدواجن،
        وحيث أن الطرف الثاني راغب في توريد وتركيب
        {{ $contract->project_name }}
        @if($contract->installation_location)
            بموقع: {{ $contract->installation_location }}
        @endif
        @if($contract->hall_count > 1)
            ({{ $contract->hall_count }} عنابر)
        @endif
        .
    </p>
</div>
