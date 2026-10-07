<div class="card">
    <table width="100%" cellpadding="0" cellspacing="0" class="rule">
        <tr>
            <td width="12%" class="idx" style="padding-bottom:1mm;">{{ $n_service }}</td>
            <td class="title" style="padding-bottom:1mm;">سياسة خدمة ما بعد البيع لدى شركة MI</td>
        </tr>
    </table>
    <div class="en" style="margin-top:1mm;">For Automatic Poultry Cages</div>
    @foreach ($after_sales as $line)
        <div style="margin-top:1.5mm;">{{ $line }}</div>
    @endforeach
</div>
