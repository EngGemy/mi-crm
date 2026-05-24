@php
    use App\Support\BroilerWeightReference;
    $rows = BroilerWeightReference::rows();
    $selected = $selectedWeight ?? null;
    $totalNests = $totalNests ?? null;
    $showTotal = $totalNests !== null && $totalNests > 0;
@endphp

<div class="broiler-weight-table" style="overflow-x:auto;margin:12px 0">
    <p style="font-size:12px;color:#64748b;margin-bottom:8px">
        جدول السعة المعتمدة لكل عش حسب وزن الطائر المستهدف (تسمين)
    </p>
    <table style="width:100%;border-collapse:collapse;font-size:13px;text-align:center">
        <thead>
            <tr style="background:#C00000;color:#fff">
                <th style="padding:10px;border:1px solid #fecaca">وزن الطائر (كجم)</th>
                <th style="padding:10px;border:1px solid #fecaca">عدد الطيور / عش</th>
                @if($showTotal)
                    <th style="padding:10px;border:1px solid #fecaca">إجمالي الطيور ({{ number_format($totalNests) }} عش)</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                @php
                    $isSelected = $selected !== null && abs($row['weight_float'] - (float)$selected) < 0.001;
                @endphp
                <tr style="{{ $isSelected ? 'background:#fef2f2;font-weight:700;color:#991b1b' : '' }}"
                    data-weight="{{ $row['weight_kg'] }}"
                    data-birds="{{ $row['birds_per_nest'] }}">
                    <td style="padding:8px;border:1px solid #e2e8f0;direction:ltr">{{ $row['weight_kg'] }}</td>
                    <td style="padding:8px;border:1px solid #e2e8f0">{{ number_format($row['birds_per_nest']) }} طائر</td>
                    @if($showTotal)
                        <td style="padding:8px;border:1px solid #e2e8f0;direction:ltr;font-weight:600">
                            {{ number_format($totalNests * $row['birds_per_nest']) }}
                        </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
