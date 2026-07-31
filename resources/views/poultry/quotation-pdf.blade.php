<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>عرض سعر {{ $q->quote_number }}</title>
<style>
@php $primary = settings('branding.primary_color','#C00000'); @endphp
body {
    font-family: 'cairo', sans-serif;
    direction: rtl;
    color: #1a1a1a;
    font-size: 10pt;
    line-height: 1.5;
}
table { border-collapse: collapse; }
td, th { vertical-align: middle; }

.banner {
    background: {{ $primary }};
    color: #fff;
    text-align: center;
    padding: 8mm 4mm;
    margin-bottom: 5mm;
}
.banner h1 { font-size: 18pt; margin: 0; font-weight: bold; }
.banner .meta { font-size: 10pt; margin-top: 2mm; opacity: 0.9; }

.company-bar {
    width: 100%;
    margin-bottom: 4mm;
    border-bottom: 2pt solid {{ $primary }};
    padding-bottom: 3mm;
}
.company-bar td { border: none; padding: 1mm 2mm; }
.brand { font-size: 13pt; font-weight: bold; color: {{ $primary }}; }
.tagline { font-size: 8pt; color: #666; }
.contact { font-size: 8pt; color: #555; direction: ltr; text-align: left; }

.box {
    border: 1.5pt solid {{ $primary }};
    padding: 3mm 4mm;
    margin-bottom: 4mm;
    background: #fffafa;
}
.box-title {
    font-size: 11pt;
    font-weight: bold;
    color: {{ $primary }};
    margin-bottom: 2mm;
    border-bottom: 0.5pt solid #f0c0c0;
    padding-bottom: 1.5mm;
}
.box td { border: none; padding: 1.5mm 2mm; }
.lbl { font-size: 8pt; color: #777; display: block; }
.val { font-size: 10.5pt; font-weight: bold; color: #111; }

.sec {
    background: #222;
    color: #fff;
    font-size: 11pt;
    font-weight: bold;
    text-align: center;
    padding: 2.5mm 0;
    margin: 5mm 0 0 0;
}
.spec {
    width: 100%;
    margin-bottom: 4mm;
}
.spec td {
    border: 0.6pt solid #e5e5e5;
    padding: 2.8mm 3.5mm;
    font-size: 10pt;
}
.spec .k {
    width: 22%;
    background: #f5f5f5;
    color: #555;
    font-weight: normal;
}
.spec .v {
    width: 28%;
    font-weight: bold;
    color: #111;
}
.spec .num {
    direction: ltr;
    text-align: right;
}

.weight {
    width: 100%;
    margin: 3mm 0 5mm 0;
}
.weight th {
    background: {{ $primary }};
    color: #fff;
    padding: 2.5mm;
    font-size: 9.5pt;
    border: 0.5pt solid {{ $primary }};
}
.weight td {
    padding: 2.2mm;
    text-align: center;
    border: 0.5pt solid #e0e0e0;
    font-size: 9.5pt;
}
.weight tr.sel td {
    background: #fef2f2;
    font-weight: bold;
    color: {{ $primary }};
}

.birds {
    width: 100%;
    margin-bottom: 5mm;
}
.birds td {
    background: {{ $primary }};
    color: #fff;
    font-weight: bold;
    padding: 3.5mm 4mm;
    font-size: 12pt;
}

.pt {
    width: 100%;
    margin-bottom: 4mm;
    page-break-inside: avoid;
}
.pt .cat {
    background: #333;
    color: #fff;
    font-size: 10.5pt;
    text-align: center;
    padding: 2.5mm;
}
.pt .cols th {
    background: {{ $primary }};
    color: #fff;
    padding: 2mm 2.5mm;
    font-size: 9pt;
    border: 0.4pt solid rgba(255,255,255,0.2);
}
.pt td {
    padding: 2mm 2.5mm;
    border: 0.5pt solid #e5e5e5;
    font-size: 9.5pt;
}
.pt .c { text-align: center; direction: ltr; }
.pt tfoot td {
    background: #f3f3f3;
    font-weight: bold;
    padding: 2.2mm 2.5mm;
}

.fin {
    width: 55%;
    margin-top: 4mm;
    border: 1.2pt solid #ddd;
}
.fin td {
    padding: 2.5mm 4mm;
    border-bottom: 0.5pt solid #eee;
    font-size: 10.5pt;
}
.fin .fl { color: #555; }
.fin .fr { font-weight: bold; direction: ltr; text-align: left; }
.fin .grand td {
    background: {{ $primary }};
    color: #fff;
    font-weight: bold;
    font-size: 13pt;
    border: none;
}

.notes {
    margin-top: 5mm;
    padding: 3mm 4mm;
    background: #fafafa;
    border-right: 3pt solid {{ $primary }};
    font-size: 8.5pt;
    color: #444;
}

.stamp {
    width: 50%;
    margin: 8mm auto 0 auto;
    text-align: center;
    border: 1.2pt solid {{ $primary }};
    padding: 5mm;
}
.stamp .line {
    border-top: 0.6pt dashed #aaa;
    margin-top: 12mm;
    padding-top: 2mm;
    font-size: 8pt;
    color: #666;
}

.ftr {
    background: {{ $primary }};
    color: #fff;
    font-size: 8pt;
    padding: 2.5mm 3mm;
    text-align: center;
}
</style>
</head>
<body>

@php
    $projectType = match($q->project_type) {
        'broiler'       => 'تسمين',
        'layer'         => 'بياض',
        'layer_rearing' => 'تربية بياض',
        default         => (string) $q->project_type,
    };
    $barnLen  = (float)($tech['barn_length']  ?? $q->length   ?? 0);
    $barnW    = (float)($tech['barn_width']   ?? $q->width    ?? 0);
    $barnH    = (float)($snap['inputs']['hall_height'] ?? $snap['inputs']['height'] ?? $q->height ?? 0);
    $svcLen   = $snap['inputs']['service_length'] ?? $q->service_length ?? '—';
    $effLen   = (float)($tech['effective_length']  ?? $computed['effective_length'] ?? 0);
    $wallType = $q->wall_type === 'sandwich' ? 'ساندوتش' : 'خرسانة';
    $scopeMap = [
        'full_project' => 'المشروع كاملاً',
        'batteries_only' => 'البطاريات فقط',
        'batteries_and_accessories' => 'بطاريات ومشتملات',
        'accessories_only' => 'المشتملات فقط',
        'construction_only' => 'الإنشاءات فقط',
        'custom' => 'مخصص',
    ];
    $scope = $scopeMap[$q->pricing_scope ?? ''] ?? 'المشروع كاملاً';
    $lines = (int)($tech['lines'] ?? $q->lines ?? 0);
    $tiers = (int)($tech['tiers'] ?? $q->tiers ?? 0);
    $dimsText = number_format($barnLen, 0).' × '.number_format($barnW, 0).' × '.number_format($barnH, 2).' م';
    $linesText = $lines.' خط × '.$tiers.' دور';

    $nestsPerLine = $computed['nests_per_line'] ?? $tech['nests_per_line'] ?? null;
    $totalNests   = $computed['total_nests'] ?? $tech['total_nests'] ?? null;
    $totalBirds   = $computed['bird_count'] ?? $tech['total_birds'] ?? $q->bird_count ?? null;
    $birdsPerNest = $tech['birds_per_nest'] ?? $q->birds_per_nest ?? null;
    $birdWeightKg = (float)($tech['bird_weight_kg'] ?? $q->bird_weight_kg ?? 0);
    $heaters      = (int)($computed['heaters_count'] ?? $tech['heaters_count'] ?? 0);

    $weightRows = ($q->project_type === 'broiler')
        ? \App\Support\BroilerWeightReference::rows()
        : [];

    $subtotal   = (float)($q->subtotal ?? $snap['subtotal'] ?? $fin['subtotal'] ?? 0);
    $vatPct     = (float)($q->vat_percentage ?? 0);
    $vatAmt     = (float)($q->vat_amount ?? $fin['vat_amount'] ?? 0);
    $grandTotal = (float)($q->total ?? $fin['total'] ?? $fin['grand_total'] ?? 0);
    if ($grandTotal <= 0 && $subtotal > 0) {
        $grandTotal = $subtotal + $vatAmt;
    }

    $excludedKeys = ['main_fans', 'cooling', 'windows', 'side_fans'];
    $excludedSections = ['ventilation', 'cooling'];
@endphp

{{-- Header --}}
<htmlpageheader name="ph">
<table class="company-bar">
    <tr>
        <td style="width:55%;">
            <div class="brand">@setting('company.name_ar','إم آي للصناعات المعدنية')</div>
            <div class="tagline">@setting('company.tagline_ar','متخصصون في أنظمة تربية الدواجن')</div>
        </td>
        <td class="contact" style="width:45%;">
            @foreach(array_slice((array) settings('contact.phones', []), 0, 2) as $p)
                {{ $p }}<br>
            @endforeach
            @setting('contact.email','info@mi-metal.com')
        </td>
    </tr>
</table>
</htmlpageheader>

<htmlpagefooter name="pf">
<div class="ftr">
    @setting('contact.address_ar','القاهرة — مصر')
    &nbsp;|&nbsp;
    صفحة {PAGENO} من {nbpg}
    &nbsp;|&nbsp;
    {{ $q->quote_number }}
    &nbsp;|&nbsp;
    {{ now()->format('d/m/Y') }}
</div>
</htmlpagefooter>

<sethtmlpageheader name="ph" page="ALL" value="on" show-this-page="1" />
<sethtmlpagefooter name="pf" page="ALL" value="on" show-this-page="1" />

<div class="banner">
    <h1>عرض سعر تقديري</h1>
    <div class="meta">رقم: {{ $q->quote_number }} &nbsp;·&nbsp; {{ now()->format('d / m / Y') }}</div>
</div>

{{-- Client --}}
<div class="box">
    <div class="box-title">بيانات العميل</div>
    <table style="width:100%;">
        <tr>
            <td style="width:40%;"><span class="lbl">اسم العميل</span><span class="val">{{ $q->client_name }}</span></td>
            <td style="width:30%;"><span class="lbl">رقم الهاتف</span><span class="val" dir="ltr">{{ $q->client_phone ?: '—' }}</span></td>
            <td style="width:30%;"><span class="lbl">العنوان</span><span class="val">{{ $q->client_address ?: '—' }}</span></td>
        </tr>
    </table>
</div>

{{-- Specs --}}
<div class="sec">مواصفات المشروع والعنبر</div>
<table class="spec">
    <tr>
        <td class="k">نوع المشروع</td>
        <td class="v">{{ $projectType }}</td>
        <td class="k">نطاق التسعير</td>
        <td class="v">{{ $scope }}</td>
    </tr>
    <tr>
        <td class="k">أبعاد العنبر</td>
        <td class="v num">{{ $dimsText }}</td>
        <td class="k">منطقة الخدمات</td>
        <td class="v num">{{ is_numeric($svcLen) ? number_format((float)$svcLen, 0).' م' : $svcLen }}</td>
    </tr>
    <tr>
        <td class="k">الطول الفعّال</td>
        <td class="v num">{{ number_format($effLen, 0) }} م</td>
        <td class="k">نوع الحوائط</td>
        <td class="v">{{ $wallType }}</td>
    </tr>
    <tr>
        <td class="k">الخطوط × الأدوار</td>
        <td class="v">{{ $linesText }}</td>
        <td class="k">تاريخ العرض</td>
        <td class="v num">{{ $q->created_at?->format('Y-m-d') }}</td>
    </tr>
</table>

{{-- Technical --}}
<div class="sec">البيانات الفنية وعدد الطيور</div>
<table class="spec">
    <tr>
        <td class="k">أعشاش / الخط</td>
        <td class="v num">{{ $nestsPerLine ? number_format((int)$nestsPerLine).' عش' : '—' }}</td>
        <td class="k">طيور / العش</td>
        <td class="v num">{{ $birdsPerNest ?? '—' }}@if($birdWeightKg > 0) ({{ number_format($birdWeightKg, 3) }} كجم)@endif</td>
    </tr>
    <tr>
        <td class="k">إجمالي الأعشاش</td>
        <td class="v num">{{ $totalNests ? number_format((int)$totalNests).' عش' : '—' }}</td>
        <td class="k">الدفايات</td>
        <td class="v num">{{ $heaters > 0 ? $heaters : '—' }}</td>
    </tr>
</table>

@if($totalBirds)
<table class="birds">
    <tr>
        <td style="width:40%;">إجمالي عدد الطيور (السعة)</td>
        <td style="width:60%; direction:ltr; text-align:left; font-size:14pt;">{{ number_format((int)$totalBirds) }} طائر</td>
    </tr>
</table>
@endif

{{-- Weight table --}}
@if(count($weightRows) > 0)
<div class="sec">جدول أوزان الفراخ وعدد الطيور (تسمين)</div>
<div style="background:#fff5f5; border:1pt solid #f5c2c2; padding:3mm 4mm; margin-bottom:3mm; font-size:9pt; color:#444;">
    <strong style="color:{{ settings('branding.primary_color','#C00000') }};">ملاحظة السعة:</strong>
    عدد الطيور يعتمد على وزن الطائر المستهدف.
    الوزن المختار لهذا العرض:
    <strong style="color:{{ settings('branding.primary_color','#C00000') }};">{{ $birdWeightKg > 0 ? number_format($birdWeightKg, 3) : '—' }} كجم</strong>
    ←
    <strong>{{ $birdsPerNest ?? '—' }} طائر/عش</strong>
    ←
    سعة معتمدة
    <strong>{{ $totalBirds ? number_format((int)$totalBirds) : '—' }} طائر</strong>
    @if($totalNests && $birdsPerNest)
    <br>
    المعادلة:
    <span dir="ltr" style="font-weight:bold;">{{ number_format((int)$totalNests) }} عش × {{ number_format((int)$birdsPerNest) }} = {{ number_format((int)$totalBirds) }} طائر</span>
    @endif
</div>
<table class="weight">
    <thead>
        <tr>
            <th>وزن الطائر (كجم)</th>
            <th>عدد الطيور / عش</th>
            @if($totalNests)
            <th>إجمالي الطيور</th>
            @endif
            <th>الحالة</th>
        </tr>
    </thead>
    <tbody>
    @foreach($weightRows as $row)
        @php $sel = $birdWeightKg > 0 && abs($row['weight_float'] - $birdWeightKg) < 0.001; @endphp
        <tr class="{{ $sel ? 'sel' : '' }}">
            <td dir="ltr">{{ $row['weight_kg'] }}</td>
            <td>{{ number_format($row['birds_per_nest']) }} طائر</td>
            @if($totalNests)
            <td dir="ltr">{{ number_format((int)$totalNests * $row['birds_per_nest']) }}</td>
            @endif
            <td>{{ $sel ? 'المُطبَّق' : '—' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
<div style="font-size:8pt; color:#666; margin-bottom:4mm;">
    الصف المظلّل بالأحمر هو الوزن المُطبَّق على هذا العرض. باقي الصفوف للمقارنة فقط.
</div>
@endif

{{-- Pricing --}}
@if(!empty($grouped))
@php
    $sectionOrder = ['civil', 'cages', 'technical', 'electrical'];
    $orderedGroups = [];
    foreach ($sectionOrder as $s) {
        if (empty($grouped[$s])) {
            continue;
        }
        $rows = array_values(array_filter($grouped[$s], fn ($r) => ! in_array($r['key'] ?? '', $excludedKeys, true)));
        if ($rows !== []) {
            $orderedGroups[$s] = $rows;
        }
    }
    foreach ($grouped as $s => $rows) {
        if (isset($orderedGroups[$s]) || in_array($s, $excludedSections, true)) {
            continue;
        }
        $filtered = array_values(array_filter($rows, fn ($r) => ! in_array($r['key'] ?? '', $excludedKeys, true)));
        if ($filtered !== []) {
            $orderedGroups[$s] = $filtered;
        }
    }
@endphp

@if(count($orderedGroups) > 0)
<div class="sec">جدول التكاليف التفصيلي</div>
@foreach($orderedGroups as $section => $rows)
@php
    $secLabel = \App\Support\PoultrySectionLabels::labelAr($section);
    $secSubtotal = collect($rows)->sum(fn ($r) => (float)($r['total_price'] ?? 0));
@endphp
<table class="pt">
    <thead>
        <tr><th class="cat" colspan="5">{{ $secLabel }}</th></tr>
        <tr class="cols">
            <th style="text-align:right; width:42%;">البند</th>
            <th style="width:12%;">الوحدة</th>
            <th style="width:12%;">الكمية</th>
            <th style="width:17%;">سعر الوحدة</th>
            <th style="width:17%;">الإجمالي</th>
        </tr>
    </thead>
    <tbody>
    @foreach($rows as $row)
    @php $hide = $row['hide_unit_details'] ?? false; @endphp
    <tr>
        <td>{{ $row['desc_ar'] ?? '' }}</td>
        <td class="c">{{ $hide ? '—' : \App\Support\QuotationPdfLabels::unit($row['unit'] ?? '') }}</td>
        <td class="c">{{ $hide ? '—' : number_format((float)($row['qty'] ?? 0), 0) }}</td>
        <td class="c">{{ $hide ? '—' : number_format((float)($row['unit_price'] ?? 0), 0) }}</td>
        <td class="c" style="font-weight:bold;">{{ number_format((float)($row['total_price'] ?? 0), 0) }}</td>
    </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4" style="text-align:right;">مجموع {{ $secLabel }}</td>
            <td class="c">{{ number_format($secSubtotal, 0) }} ج.م</td>
        </tr>
    </tfoot>
</table>
@endforeach
@endif
@endif

{{-- Financial --}}
<table class="fin">
    <tr>
        <td class="fl">المجموع الفرعي</td>
        <td class="fr">{{ number_format($subtotal, 0) }} ج.م</td>
    </tr>
    @if($vatAmt > 0)
    <tr>
        <td class="fl">ضريبة القيمة المضافة ({{ number_format($vatPct, 0) }}%)</td>
        <td class="fr">{{ number_format($vatAmt, 0) }} ج.م</td>
    </tr>
    @endif
    <tr class="grand">
        <td>الإجمالي النهائي</td>
        <td style="direction:ltr; text-align:left;">{{ number_format($grandTotal, 0) }} ج.م</td>
    </tr>
</table>

<div class="notes">
    <strong>ملاحظات هامة:</strong><br>
    • هذا العرض تقديري وقابل للتعديل بناءً على المتطلبات الفعلية للموقع.<br>
    • الأسعار لا تشمل أعمال التركيب والتمديدات إلا إذا نُصَّ على ذلك صراحةً.<br>
    • يُرجى التواصل لتأكيد العرض النهائي وإصدار عقد العمل.
</div>

<div class="stamp">
    @include('components.company-seal', [
        'q' => $q,
        'sealId' => $q->quote_number,
        'sealDate' => now()->format('Y'),
        'sealColor' => '#1e40af',
    ])
    <div class="stamp-line" style="margin-top:4mm;">
        ختم الشركة المعتمد للتوقيع
    </div>
</div>

</body>
</html>
