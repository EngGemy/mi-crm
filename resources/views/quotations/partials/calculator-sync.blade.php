@php
    $snapshot = ($quotation ?? null)?->pricing_snapshot ?? [];
    $technical = $snapshot['technical'] ?? [];
    $computed = $snapshot['computed'] ?? [];
    $hasSnapshot = ! empty($technical);
@endphp

@if($hasSnapshot)
    <div class="page-break-before" style="page-break-before: always;"></div>
    <h2>البيانات الفنية وجدول عدد الطيور | Technical Data &amp; Bird Count</h2>

    <table class="info-table" style="margin-bottom: 5mm;">
        <tr>
            <td class="label">وزن الطائر (كجم)</td>
            <td class="value">{{ $technical['bird_weight_kg'] ?? '-' }}</td>
            <td class="label">عدد الطيور / العش</td>
            <td class="value">{{ $technical['birds_per_nest'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">الطول الفعّال (م)</td>
            <td class="value">{{ $computed['effective_length'] ?? $technical['effective_length'] ?? '-' }}</td>
            <td class="label">عدد الأعشاش / خط</td>
            <td class="value">{{ $computed['nests_per_line'] ?? $technical['nests_per_line'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">إجمالي الأعشاش</td>
            <td class="value">{{ $computed['total_nests'] ?? $technical['total_nests'] ?? '-' }}</td>
            <td class="label">إجمالي عدد الطيور (السعة)</td>
            <td class="value" style="font-weight:bold; color:{{ settings('branding.primary_color') }};">
                {{ $computed['bird_count'] ?? $technical['total_birds'] ?? '-' }}
            </td>
        </tr>
    </table>

    <h3>التجهيزات والمعدات | Equipment</h3>
    <table class="info-table" style="margin-bottom: 5mm;">
        <tr>
            <td class="label">الشفاطات الرئيسية</td>
            <td class="value">{{ $computed['main_fans_count'] ?? $technical['main_fans_count'] ?? '-' }}</td>
            <td class="label">معادلة الشفاطات</td>
            <td class="value" style="font-size:9pt; direction:ltr; text-align:right;">
                {{ $technical['fan_formula'] ?? '-' }}
            </td>
        </tr>
        <tr>
            <td class="label">وحدات التبريد (م)</td>
            <td class="value">{{ $computed['cooling_units'] ?? $technical['cooling_pad_length_m'] ?? '-' }}</td>
            <td class="label">معادلة التبريد</td>
            <td class="value" style="font-size:9pt; direction:ltr; text-align:right;">
                {{ $technical['cooling_formula'] ?? '-' }}
            </td>
        </tr>
        <tr>
            <td class="label">الشبابيك (Inlets)</td>
            <td class="value">{{ $computed['windows_count'] ?? $technical['air_windows_count'] ?? '-' }}</td>
            <td class="label">الشفاطات الجانبية</td>
            <td class="value">{{ $computed['side_fans_count'] ?? $technical['side_fans_count'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">الدفايات</td>
            <td class="value">{{ $computed['heaters_count'] ?? $technical['heaters_count'] ?? '-' }}</td>
            <td class="label">—</td>
            <td class="value">—</td>
        </tr>
    </table>

    <h3>المساحات | Areas</h3>
    <table class="info-table" style="margin-bottom: 5mm;">
        <tr>
            <td class="label">مساحة الخرسانة (م²)</td>
            <td class="value">{{ number_format((float) ($computed['concrete_area'] ?? 0), 2) }}</td>
            <td class="label">مساحة الاستيل (م²)</td>
            <td class="value">{{ number_format((float) ($computed['steel_area'] ?? 0), 2) }}</td>
        </tr>
        <tr>
            <td class="label">مساحة الحوائط (م²)</td>
            <td class="value">{{ number_format((float) ($computed['walls_area'] ?? 0), 2) }}</td>
            <td class="label">—</td>
            <td class="value">—</td>
        </tr>
    </table>
@endif
