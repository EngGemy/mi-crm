<div class="card" style="margin-bottom:6mm;">
    <table width="100%" cellpadding="0" cellspacing="0" class="rule">
        <tr>
            <td width="12%" class="idx" style="padding-bottom:1mm;">{{ $n_includes }}</td>
            <td class="title" style="padding-bottom:1mm;">العرض شامل</td>
        </tr>
    </table>
    <table class="clean" width="100%" cellpadding="0" cellspacing="0">
        @foreach ($includes as $line)
            <tr class="{{ $loop->even ? 'alt' : '' }}">
                <td>{{ $line }}</td>
            </tr>
        @endforeach
    </table>
    <div style="margin-top:3mm; font-weight:bold;">{{ $validity_line }}</div>
</div>

<div class="card" style="margin-bottom:6mm;">
    <table width="100%" cellpadding="0" cellspacing="0" class="rule">
        <tr>
            <td width="12%" class="idx" style="padding-bottom:1mm;">{{ $n_warranty }}</td>
            <td class="title" style="padding-bottom:1mm;">بند الضمان</td>
        </tr>
    </table>
    @foreach ($warranty as $line)
        <div style="margin-top:1.5mm;">{{ $line }}</div>
    @endforeach
</div>

<div class="card" style="margin-bottom:6mm;">
    <table width="100%" cellpadding="0" cellspacing="0" class="rule">
        <tr>
            <td width="12%" class="idx" style="padding-bottom:1mm;">{{ $n_install }}</td>
            <td class="title" style="padding-bottom:1mm;">بند التركيب</td>
        </tr>
    </table>
    @foreach ($installation as $line)
        <div style="margin-top:1.5mm;">{{ $line }}</div>
    @endforeach
</div>

<div class="card" style="margin-bottom:6mm;">
    <table width="100%" cellpadding="0" cellspacing="0" class="rule">
        <tr>
            <td width="12%" class="idx" style="padding-bottom:1mm;">{{ $n_spares }}</td>
            <td class="title" style="padding-bottom:1mm;">بند قطع الغيار</td>
        </tr>
    </table>
    @foreach ($spare_parts_notes as $line)
        <div style="margin-top:1.5mm;">{{ $line }}</div>
    @endforeach
</div>
