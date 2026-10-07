<div class="card" style="margin-bottom:6mm;">
    <table width="100%" cellpadding="0" cellspacing="0" class="rule">
        <tr>
            <td width="12%" class="idx" style="padding-bottom:1mm;">{{ $n_specs }}</td>
            <td class="title" style="padding-bottom:1mm;">المواصفات الفنية</td>
        </tr>
    </table>
    @foreach ($spec_groups as $group)
        <div class="muted" style="margin-top:3mm;">{{ $group['title'] }}</div>
        <table class="clean" width="100%" cellpadding="0" cellspacing="0">
            <thead>
                <tr>
                    <th width="62%">البيان</th>
                    <th width="38%" class="num">القيمة</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($group['rows'] as $row)
                    <tr class="{{ $loop->even ? 'alt' : '' }}">
                        <td>{{ $row['label'] }}</td>
                        <td class="num">{{ $row['value'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:4mm;">
        <tr>
            <td width="50%" class="card" style="padding-left:3mm;">
                <div class="faint">مساحة التسكين لكل دجاجة</div>
                <div class="mid num">{{ $stocking['area'] }}</div>
                <div class="formula">{{ $stocking['area_equation'] }}</div>
            </td>
            <td width="50%" class="card">
                <div class="faint">منطقة التغذية لكل دجاجة</div>
                <div class="mid num">{{ $stocking['feeding'] }}</div>
                <div class="formula">{{ $stocking['feeding_equation'] }}</div>
            </td>
        </tr>
    </table>
</div>
