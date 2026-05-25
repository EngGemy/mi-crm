@php
    $snapshot   = ($quotation ?? null)?->pricing_snapshot ?? [];
    $technical  = $snapshot['technical'] ?? [];
    $computed   = $snapshot['computed']  ?? [];
    $snapInputs = $snapshot['inputs']    ?? [];
    $hasSnapshot = ! empty($technical);

    $hallLength  = $snapInputs['hall_length']   ?? $technical['barn_length']     ?? null;
    $hallWidth   = $snapInputs['hall_width']    ?? $technical['barn_width']      ?? null;
    $hallHeight  = $snapInputs['hall_height']   ?? null;
    $serviceLen  = $snapInputs['service_length'] ?? $technical['service_length'] ?? null;
    $effectiveL  = $computed['effective_length']  ?? $technical['effective_length'] ?? null;
    $lines       = $technical['lines']           ?? $snapInputs['lines']          ?? null;
    $tiers       = $technical['tiers']           ?? $snapInputs['tiers']          ?? null;
    $birdWeight  = $technical['bird_weight_kg']  ?? $snapInputs['bird_weight_kg'] ?? null;
    $birdsPerN   = $technical['birds_per_nest']  ?? $snapInputs['birds_per_nest'] ?? null;

    $nestsPerLine = $computed['nests_per_line'] ?? $technical['nests_per_line'] ?? null;
    $totalNests   = $computed['total_nests']   ?? $technical['total_nests']    ?? null;
    $totalBirds   = $computed['bird_count']    ?? $technical['total_birds']    ?? null;
    $mainFans     = $computed['main_fans_count'] ?? $technical['main_fans_count'] ?? $technical['rear_fans_count'] ?? null;
    $fanFormula   = $technical['fan_formula']    ?? null;
    $coolingM     = $computed['cooling_pad_length_m'] ?? $technical['cooling_pad_length_m'] ?? null;
    $coolingFormula = $technical['cooling_formula'] ?? null;
    $windows      = $computed['windows_count']  ?? $technical['air_windows_count']  ?? null;
    $sideFans     = $computed['side_fans_count'] ?? $technical['side_fans_count']   ?? null;
    $heaters      = $computed['heaters_count']  ?? $technical['heaters_count']      ?? null;

    $projectTypeRaw = $snapshot['project_type'] ?? $snapInputs['project_type'] ?? null;
    $projectTypeAr  = match ($projectTypeRaw) {
        'broiler'       => 'تسمين',
        'layer'         => 'بياض',
        'layer_rearing' => 'تربية بياض',
        default         => $projectTypeRaw,
    };

    $nestsFormula = ($effectiveL && $tiers)
        ? "{$effectiveL} م × 2 × {$tiers} أدوار"
        : null;
    $totalNestsFormula = ($nestsPerLine && $lines)
        ? "{$nestsPerLine} × {$lines} خطوط"
        : null;
    $totalBirdsFormula = ($totalNests && $birdsPerN)
        ? "{$totalNests} × {$birdsPerN} طيور/عش"
        : null;
@endphp

@if($hasSnapshot)
    <div class="page-break-before" style="page-break-before: always;"></div>
    <h2>البيانات الفنية وجدول عدد الطيور | Technical Data &amp; Bird Count</h2>

    {{-- جدول أ: مواصفات العنبر --}}
    <h3>أولًا: مواصفات العنبر</h3>
    <table class="info-table" style="margin-bottom: 5mm;">
        @if($projectTypeAr)
        <tr>
            <td class="label" style="width:35%;">نوع المشروع</td>
            <td class="value" style="width:65%;">{{ $projectTypeAr }}</td>
        </tr>
        @endif
        @if($hallLength || $hallWidth || $hallHeight)
        <tr>
            <td class="label">الأبعاد (ط × ع × ار)</td>
            <td class="value">
                @if($hallLength){{ number_format((float)$hallLength, 0) }}م@endif
                @if($hallWidth) × {{ number_format((float)$hallWidth, 0) }}م@endif
                @if($hallHeight) × {{ number_format((float)$hallHeight, 0) }}م@endif
            </td>
        </tr>
        @endif
        @if($serviceLen)
        <tr>
            <td class="label">منطقة الخدمات</td>
            <td class="value">{{ number_format((float)$serviceLen, 1) }} م</td>
        </tr>
        @endif
        @if($effectiveL)
        <tr>
            <td class="label">الطول الفعّال</td>
            <td class="value">{{ number_format((float)$effectiveL, 1) }} م</td>
        </tr>
        @endif
        @if($lines)
        <tr>
            <td class="label">عدد الخطوط</td>
            <td class="value">{{ $lines }}</td>
        </tr>
        @endif
        @if($tiers)
        <tr>
            <td class="label">الأدوار</td>
            <td class="value">{{ $tiers }}</td>
        </tr>
        @endif
        @if($birdWeight)
        <tr>
            <td class="label">وزن الطائر</td>
            <td class="value">{{ number_format((float)$birdWeight, 3) }} كجم</td>
        </tr>
        @endif
        @if($birdsPerN)
        <tr>
            <td class="label">طيور / العش</td>
            <td class="value">{{ $birdsPerN }}</td>
        </tr>
        @endif
    </table>

    {{-- جدول ب: الكميات المحسوبة + المعادلة --}}
    <h3>ثانيًا: الكميات المحسوبة</h3>
    <table style="margin-bottom: 5mm;">
        <thead>
            <tr>
                <th style="width:35%; text-align:right;">البند</th>
                <th style="width:20%; text-align:center;">القيمة</th>
                <th style="width:45%; text-align:right; font-size:9pt;">المعادلة</th>
            </tr>
        </thead>
        <tbody>
            @if($nestsPerLine)
            <tr>
                <td>أعشاش / الخط</td>
                <td style="text-align:center; font-weight:bold;">{{ number_format((int)$nestsPerLine) }}</td>
                <td style="font-size:9pt; color:#555; direction:ltr; text-align:right;">{{ $nestsFormula }}</td>
            </tr>
            @endif
            @if($totalNests)
            <tr>
                <td>إجمالي الأعشاش</td>
                <td style="text-align:center; font-weight:bold;">{{ number_format((int)$totalNests) }}</td>
                <td style="font-size:9pt; color:#555; direction:ltr; text-align:right;">{{ $totalNestsFormula }}</td>
            </tr>
            @endif
            @if($totalBirds)
            <tr style="background:{{ settings('branding.primary_color') }} !important;">
                <td style="background:{{ settings('branding.primary_color') }}; color:white; font-weight:bold;">
                    إجمالي عدد الطيور (السعة)
                </td>
                <td style="background:{{ settings('branding.primary_color') }}; color:white; font-weight:bold; text-align:center; font-size:12pt;">
                    {{ number_format((int)$totalBirds) }}
                </td>
                <td style="background:{{ settings('branding.primary_color') }}; color:white; font-size:9pt; direction:ltr; text-align:right;">
                    {{ $totalBirdsFormula }}
                </td>
            </tr>
            @endif
            @if($mainFans)
            <tr>
                <td>الشفاطات الرئيسية</td>
                <td style="text-align:center; font-weight:bold;">{{ $mainFans }}</td>
                <td style="font-size:9pt; color:#555; direction:ltr; text-align:right;">{{ $fanFormula }}</td>
            </tr>
            @endif
            @if($coolingM)
            <tr>
                <td>طول التبريد (م)</td>
                <td style="text-align:center; font-weight:bold;">{{ number_format((float)$coolingM, 1) }}</td>
                <td style="font-size:9pt; color:#555; direction:ltr; text-align:right;">{{ $coolingFormula }}</td>
            </tr>
            @endif
            @if($windows)
            <tr>
                <td>شبابيك الهواء (Inlets)</td>
                <td style="text-align:center; font-weight:bold;">{{ $windows }}</td>
                <td style="font-size:9pt; color:#888;">—</td>
            </tr>
            @endif
            @if($sideFans)
            <tr>
                <td>الشفاطات الجانبية</td>
                <td style="text-align:center; font-weight:bold;">{{ $sideFans }}</td>
                <td style="font-size:9pt; color:#888;">—</td>
            </tr>
            @endif
            @if($heaters)
            <tr>
                <td>الدفايات</td>
                <td style="text-align:center; font-weight:bold;">{{ $heaters }}</td>
                <td style="font-size:9pt; color:#888;">—</td>
            </tr>
            @endif
        </tbody>
    </table>
@endif
