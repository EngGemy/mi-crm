<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>عرض سعر {{ $q->quote_number }}</title>
<style>
@php $primary = settings('branding.primary_color','#C00000'); @endphp
* { font-family:'cairo',sans-serif; box-sizing:border-box; margin:0; padding:0; }
body { direction:rtl; color:#1a1a1a; font-size:10.5pt; line-height:1.65; background:#fff; }

/* ─── Header/Footer ─── */
.hdr { border-bottom:3pt solid {{ settings('branding.primary_color','#C00000') }}; padding-bottom:3mm; }
.hdr table,.hdr td { border:none !important; }
.hdr td { vertical-align:middle; }
.logo-badge {
    display:inline-block; width:15mm; height:15mm; line-height:15mm;
    background:{{ settings('branding.primary_color','#C00000') }};
    border-radius:3mm; color:#fff; font-size:9pt; font-weight:bold; text-align:center;
}
.brand   { font-size:13pt; font-weight:bold; color:{{ settings('branding.primary_color','#C00000') }}; }
.sub     { font-size:8pt; color:#666; margin-top:1mm; }
.contact { font-size:8pt; color:#555; line-height:1.7; direction:ltr; text-align:left; }

.ftr { background:{{ settings('branding.primary_color','#C00000') }}; color:#fff; padding:3mm 4mm; font-size:8pt; }
.ftr table,.ftr td { border:none !important; }
.ftr td { vertical-align:middle; color:#fff; }
.ftr-meta {
    border-top:1px solid rgba(255,255,255,.3); margin-top:2mm; padding-top:1.5mm;
    text-align:center; font-size:7.5pt; color:rgba(255,255,255,.85);
}

/* ─── Ribbon ─── */
.ribbon {
    background:{{ settings('branding.primary_color','#C00000') }};
    color:#fff; text-align:center; padding:6mm 0; margin-bottom:5mm;
}
.ribbon h1 { font-size:18pt; font-weight:bold; letter-spacing:.3pt; }
.ribbon .qnum { font-size:10.5pt; opacity:.88; margin-top:1.5mm; }

/* ─── Boxes ─── */
.box {
    border:1.5pt solid {{ settings('branding.primary_color','#C00000') }};
    border-radius:2.5mm; padding:4mm 5mm; margin-bottom:5mm;
    background:#fff9f9;
}
.box-title {
    font-size:10.5pt; font-weight:bold;
    color:{{ settings('branding.primary_color','#C00000') }};
    border-bottom:1pt solid #fcc; padding-bottom:2mm; margin-bottom:3mm;
}
.box table,.box td { border:none !important; }
.box td { vertical-align:top; padding:1mm 2mm; }
.lbl { font-size:8.5pt; color:#666; }
.val { font-weight:bold; color:#111; font-size:10pt; }

/* ─── Section heading ─── */
.sec-head {
    background:#2d2d2d; color:#fff; font-size:11pt; font-weight:bold;
    text-align:center; padding:2.5mm 0; border-radius:1.5mm; margin:5mm 0 3mm 0;
}

/* ─── Info table ─── */
.it { width:100%; border-collapse:collapse; font-size:10pt; margin-bottom:4mm; }
.it tr:nth-child(even) td { background:#f7f7f7; }
.it td { padding:2.2mm 4mm; border-bottom:1pt solid #ebebeb; vertical-align:middle; }
.it .l { color:#555; width:38%; font-size:9.5pt; }
.it .v { font-weight:bold; color:#111; }
.it .hi { color:{{ settings('branding.primary_color','#C00000') }}; font-weight:bold; }

/* ─── Birds highlight row ─── */
.birds-row { background:{{ settings('branding.primary_color','#C00000') }} !important; }
.birds-row td { color:#fff !important; font-weight:bold !important; background:{{ settings('branding.primary_color','#C00000') }} !important; }

/* ─── Price table ─── */
.pt { width:100%; border-collapse:collapse; font-size:9.5pt; margin-bottom:3mm; page-break-inside:avoid; }
.pt thead tr.cat-head th { background:#3a3a3a; color:#fff; font-size:10.5pt; text-align:center; padding:2.5mm 3mm; }
.pt thead tr.col-head th {
    background:{{ settings('branding.primary_color','#C00000') }};
    color:#fff; padding:2mm 3mm; font-weight:bold; text-align:center;
    border:1pt solid rgba(255,255,255,.25);
}
.pt thead tr.col-head th:first-child { text-align:right; }
.pt tbody tr:nth-child(odd)  td { background:#fff; }
.pt tbody tr:nth-child(even) td { background:#f9f9f9; }
.pt tbody td {
    padding:2mm 3mm; border:1pt solid #e5e5e5; vertical-align:middle; font-size:9.5pt;
}
.pt tbody td.c { text-align:center; direction:ltr; }
.pt tfoot td {
    background:#f0f0f0; border-top:2pt solid #ccc; padding:2mm 3mm;
    font-weight:bold; font-size:10pt; text-align:center;
}
.pt tfoot td.tl { text-align:right; }

/* ─── Financials ─── */
.fin {
    width:62%; margin-right:0; margin-top:5mm;
    border:1.5pt solid #ddd; border-radius:2mm;
    page-break-inside:avoid; overflow:hidden;
}
.fin table { width:100%; border-collapse:collapse; }
.fin td { padding:2.5mm 4mm; border-bottom:1pt solid #ebebeb; font-size:10.5pt; }
.fin .fl { color:#555; }
.fin .fr { font-weight:bold; direction:ltr; text-align:left; }
.fin .grand td {
    background:{{ settings('branding.primary_color','#C00000') }} !important;
    color:#fff !important; font-weight:bold; font-size:13pt;
    border-bottom:none !important;
}

/* ─── Notes ─── */
.notes {
    border-right:4pt solid {{ settings('branding.primary_color','#C00000') }};
    background:#fff9f9; padding:3mm 4mm; margin-top:5mm;
    font-size:9pt; color:#444; border-radius:0 2mm 2mm 0;
    page-break-inside:avoid;
}

/* ─── Stamp ─── */
.stamp-box {
    width:55%; margin:8mm auto 0 auto; text-align:center;
    border:1.5pt solid {{ settings('branding.primary_color','#C00000') }};
    border-radius:2.5mm; padding:5mm;
    page-break-inside:avoid;
}
.stamp-line {
    border-top:1pt dashed #aaa; margin-top:12mm; padding-top:2mm;
    font-size:8.5pt; color:#555;
}
</style>
</head>
<body>

{{-- ══ HEADER ══ --}}
<htmlpageheader name="ph">
<div class="hdr">
    <table style="width:100%;">
        <tr>
            <td style="width:16%; text-align:right;"><div class="logo-badge">إم آي</div></td>
            <td style="width:52%; text-align:center;">
                <div class="brand">@setting('company.name_ar','إم آي للصناعات المعدنية')</div>
                <div class="sub">@setting('company.tagline_ar','متخصصون في أنظمة تربية الدواجن')</div>
            </td>
            <td style="width:32%;" class="contact">
                @foreach(settings('contact.phones',[]) as $p)📞 {{ $p }}<br>@endforeach
                ✉ @setting('contact.email','info@mi-metal.com')
            </td>
        </tr>
    </table>
</div>
</htmlpageheader>

{{-- ══ FOOTER ══ --}}
<htmlpagefooter name="pf">
<div class="ftr">
    <table style="width:100%;">
        <tr>
            <td style="width:33%; text-align:right;">🏢 @setting('contact.address_ar','القاهرة — مصر')</td>
            <td style="width:34%; text-align:center;">@setting('contact.website','www.mi-metal.com')</td>
            <td style="width:33%; direction:ltr; text-align:left;">@setting('contact.email','info@mi-metal.com')</td>
        </tr>
    </table>
    <div class="ftr-meta">
        صفحة {PAGENO} من {nbpg} &nbsp;|&nbsp; عرض سعر رقم: {{ $q->quote_number }} &nbsp;|&nbsp; تاريخ: {{ now()->format('d/m/Y') }}
    </div>
</div>
</htmlpagefooter>

<sethtmlpageheader name="ph" page="ALL" value="on" show-this-page="1" />
<sethtmlpagefooter name="pf" page="ALL" value="on" show-this-page="1" />

{{-- ══ RIBBON ══ --}}
<div class="ribbon">
    <h1>عرض سعر تقديري</h1>
    <div class="qnum">رقم: {{ $q->quote_number }} &nbsp;·&nbsp; {{ now()->format('d / m / Y') }}</div>
</div>

{{-- ══ CLIENT ══ --}}
<div class="box">
    <div class="box-title">📋 بيانات العميل</div>
    <table style="width:100%;">
        <tr>
            <td style="width:40%;"><div class="lbl">اسم العميل</div><div class="val">{{ $q->client_name }}</div></td>
            <td style="width:30%;"><div class="lbl">رقم الهاتف</div><div class="val" dir="ltr">{{ $q->client_phone ?: '—' }}</div></td>
            <td style="width:30%;"><div class="lbl">العنوان</div><div class="val">{{ $q->client_address ?: '—' }}</div></td>
        </tr>
    </table>
</div>

{{-- ══ SPECS ══ --}}
@php
    $projectType = match($q->project_type) {
        'broiler'       => 'تسمين (Broiler)',
        'layer'         => 'بياض (Layer)',
        'layer_rearing' => 'تربية بياض',
        default         => $q->project_type,
    };
    $barnLen  = (float)($tech['barn_length']  ?? $q->length   ?? 0);
    $barnW    = (float)($tech['barn_width']   ?? $q->width    ?? 0);
    $barnH    = (float)($snap['inputs']['hall_height'] ?? $snap['inputs']['height'] ?? $q->height ?? 0);
    $svcLen   = $snap['inputs']['service_length'] ?? '—';
    $effLen   = (float)($tech['effective_length']  ?? $computed['effective_length'] ?? 0);
    $wallType = $q->wall_type === 'sandwich' ? 'ساندوتش' : 'خرسانة';
    $scopeMap = ['full_project'=>'المشروع كاملاً','batteries_only'=>'البطاريات فقط','construction_only'=>'الإنشاءات فقط','custom'=>'مخصص'];
    $scope    = $scopeMap[$q->pricing_scope ?? ''] ?? 'المشروع كاملاً';
@endphp

<div class="sec-head">مواصفات المشروع والعنبر</div>
<table class="it">
    <tr>
        <td class="l">نوع المشروع</td>
        <td class="v">{{ $projectType }}</td>
        <td class="l">نطاق التسعير</td>
        <td class="v">{{ $scope }}</td>
    </tr>
    <tr>
        <td class="l">أبعاد العنبر</td>
        <td class="v" dir="ltr">{{ number_format($barnLen,0) }} × {{ number_format($barnW,0) }} × {{ number_format($barnH,2) }} م</td>
        <td class="l">منطقة الخدمات</td>
        <td class="v">{{ $svcLen }} م</td>
    </tr>
    <tr>
        <td class="l">الطول الفعّال</td>
        <td class="hi">{{ number_format($effLen,0) }} م</td>
        <td class="l">نوع الحوائط</td>
        <td class="v">{{ $wallType }}</td>
    </tr>
    <tr>
        <td class="l">الخطوط × الأدوار</td>
        <td class="v">{{ $tech['lines'] ?? $q->lines }} خطوط × {{ $tech['tiers'] ?? $q->tiers }} أدوار</td>
        <td class="l">تاريخ العرض</td>
        <td class="v" dir="ltr">{{ $q->created_at?->format('Y-m-d') }}</td>
    </tr>
</table>

{{-- ══ TECHNICAL ══ --}}
@php
    $nestsPerLine   = $computed['nests_per_line']      ?? $tech['nests_per_line']      ?? null;
    $nestsOneSide   = $tech['nests_one_side']          ?? null;
    $totalNests     = $computed['total_nests']          ?? $tech['total_nests']         ?? null;
    $totalBirds     = $computed['bird_count']           ?? $tech['total_birds']         ?? null;
    $birdsPerNest   = $tech['birds_per_nest']           ?? null;
    $birdWeightKg   = $tech['bird_weight_kg']           ?? null;
    $mainFans       = $computed['main_fans_count']      ?? $tech['main_fans_count']     ?? $tech['rear_fans_count'] ?? null;
    $coolingM       = $computed['cooling_pad_length_m'] ?? $tech['cooling_pad_length_m'] ?? null;
    $windows        = $computed['windows_count']        ?? $tech['air_windows_count']   ?? null;
    $sideFans       = $computed['side_fans_count']      ?? $tech['side_fans_count']     ?? 0;
    $heaters        = $computed['heaters_count']        ?? $tech['heaters_count']       ?? 0;
    $fanFormula     = $tech['fan_formula']              ?? null;
    $coolingFormula = $tech['cooling_formula']          ?? null;
@endphp
<div class="sec-head">البيانات الفنية وعدد الطيور</div>
<table class="it">
    @if($nestsPerLine)
    <tr>
        <td class="l">أعشاش / الخط</td>
        <td class="v">{{ number_format((int)$nestsPerLine) }} عش</td>
        <td class="l">طيور / العش</td>
        <td class="v">{{ $birdsPerNest ?? '—' }}@if($birdWeightKg) &nbsp;<small style="font-size:8.5pt;color:#666;">({{ number_format((float)$birdWeightKg,3) }} كجم)</small>@endif</td>
    </tr>
    @endif
    @if($totalNests)
    <tr>
        <td class="l">إجمالي الأعشاش</td>
        <td class="v">{{ number_format((int)$totalNests) }} عش</td>
        <td class="l">الشفاطات الرئيسية</td>
        <td class="v">{{ $mainFans ?? '—' }} مروحة@if($fanFormula) &nbsp;<small style="font-size:8pt;color:#666;direction:ltr;">{{ $fanFormula }}</small>@endif</td>
    </tr>
    @endif
    <tr>
        <td class="l">وحدات التبريد</td>
        <td class="v">{{ $coolingM ? number_format((float)$coolingM,1).' م' : '—' }}@if($coolingFormula) &nbsp;<small style="font-size:8pt;color:#666;direction:ltr;">{{ $coolingFormula }}</small>@endif</td>
        <td class="l">شبابيك الهواء</td>
        <td class="v">{{ $windows ?? '—' }}</td>
    </tr>
    @if($sideFans || $heaters)
    <tr>
        <td class="l">الشفاطات الجانبية</td>
        <td class="v">{{ $sideFans ?: '—' }}</td>
        <td class="l">الدفايات</td>
        <td class="v">{{ $heaters ?: '—' }}</td>
    </tr>
    @endif
</table>

{{-- Birds total highlight --}}
@if($totalBirds)
<table class="it" style="margin-top:-2mm;">
    <tr class="birds-row">
        <td style="width:38%; padding:3mm 4mm;">إجمالي عدد الطيور (السعة)</td>
        <td style="width:62%; font-size:14pt; padding:3mm 4mm; direction:ltr; text-align:left;">
            {{ number_format((int)$totalBirds) }} طائر
        </td>
    </tr>
</table>
@endif

{{-- ══ PRICING TABLE ══ --}}
@if(!empty($grouped))
<div style="page-break-before:always;"></div>
<div class="sec-head">جدول التكاليف التفصيلي</div>

@php
    $sectionOrder  = ['civil','cages','ventilation','cooling','technical','electrical'];
    $orderedGroups = [];
    foreach ($sectionOrder as $s) {
        if (!empty($grouped[$s])) $orderedGroups[$s] = $grouped[$s];
    }
    foreach ($grouped as $s => $rows) {
        if (!isset($orderedGroups[$s])) $orderedGroups[$s] = $rows;
    }
    $grandItems = collect($orderedGroups)->flatten(1);
@endphp

@foreach($orderedGroups as $section => $rows)
@php
    $secLabel    = \App\Support\PoultrySectionLabels::labelAr($section);
    $secSubtotal = collect($rows)->sum(fn($r) => (float)($r['total_price'] ?? 0));
@endphp
<table class="pt" style="margin-bottom:4mm;">
    <thead>
        <tr class="cat-head"><th colspan="5">{{ $secLabel }}</th></tr>
        <tr class="col-head">
            <th style="text-align:right; width:40%;">البند</th>
            <th style="width:12%;">الوحدة</th>
            <th style="width:10%;">الكمية</th>
            <th style="width:18%;">سعر الوحدة</th>
            <th style="width:20%;">الإجمالي (ج.م)</th>
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
        <tr><td colspan="4" class="tl">مجموع {{ $secLabel }}</td><td>{{ number_format($secSubtotal, 0) }} ج.م</td></tr>
    </tfoot>
</table>
@endforeach
@endif

{{-- ══ FINANCIAL SUMMARY ══ --}}
@php
    $subtotal  = (float)($q->subtotal        ?? $snap['subtotal']           ?? 0);
    $vatPct    = (float)($q->vat_percentage  ?? 0);
    $vatAmt    = (float)($q->vat_amount      ?? $fin['vat_amount']          ?? 0);
    $grandTotal= (float)($q->total           ?? $fin['grand_total']         ?? ($subtotal + $vatAmt));
@endphp
<div class="fin">
    <table>
        <tr><td class="fl">المجموع الفرعي</td><td class="fr">{{ number_format($subtotal, 0) }} ج.م</td></tr>
        @if($vatAmt > 0)
        <tr><td class="fl">ضريبة القيمة المضافة ({{ number_format($vatPct,0) }}%)</td><td class="fr">{{ number_format($vatAmt, 0) }} ج.م</td></tr>
        @endif
        <tr class="grand"><td>الإجمالي النهائي</td><td style="direction:ltr; text-align:left; font-size:14pt;">{{ number_format($grandTotal, 0) }} ج.م</td></tr>
    </table>
</div>

{{-- ══ NOTES ══ --}}
<div class="notes">
    <strong>ملاحظات هامة:</strong><br>
    • هذا العرض تقديري وقابل للتعديل بناءً على المتطلبات الفعلية للموقع.<br>
    • الأسعار لا تشمل أعمال التركيب والتمديدات إلا إذا نُصَّ على ذلك صراحةً.<br>
    • يُرجى التواصل لتأكيد العرض النهائي وإصدار عقد العمل.
</div>

{{-- ══ COMPANY STAMP (شركة فقط) ══ --}}
<div class="stamp-box">
    <div style="font-size:10pt; font-weight:bold; color:#333; margin-bottom:2mm;">
        الختم والتوقيع المعتمد
    </div>
    <div style="font-size:9pt; color:#666; margin-bottom:1mm;">
        @setting('company.name_ar','إم آي للصناعات المعدنية')
    </div>
    <div class="stamp-line">
        ختم الشركة &nbsp;/&nbsp; Authorized Signature
    </div>
</div>

</body>
</html>
