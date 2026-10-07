@php
    $hero = $images['cover'][0] ?? null;
@endphp
<table width="100%" cellpadding="0" cellspacing="0" class="card">
    <tr>
        <td height="188mm" style="padding:0; background-color:#111111;">
            @if ($hero)
                <img src="{{ $hero['path'] }}" width="210mm" height="188mm" />
            @endif
        </td>
    </tr>
    <tr>
        <td height="109mm" bgcolor="#FFFFFF" style="padding:14mm 16mm 12mm 16mm;">
            <div class="faint" style="letter-spacing:1px;">عرض مالي وفني</div>
            <div class="title" style="font-size:20pt; margin-top:2mm;">بطاريات دواجن أوتوماتيك</div>
            <div class="muted" style="margin-top:2mm;">مقدم إلى</div>
            <div class="mid" style="margin-bottom:6mm;">{{ $client }}</div>
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="33%" style="padding:2mm 0; border-top:0.6pt solid {{ $accent }};">
                        <div class="faint">نوع العنبر</div>
                        <div>{{ $project_type }}</div>
                    </td>
                    <td width="33%" style="padding:2mm 3mm; border-top:0.6pt solid {{ $accent }};">
                        <div class="faint">التاريخ</div>
                        <div class="num">{{ $issued_at }}</div>
                    </td>
                    <td width="34%" style="padding:2mm 0; border-top:0.6pt solid {{ $accent }};">
                        <div class="faint">الصلاحية</div>
                        <div class="num">{{ $validity_date }}</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding-top:4mm;">
                        <div class="faint">مكان المشروع</div>
                        <div>{{ $location }}</div>
                    </td>
                    <td style="padding:4mm 3mm 0 3mm;">
                        <div class="faint">الطول</div>
                        <div class="num">{{ $length }}</div>
                    </td>
                    <td style="padding-top:4mm;">
                        <div class="faint">العرض / الارتفاع</div>
                        <div class="num">{{ $width }} / {{ $height }}</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
