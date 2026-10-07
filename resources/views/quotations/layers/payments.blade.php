<div class="card" style="margin-bottom:6mm;">
    <table width="100%" cellpadding="0" cellspacing="0" class="rule">
        <tr>
            <td width="12%" class="idx" style="padding-bottom:1mm;">{{ $n_payments }}</td>
            <td class="title" style="padding-bottom:1mm;">بند التعاقد</td>
        </tr>
    </table>
    <div style="margin:2mm 0 3mm 0;">{{ $payment_intro }}</div>
    <table class="clean" width="100%" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th width="40%">النسبة %</th>
                <th width="30%" class="num">المبلغ USD</th>
                <th width="30%" class="num">المبلغ EGP</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($payments['rows'] as $row)
                <tr class="{{ $loop->even ? 'alt' : '' }}">
                    <td>{{ $row['label'] }} — {{ $row['percent'] }}</td>
                    <td class="num">{{ $row['usd'] }}</td>
                    <td class="num">{{ $row['egp'] }}</td>
                </tr>
            @endforeach
            <tr>
                <td class="totalbox" style="font-weight:bold;">الإجمالي</td>
                <td class="num totalbox">{{ $payments['total']['usd'] }}</td>
                <td class="num totalbox" style="font-size:18pt;">{{ $payments['total']['egp'] }}</td>
            </tr>
        </tbody>
    </table>
</div>
