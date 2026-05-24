<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>عرض سعر — {{ $clientName }}</title>
    <style>
        body { font-family: 'cairo', 'DejaVu Sans', sans-serif; font-size: 10.5pt; color: #1e293b; direction: rtl; margin: 0; padding: 0; }
        h1 { color: #C00000; font-size: 16pt; margin: 0 0 6px; }
        .meta { color: #64748b; font-size: 9pt; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; page-break-inside: avoid; }
        th { background: #C00000; color: #fff; padding: 6px; text-align: center; font-size: 9.5pt; }
        td { border: 1px solid #e2e8f0; padding: 5px 6px; }
        .section-header td { background: #333; color: #fff; font-weight: 700; text-align: center; font-size: 11pt; }
        .section-subtotal td { background: #f8fafc; font-weight: 700; }
        .num { direction: ltr; text-align: center; }
        .totals td { font-weight: 700; }
        .grand { background: #C00000; color: #fff; }
    </style>
</head>
<body>
    <htmlpagefooter name="signature">
        <div style="font-size: 8pt; color: #666; border-top: 1px dashed #ccc; padding-top: 2mm; margin-top: 2mm; text-align: center;">
            توقيع الطرف الثاني: .................................................... | صفحة {PAGENO} من {nbpg}
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="signature" page="ALL" value="on" show-this-page="1" />

    <h1>عرض سعر — عنبر دواجن</h1>
    <div class="meta">
        العميل: {{ $clientName }}
        @if($clientPhone) | {{ $clientPhone }} @endif
        | {{ $projectLabel }} | {{ $scopeLabel }}
        | {{ now()->format('Y-m-d') }}
    </div>

    @php
        $orderedGroups = [];
        foreach (['الإنشاءات', 'بطاريات العنبر', 'المشتملات'] as $label) {
            if (isset($groupedItems[$label])) {
                $orderedGroups[$label] = $groupedItems[$label];
            }
        }
    @endphp

    @foreach($orderedGroups as $sectionName => $rows)
        <table>
            <thead>
                <tr class="section-header">
                    <td colspan="4">{{ $sectionName }}</td>
                </tr>
                <tr>
                    <th style="width: 45%;">البند</th>
                    <th style="width: 15%;">الكمية</th>
                    <th style="width: 15%;">الوحدة</th>
                    <th style="width: 25%;">الإجمالي (ج.م)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td>{{ $row['desc_ar'] }}</td>
                        @if($row['hide_unit_details'] ?? false)
                            <td class="num" colspan="2" style="color:#666; font-size:9pt;">—</td>
                        @else
                            <td class="num">{{ is_float($row['qty']) ? number_format($row['qty'], 2) : number_format($row['qty']) }}</td>
                            <td class="num">{{ $row['unit'] }}</td>
                        @endif
                        <td class="num">{{ number_format($row['total_price'], 2) }}</td>
                    </tr>
                @endforeach
                <tr class="section-subtotal">
                    <td colspan="3" style="text-align:right;">مجموع {{ $sectionName }}</td>
                    <td class="num">{{ number_format($groupSubtotals[$sectionName] ?? 0, 2) }}</td>
                </tr>
            </tbody>
        </table>
    @endforeach

    <table>
        <tr class="totals"><td colspan="3">المجموع الفرعي</td><td class="num">{{ number_format($subtotal, 2) }}</td></tr>
        @if($vatAmount > 0)
        <tr class="totals"><td colspan="3">الضريبة</td><td class="num">{{ number_format($vatAmount, 2) }}</td></tr>
        @endif
        <tr class="totals grand"><td colspan="3">الإجمالي النهائي (جنيه)</td><td class="num">{{ number_format($total, 2) }}</td></tr>
        <tr class="totals"><td colspan="3">تقريبي بالدولار (سعر {{ number_format($usdRate, 2) }})</td><td class="num">{{ number_format($totalUsd, 2) }} $</td></tr>
    </table>

    @if(!empty($technical['fan_formula']))
    <p style="font-size:8pt;color:#64748b">الشفاطات: {{ $technical['fan_formula'] }}</p>
    @endif
    @if(!empty($technical['cooling_formula']))
    <p style="font-size:8pt;color:#64748b">التبريد: {{ $technical['cooling_formula'] }}</p>
    @endif
</body>
</html>
