<div class="card" style="margin-bottom:6mm;">
    <table width="100%" cellpadding="0" cellspacing="0" class="rule">
        <tr>
            <td width="12%" class="idx" style="padding-bottom:1mm;">{{ $n_clean }}</td>
            <td class="title" style="padding-bottom:1mm;">دليل تطهير عنابر بطاريات الدواجن الأوتوماتيك</td>
        </tr>
    </table>
    <div class="muted" style="margin-top:3mm;">الأهداف</div>
    @foreach ($disinfection['goals'] as $line)
        <div style="margin-top:1.2mm;">{{ $line }}</div>
    @endforeach
    <div class="muted" style="margin-top:3mm;">التوصيات</div>
    @foreach ($disinfection['recommendations'] as $line)
        <div style="margin-top:1.2mm;">{{ $line }}</div>
    @endforeach
    <div class="muted" style="margin-top:3mm;">خطوات التنظيف والتطهير</div>
    @foreach ($disinfection['steps'] as $line)
        <div style="margin-top:1.2mm;">{{ $line }}</div>
    @endforeach
    <div class="muted" style="margin-top:3mm;">ملاحظات هامة</div>
    @foreach ($disinfection['notes'] as $line)
        <div style="margin-top:1.2mm;">{{ $line }}</div>
    @endforeach
</div>
