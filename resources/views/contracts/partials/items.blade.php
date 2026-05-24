@if($contract->items && $contract->items->isNotEmpty())
<h2 style="font-size: 14pt; border-bottom: 2px solid #1a1a1a; padding-bottom: 3mm; margin-top: 10mm;">بنود التوريد والتركيب</h2>

<table style="width: 100%; border-collapse: collapse; margin: 5mm 0; font-size: 11pt;">
    <thead>
        <tr>
            <th style="width: 5%; border: 1px solid #1a1a1a; padding: 6px 10px; text-align: center; background: #f0f0f0; font-weight: bold;">م</th>
            <th style="border: 1px solid #1a1a1a; padding: 6px 10px; text-align: right; background: #f0f0f0; font-weight: bold;">البيان</th>
            <th style="width: 12%; border: 1px solid #1a1a1a; padding: 6px 10px; text-align: center; background: #f0f0f0; font-weight: bold;">الوحدة</th>
            <th style="width: 10%; border: 1px solid #1a1a1a; padding: 6px 10px; text-align: center; background: #f0f0f0; font-weight: bold;">الكمية</th>
            <th style="width: 15%; border: 1px solid #1a1a1a; padding: 6px 10px; text-align: center; background: #f0f0f0; font-weight: bold;">سعر الوحدة</th>
            <th style="width: 18%; border: 1px solid #1a1a1a; padding: 6px 10px; text-align: center; background: #f0f0f0; font-weight: bold;">الإجمالي</th>
        </tr>
    </thead>
    <tbody>
        @foreach($contract->items as $idx => $item)
            <tr>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px; text-align: center;">{{ $idx + 1 }}</td>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px;">{{ $item->description_ar }}</td>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px; text-align: center;">{{ $item->unit ?? '-' }}</td>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px; text-align: center; direction: ltr;">
                    {{ number_format((float) $item->quantity, 2) }}
                </td>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px; text-align: center; direction: ltr;">
                    {{ number_format((float) $item->unit_price, 2) }}
                </td>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px; text-align: center; direction: ltr; font-weight: bold;">
                    {{ number_format((float) $item->total_price, 2) }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
@endif
