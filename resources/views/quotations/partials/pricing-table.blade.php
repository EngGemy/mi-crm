<h2>البند المالي والفني | Financial & Technical Proposal</h2>

@php
    $snapshot = ($quotation ?? null)?->pricing_snapshot ?? [];
    $snapshotItems = $snapshot['items'] ?? [];
    $useSnapshot = ! empty($snapshotItems);

    if ($useSnapshot) {
        $orderedGroups = [];
        foreach (['الإنشاءات', 'بطاريات العنبر', 'المشتملات'] as $label) {
            $orderedGroups[$label] = [];
        }
        foreach ($snapshotItems as $item) {
            $groupName = \App\Support\PoultrySectionLabels::groupLabel($item['section'] ?? 'general');
            if (! isset($orderedGroups[$groupName])) {
                $orderedGroups[$groupName] = [];
            }
            $orderedGroups[$groupName][] = (object) [
                'description_ar' => $item['desc_ar'] ?? '',
                'description_en' => $item['desc_en'] ?? '',
                'unit' => $item['unit'] ?? '',
                'quantity' => $item['qty'] ?? 0,
                'unit_price' => $item['unit_price'] ?? 0,
                'total_price' => $item['total_price'] ?? 0,
                'is_taxable' => $item['is_taxable'] ?? true,
                'hide_unit_details' => $item['hide_unit_details'] ?? false,
                'section' => (object) ['category' => $item['section'] ?? ''],
            ];
        }
        $orderedGroups = array_filter($orderedGroups);
        $groupSubtotals = [];
        foreach ($orderedGroups as $label => $items) {
            $groupSubtotals[$label] = collect($items)->sum(fn ($i) => (float) $i->total_price);
        }
    } else {
        $orderedGroups = [];
        foreach (['الإنشاءات', 'بطاريات العنبر', 'المشتملات'] as $label) {
            if (isset($groupedItems[$label])) {
                $orderedGroups[$label] = $groupedItems[$label];
            }
        }
        foreach ($groupedItems as $label => $items) {
            if (! isset($orderedGroups[$label])) {
                $orderedGroups[$label] = $items;
            }
        }
    }
@endphp

@foreach($orderedGroups as $sectionName => $groupItems)
    @php
        $groupTotal = $groupSubtotals[$sectionName] ?? collect($groupItems)->sum(fn ($item) => (float) $item->total_price);
    @endphp
    <table dir="rtl" style="margin-bottom: 8mm; page-break-inside: avoid;">
        <thead>
            <tr style="background:#333 !important; color:white;">
                <th colspan="6" style="background:#333; color:white; font-weight:bold; text-align:center; font-size:12pt;">
                    {{ $sectionName }}
                </th>
            </tr>
            <tr>
                <th style="width:32%;">
                    البند
                    <br><span style="font-size:8pt; font-weight:normal;">Description</span>
                </th>
                <th style="width:13%;">
                    سعر الوحدة
                    <br><span style="font-size:8pt; font-weight:normal;">Unit price</span>
                </th>
                <th style="width:10%;">
                    الوحدة
                    <br><span style="font-size:8pt; font-weight:normal;">Unit</span>
                </th>
                <th style="width:10%;">
                    الكمية
                    <br><span style="font-size:8pt; font-weight:normal;">Each</span>
                </th>
                <th style="width:10%;">
                    الضريبة
                    <br><span style="font-size:8pt; font-weight:normal;">Taxed</span>
                </th>
                <th style="width:13%;">
                    المبلغ
                    <br><span style="font-size:8pt; font-weight:normal;">Amount</span>
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach($groupItems as $item)
            @php
                $isBattery = ($item->section?->category ?? '') === 'cages'
                    || str_contains($item->description_ar ?? '', 'بطاريات العنبر')
                    || ($item->hide_unit_details ?? false);
            @endphp
            <tr>
                <td style="text-align:right;">
                    {{ $item->description_ar ?: $item->description_en }}
                    @if($item->description_en && $item->description_ar)
                        <br><span style="font-size:9pt; color:#666;">{{ $item->description_en }}</span>
                    @endif
                </td>
                @if($isBattery)
                    <td class="text-center" colspan="4" style="text-align:center; color:#666; font-size:9pt;">
                        —
                    </td>
                @else
                    <td class="text-center" dir="ltr">{{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="text-center">{{ \App\Support\QuotationPdfLabels::unit($item->unit) }}</td>
                    <td class="text-center" dir="ltr">{{ number_format((float) $item->quantity, 2) }}</td>
                    <td class="text-center">
                        @if($item->is_taxable)
                            نعم
                        @else
                            لا
                        @endif
                    </td>
                @endif
                <td class="text-center" style="font-weight:bold;" dir="ltr">
                    {{ number_format((float) $item->total_price, 2) }}
                </td>
            </tr>
            @endforeach
            <tr style="background:#f0f0f0; font-weight:bold;">
                <td colspan="5" style="text-align:right;">مجموع {{ $sectionName }}</td>
                <td class="text-center" dir="ltr">{{ number_format((float) $groupTotal, 2) }}</td>
            </tr>
        </tbody>
    </table>
@endforeach

@if(count($orderedGroups) === 0)
    <table dir="rtl">
        <tbody>
            <tr>
                <td colspan="6" class="text-center text-gray" style="text-align:center;">
                    لا توجد بنود تسعير
                </td>
            </tr>
        </tbody>
    </table>
@endif
