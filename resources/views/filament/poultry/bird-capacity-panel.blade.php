{{-- لوحة سعة الطيور — واضحة وفعّالة --}}
@php
    use App\Support\BroilerWeightReference;
    /** @var \App\Models\PoultryQuotation $record */
    $weight = (float) ($record->bird_weight_kg ?? 0);
    $nests = (int) ($record->total_nests ?? 0);
    $birdsPerNest = (int) ($record->birds_per_nest ?? BroilerWeightReference::birdsPerNest($weight ?: 2.1));
    $birdCount = (int) ($record->bird_count ?? 0);
    $rows = BroilerWeightReference::rows();
@endphp

<div style="border:1px solid #fecaca;border-radius:16px;overflow:hidden;background:#fff;box-shadow:0 8px 24px rgba(192,0,0,0.06);">
    <div style="background:linear-gradient(135deg,#C00000,#8B0000);color:#fff;padding:16px 20px;">
        <div style="font-size:11px;opacity:.85;letter-spacing:.04em;">ملاحظة هامة — سعة العنبر</div>
        <div style="font-size:18px;font-weight:800;margin-top:4px;">عدد الطيور يعتمد على وزن الطائر المستهدف</div>
        <div style="font-size:13px;opacity:.92;margin-top:8px;line-height:1.6;">
            كل وزن له عدد طيور معتمد لكل عش. عند تغيير الوزن يتغير
            <strong>عدد الطيور / عش</strong> وبالتالي
            <strong>إجمالي السعة</strong> وسعر البطاريات.
            استخدم زر «تغيير وزن الطائر» أعلاه للتحكم في العدد.
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;padding:16px 20px;background:#fff7f7;">
        <div style="background:#fff;border:1px solid #fecaca;border-radius:12px;padding:14px;text-align:center;">
            <div style="font-size:11px;color:#991b1b;">الوزن المختار</div>
            <div style="font-size:22px;font-weight:800;color:#C00000;direction:ltr;margin-top:4px;">
                {{ $weight > 0 ? number_format($weight, 3) : '—' }}
            </div>
            <div style="font-size:11px;color:#64748b;">كجم / طائر</div>
        </div>
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px;text-align:center;">
            <div style="font-size:11px;color:#64748b;">طيور / عش</div>
            <div style="font-size:22px;font-weight:800;color:#0f172a;margin-top:4px;">{{ number_format($birdsPerNest) }}</div>
            <div style="font-size:11px;color:#64748b;">حسب الوزن</div>
        </div>
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px;text-align:center;">
            <div style="font-size:11px;color:#64748b;">إجمالي الأعشاش</div>
            <div style="font-size:22px;font-weight:800;color:#0f172a;margin-top:4px;">{{ number_format($nests) }}</div>
            <div style="font-size:11px;color:#64748b;">عش</div>
        </div>
        <div style="background:#0f172a;border-radius:12px;padding:14px;text-align:center;color:#fff;">
            <div style="font-size:11px;opacity:.75;">السعة المعتمدة</div>
            <div style="font-size:22px;font-weight:800;color:#fecaca;margin-top:4px;">{{ number_format($birdCount) }}</div>
            <div style="font-size:11px;opacity:.75;">طائر</div>
        </div>
    </div>

    <div style="padding:0 20px 8px;font-size:12px;color:#64748b;">
        معادلة السعة:
        <span style="direction:ltr;display:inline-block;font-weight:700;color:#334155;">
            {{ number_format($nests) }} عش × {{ number_format($birdsPerNest) }} طائر/عش = {{ number_format($birdCount) }} طائر
        </span>
    </div>

    <div style="padding:8px 20px 20px;overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;text-align:center;">
            <thead>
                <tr style="background:#111827;color:#fff;">
                    <th style="padding:12px;border:1px solid #1f2937;">وزن الطائر (كجم)</th>
                    <th style="padding:12px;border:1px solid #1f2937;">عدد الطيور / عش</th>
                    <th style="padding:12px;border:1px solid #1f2937;">إجمالي الطيور</th>
                    <th style="padding:12px;border:1px solid #1f2937;">الحالة</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    @php
                        $isSelected = $weight > 0 && abs($row['weight_float'] - $weight) < 0.001;
                        $rowTotal = $nests > 0 ? $nests * $row['birds_per_nest'] : null;
                    @endphp
                    <tr style="{{ $isSelected ? 'background:#fef2f2;font-weight:700;' : ($loop->even ? 'background:#f8fafc;' : '') }}">
                        <td style="padding:10px;border:1px solid #e2e8f0;direction:ltr;{{ $isSelected ? 'color:#991b1b;' : '' }}">
                            {{ $row['weight_kg'] }}
                        </td>
                        <td style="padding:10px;border:1px solid #e2e8f0;{{ $isSelected ? 'color:#991b1b;' : '' }}">
                            {{ number_format($row['birds_per_nest']) }} طائر
                        </td>
                        <td style="padding:10px;border:1px solid #e2e8f0;direction:ltr;{{ $isSelected ? 'color:#991b1b;' : '' }}">
                            {{ $rowTotal !== null ? number_format($rowTotal) : '—' }}
                        </td>
                        <td style="padding:10px;border:1px solid #e2e8f0;">
                            @if($isSelected)
                                <span style="display:inline-block;background:#C00000;color:#fff;padding:4px 10px;border-radius:999px;font-size:11px;">المُطبَّق على العرض</span>
                            @else
                                <span style="color:#94a3b8;font-size:12px;">بديل</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p style="margin:12px 0 0;font-size:12px;color:#64748b;line-height:1.6;">
            • الصف الأحمر هو الوزن المُختار لهذا العرض.<br>
            • سعر البطاريات يُحسب على إجمالي الطيور الناتج عن الوزن المختار.<br>
            • الأوزان الأخرى معروضة للمقارنة فقط ولا تُطبَّق إلا بعد اختيارها.
        </p>
    </div>
</div>
