<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>{{ $quotation->quotation_number }} — {{ $customer->name ?? '' }}</title>
<style>
    * {
        font-family: 'cairo', sans-serif;
        direction: rtl;
        text-align: right;
        box-sizing: border-box;
    }
    {{-- لا تستخدم @page هنا: mPDF يولّد آلاف الصفحات الفارغة عند دمج @page مع الهوامش/الترويسة --}}
    body {
        color: #1F1F1F;
        line-height: 1.6;
        font-size: 10.5pt;
        font-family: 'cairo', sans-serif;
        direction: rtl;
        text-align: right;
        margin: 0;
        padding: 0;
    }

    /* ===== Page Breaks ===== */
    .page-break { page-break-after: always; }
    .page-break-before { page-break-before: always; }

    /* ===== Header / Footer (mPDF native) ===== */
    .pdf-header {
        border-bottom: 3px solid {{ settings('branding.primary_color') }};
        padding: 2mm 0 5mm 0;
    }
    .pdf-header table { border: none !important; margin: 0 !important; width: 100%; }
    .pdf-header td { border: none !important; vertical-align: middle; }
    .pdf-header .header-logo img {
        max-height: 14mm; width: auto;
        border-radius: 2mm;
    }
    .pdf-header .header-logo .logo-badge {
        width: 14mm; height: 14mm;
        background: linear-gradient(135deg, {{ settings('branding.primary_color') }} 0%, #8C0000 100%);
        border-radius: 3mm;
        display: inline-flex; align-items: center; justify-content: center;
        color: #fff; font-size: 10pt; font-weight: bold;
    }
    .pdf-header .brand {
        font-size: 13pt;
        font-weight: bold;
        color: {{ settings('branding.primary_color') }};
        margin: 0;
    }
    .pdf-header .sub-brand {
        font-size: 8.5pt;
        color: #666;
        margin-top: 0.5mm;
    }
    .pdf-header .header-contact {
        font-size: 8pt;
        color: #666;
        line-height: 1.5;
        text-align: left;
    }
    .pdf-footer-bar {
        background: {{ settings('branding.primary_color') }};
        color: #fff;
        margin: 0 -15mm;
        padding: 2.5mm 4mm 3mm 4mm;
        font-size: 8pt;
        line-height: 1.45;
    }
    .pdf-footer-bar table { border: none !important; margin: 0 !important; }
    .pdf-footer-bar td { border: none !important; vertical-align: top; }
    .pdf-footer-meta {
        text-align: center;
        font-size: 7pt;
        color: rgba(255, 255, 255, 0.92);
        padding-top: 2mm;
        margin-top: 2mm;
        border-top: 1px solid rgba(255, 255, 255, 0.35);
    }
    .pdf-signature-line {
        text-align: center;
        font-size: 8pt;
        color: #666;
        padding-top: 2mm;
        border-top: 1px dashed #ccc;
        margin-top: 2mm;
    }

    /* ===== Typography ===== */
    h1 { font-size: 22pt; color: {{ settings('branding.primary_color') }}; margin: 0 0 4mm 0; }
    /* mPDF + RTL: إطار جانبي سميك على h2 يسبب خطاً يغطي أعلى الحروف — نستخدم حداً سفلياً ومسافات داخلية فقط */
    h2 {
        font-size: 16pt;
        font-weight: bold;
        color: {{ settings('branding.primary_color') }};
        margin: 5mm 0 3mm 0;
        padding: 3.5mm 4mm 3mm 4mm;
        background: #FFF5F5;
        border: none;
        border-bottom: 2px solid {{ settings('branding.primary_color') }};
        line-height: 1.45;
    }
    h3 { font-size: 12pt; color: #1F1F1F; margin: 3mm 0 2mm 0; }
    h4 { font-size: 11pt; color: #333; margin: 2mm 0 1mm 0; }
    p { margin: 1.5mm 0; text-align: right; }
    ul, ol { padding-right: 5mm; margin: 2mm 0; }
    li { margin: 1mm 0; }

    /* ===== Utility ===== */
    .text-center { text-align: center; }
    .text-left { text-align: left; }
    .text-bold { font-weight: bold; }
    .text-red { color: {{ settings('branding.primary_color') }}; }
    .text-gray { color: #666; }
    .text-small { font-size: 9pt; }
    .text-large { font-size: 14pt; }
    .bg-light { background: #F8F8F8; }
    .highlight {
        background: #FFEB9C;
        padding: 1mm 2mm;
        font-weight: bold;
    }

    /* ===== Tables ===== */
    table {
        width: 100%;
        border-collapse: collapse;
        margin: 2mm 0;
    }
    th {
        background: {{ settings('branding.primary_color') }};
        color: white;
        padding: 2mm 3mm;
        font-weight: bold;
        text-align: center;
        border: 1px solid #900;
    }
    td {
        padding: 2mm 3mm;
        border: 1px solid #CCC;
        vertical-align: top;
    }
    tr:nth-child(even) { background: #FFF0F0; }
    tr:nth-child(odd)  { background: #FFFFFF; }
    tr.total-row {
        background: {{ settings('branding.primary_color') }} !important;
        color: white !important;
        font-weight: bold;
    }
    tr.total-row td { background: {{ settings('branding.primary_color') }}; color: white; border-color: #900; }

    /* ===== Info Table (label/value) ===== */
    .info-table td {
        border: 1px solid #CCC;
        padding: 2mm 3mm;
    }
    .info-table .label {
        background: #F2F2F2;
        font-weight: bold;
        width: 35%;
        text-align: right;
    }
    .info-table .value {
        width: 65%;
        text-align: right;
    }

    /* ===== Images ===== */
    .img-block {
        text-align: center;
        margin: 2mm 0;
    }
    .img-block img {
        max-width: 100%;
        height: auto;
    }
    .img-grid {
        text-align: center;
    }
    .img-grid img {
        display: inline-block;
        width: 48%;
        max-height: 45mm;
        vertical-align: top;
        border: 1px solid #DDD;
    }

    /* ===== Sections ===== */
    .section-box {
        margin: 3mm 0;
        padding: 3mm;
        border: 1px solid #E0E0E0;
        page-break-inside: avoid;
    }
    .section-header {
        background: #F5F5F5;
        border-right: 4px solid {{ settings('branding.primary_color') }};
        padding: 2mm 3mm;
        margin-bottom: 2mm;
    }
    .section-header h3 {
        margin: 0;
        color: {{ settings('branding.primary_color') }};
    }
    .section-header .en {
        font-size: 10pt;
        color: #888;
        margin: 0;
    }

    /* ===== Term Box ===== */
    .term-box {
        margin: 2mm 0 4mm 0;
        padding: 4mm;
        background: #FAFAFA;
        border: 1px solid {{ settings('branding.primary_color') }};
        page-break-inside: avoid;
    }
    .term-box h4 {
        color: {{ settings('branding.primary_color') }};
        margin: 0 0 2mm 0;
        border-bottom: 1px solid #E0E0E0;
        padding-bottom: 1mm;
    }

    /* ===== Watermark ===== */
    .watermark {
        text-align: center;
        color: #E8E8E8;
        font-size: 28pt;
        font-weight: bold;
        margin: 5mm 0;
    }

    /* ===== Two Column Layout ===== */
    .two-col { width: 100%; }
    .two-col td { vertical-align: top; width: 50%; padding: 2mm; }

    /* ===== Cover Page Specific ===== */
    .cover-header-bar {
        background: #1a1a1a;
        color: white;
        text-align: center;
        padding: 4mm;
        margin: 0 -15mm 5mm -15mm;
    }
    .cover-header-bar .logo-text {
        font-size: 18pt;
        font-weight: bold;
        color: {{ settings('branding.primary_color') }};
    }
    .cover-header-bar .tagline {
        font-size: 9pt;
        color: #CCC;
    }
    .cover-title {
        text-align: center;
        margin: 8mm 0;
    }
    .cover-title .main-title {
        font-size: 26pt;
        color: {{ settings('branding.primary_color') }};
        font-weight: bold;
        margin: 0;
    }
    .cover-title .sub-title {
        font-size: 14pt;
        color: #333;
        margin: 2mm 0 0 0;
    }
    .cover-dims-table {
        width: 60%;
        margin: 4mm auto;
        border: 2px solid {{ settings('branding.primary_color') }};
    }
    .cover-dims-table td {
        padding: 2mm 4mm;
        border: 1px solid #CCC;
    }
    .cover-dims-table .dim-label {
        background: #F5F5F5;
        font-weight: bold;
        width: 40%;
    }
</style>
</head>
<body>

{{-- mPDF Native Header/Footer --}}
<htmlpageheader name="quote-header">
    <div class="pdf-header">
        <table>
            <tr>
                <td style="width:25%; text-align:right;">
                    <div class="header-logo">
                        <div class="logo-badge">إم آي</div>
                    </div>
                </td>
                <td style="width:50%; text-align:center;">
                    <div class="brand">@setting('company.name_ar')</div>
                    <div class="sub-brand">@setting('company.tagline_ar')</div>
                </td>
                <td style="width:25%;" class="header-contact" dir="ltr">
                    <strong style="color:{{ settings('branding.primary_color') }}; font-size:8.5pt;">Contact Us</strong><br>
                    @foreach(settings('contact.phones', []) as $phone)
                        +{{ ltrim($phone, '+') }}@if(!$loop->last)<br>@endif
                    @endforeach
                    <br>@setting('contact.website')
                </td>
            </tr>
        </table>
    </div>
</htmlpageheader>

<htmlpagefooter name="quote-footer">
    <div class="pdf-footer-bar">
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="width:33%; text-align:center;">
                    @foreach(settings('contact.phones', []) as $phone)
                        {{ $phone }}@if(!$loop->last)<br>@endif
                    @endforeach
                </td>
                <td style="width:33%; text-align:center;">
                    @setting('contact.website')<br>@setting('contact.email')
                </td>
                <td style="width:33%; text-align:center;">
                    @setting('contact.address_ar')<br>@setting('company.name_ar')
                </td>
            </tr>
        </table>
        <div class="pdf-footer-meta">
            صفحة {PAGENO} | عرض سعر رقم: {{ $quotation->quotation_number }} | صالح حتى {{ $quotation->valid_until?->format('Y-m-d') }}
        </div>
        <div class="pdf-signature-line">
            توقيع الطرف الثاني: .................................................... | صفحة {PAGENO} من {nbpg}
        </div>
    </div>
</htmlpagefooter>

<sethtmlpageheader name="quote-header" page="ALL" value="on" show-this-page="1" />
<sethtmlpagefooter name="quote-footer" page="ALL" value="on" show-this-page="1" />

@if($quotation->status === 'draft')
<div class="watermark">مسودة</div>
@endif

{{-- ========== COVER PAGE ========== --}}
@include('quotations.partials.cover-page')

{{-- ========== QUOTATION INFO ========== --}}
<div class="page-break-before"></div>
@include('quotations.partials.quotation-info')

{{-- ========== ABOUT COMPANY ========== --}}
<div class="page-break-before" style="page-break-before: always;"></div>
@include('quotations.partials.about-company')

{{-- ========== TECHNICAL SECTIONS ========== --}}
@include('quotations.partials.tech-section')

{{-- ========== TECHNICAL SPECS ========== --}}
@if($technicalSpecs->count() > 0)
<div class="page-break-before" style="page-break-before: always;"></div>
@include('quotations.partials.technical-specs')
@endif

{{-- ========== CALCULATOR SYNC (TECHNICAL DATA) ========== --}}
@include('quotations.partials.calculator-sync')

{{-- ========== PRICING TABLE ========== --}}
<div class="page-break-before" style="page-break-before: always;"></div>
@include('quotations.partials.pricing-table', ['groupSubtotals' => $groupSubtotals ?? []])

{{-- ========== QUOTATION SUMMARY ========== --}}
@include('quotations.partials.quotation-summary')

{{-- ========== TERMS ========== --}}
@include('quotations.partials.terms')

{{-- ========== DISINFECTION GUIDE ========== --}}
@include('quotations.partials.disinfection-guide')

{{-- ========== AFTER SALES ========== --}}
@include('quotations.partials.after-sales')

</body>
</html>
