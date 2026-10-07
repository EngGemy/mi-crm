<div class="card" style="margin-bottom:6mm;">
    <table width="100%" cellpadding="0" cellspacing="0" class="rule">
        <tr>
            <td width="12%" class="idx" style="padding-bottom:1mm;">{{ $n_money }}</td>
            <td class="title" style="padding-bottom:1mm;">البند المالي والفني لتجهيز بطاريات دواجن أوتوماتيك</td>
        </tr>
    </table>
    <table class="clean" width="100%" cellpadding="0" cellspacing="0" style="margin-top:3mm;">
        <thead>
            <tr>
                <th width="40%">البيان</th>
                <th width="12%">الوحدة</th>
                <th width="12%">الضريبة</th>
                <th width="18%" class="num">USD</th>
                <th width="18%" class="num">EGP</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $offer['description'] }}</td>
                <td>{{ $offer['unit'] }}</td>
                <td>{{ $offer['taxed'] }}</td>
                <td class="num">{{ $offer['barn_usd'] }}</td>
                <td class="num">{{ $offer['barn_egp'] }}</td>
            </tr>
            @foreach ($offer['rows'] as $row)
                <tr class="{{ $loop->last ? '' : 'alt' }}">
                    <td colspan="3" style="{{ $loop->last ? 'font-weight:bold;' : '' }}">{{ $row['label'] }}</td>
                    <td class="num" style="{{ $loop->last ? 'font-size:18pt;' : '' }}">{{ $row['usd'] }}</td>
                    <td class="num totalbox" style="{{ $loop->last ? 'font-size:18pt;' : '' }}">{{ $row['egp'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="faint" style="margin-top:2mm;">CUSTOMER ID: {{ $quote_number }} — DATE: {{ $issued_at }}</div>
</div>
