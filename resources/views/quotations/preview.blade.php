<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرض سعر #{{ $quotation->quotation_number }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: {{ settings('branding.primary_color') ?? '#C00000' }};
            --primary-dark: {{ settings('branding.primary_color') ?? '#C00000' }};
            --text: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --bg: #f1f5f9;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Cairo', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.7;
        }

        /* ========== Toolbar ========== */
        .toolbar {
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        .toolbar-brand { display: flex; align-items: center; gap: 14px; }
        .toolbar-brand .icon {
            width: 44px; height: 44px;
            background: linear-gradient(135deg, var(--primary) 0%, #8C0000 100%);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 20px;
        }
        .toolbar-brand .info h4 { font-size: 15px; font-weight: 800; color: var(--text); }
        .toolbar-brand .info span { font-size: 12px; color: var(--text-muted); }
        .toolbar-actions { display: flex; align-items: center; gap: 10px; }
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 9px 18px; border-radius: 10px;
            font-size: 13px; font-weight: 700; cursor: pointer;
            border: none; text-decoration: none; transition: all 0.2s ease; white-space: nowrap;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, #8C0000 100%);
            color: #fff; box-shadow: 0 4px 14px rgba(192,0,0,0.25);
        }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(192,0,0,0.35); }
        .btn-secondary {
            background: #f8fafc; color: var(--text); border: 1px solid var(--border);
        }
        .btn-secondary:hover { background: #f1f5f9; border-color: #cbd5e1; }
        .btn-ghost { background: transparent; color: var(--text-muted); }
        .btn-ghost:hover { color: var(--text); background: #f8fafc; }

        /* ========== A4 Page ========== */
        .page-wrapper {
            max-width: 960px;
            margin: 28px auto;
            padding: 0 20px;
            display: flex;
            flex-direction: column;
            gap: 28px;
            padding-bottom: 60px;
        }

        .page {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.04), 0 10px 15px -3px rgba(0,0,0,0.03);
            overflow: hidden;
            border: 1px solid var(--border);
            min-height: 800px;
            display: flex;
            flex-direction: column;
        }

        /* ========== Page Header / Footer ========== */
        .page-header {
            background: #fff;
            border-bottom: 3px solid var(--primary);
            padding: 14px 40px;
        }
        .page-header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }
        .page-header .header-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }
        .page-header .header-logo img {
            height: 55px; width: auto;
            border-radius: 8px;
            object-fit: contain;
        }
        .page-header .header-logo .logo-badge {
            width: 55px; height: 55px;
            background: linear-gradient(135deg, var(--primary) 0%, #8C0000 100%);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 20px; font-weight: 900;
            box-shadow: 0 4px 10px rgba(192,0,0,0.25);
            flex-shrink: 0;
        }
        .page-header .header-brand {
            text-align: center;
            flex: 1;
        }
        .page-header .header-brand .brand {
            font-size: 18px; font-weight: 900; color: var(--primary);
            letter-spacing: -0.3px;
        }
        .page-header .header-brand .sub-brand {
            font-size: 12px; color: #666; margin-top: 2px;
        }
        .page-header .header-contact {
            text-align: left;
            font-size: 11px;
            color: #666;
            line-height: 1.6;
            flex-shrink: 0;
        }
        .page-header .header-contact strong {
            color: var(--primary); font-size: 12px;
        }
        .page-footer {
            margin-top: auto;
            background: var(--primary);
            color: #fff;
            padding: 12px 40px;
            font-size: 12px;
        }
        .page-footer-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            text-align: center;
        }
        .page-footer-meta {
            text-align: center;
            font-size: 11px;
            color: rgba(255,255,255,0.9);
            padding-top: 8px;
            margin-top: 8px;
            border-top: 1px solid rgba(255,255,255,0.35);
        }

        /* ========== Cover Page ========== */
        .cover-header-bar {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
            text-align: center;
            padding: 28px 40px;
            position: relative;
            overflow: hidden;
        }
        .cover-header-bar::before {
            content: '';
            position: absolute;
            top: -50%; right: -20%;
            width: 300px; height: 300px;
            background: rgba(255,255,255,0.02);
            border-radius: 50%;
        }
        .cover-header-bar .cover-logo {
            margin-bottom: 12px;
            position: relative; z-index: 1;
        }
        .cover-header-bar .cover-logo img {
            height: 70px; width: auto;
            border-radius: 10px;
            object-fit: contain;
        }
        .cover-header-bar .cover-logo .logo-badge {
            width: 70px; height: 70px;
            background: linear-gradient(135deg, var(--primary) 0%, #8C0000 100%);
            border-radius: 16px;
            display: inline-flex; align-items: center; justify-content: center;
            color: #fff; font-size: 26px; font-weight: 900;
            box-shadow: 0 6px 16px rgba(192,0,0,0.3);
        }
        .cover-header-bar .logo-text {
            font-size: 24px; font-weight: 900; color: var(--primary);
            position: relative; z-index: 1;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .cover-header-bar .tagline {
            font-size: 14px; color: #ccc; margin-top: 6px;
            position: relative; z-index: 1;
        }

        .cover-hero {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 40px;
            padding: 40px;
            flex-wrap: wrap;
        }
        .cover-hero-text {
            text-align: center;
        }
        .cover-hero-text .main-title {
            font-size: 18px; font-weight: 800; color: #333; margin: 0; line-height: 1.4;
        }
        .cover-hero-text .sub-title {
            font-size: 28px; font-weight: 900; color: var(--primary); margin: 8px 0 0 0; line-height: 1.2;
        }
        .cover-hero-vertical {
            font-size: 14px; font-weight: 700; color: #333;
            letter-spacing: 2px; line-height: 1.8;
            text-align: center;
            text-transform: uppercase;
        }

        .cover-image {
            padding: 0 40px 20px;
            text-align: center;
        }
        .cover-image img {
            max-height: 300px; width: auto; border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .cover-image .placeholder {
            width: 100%; height: 240px; background: #f0f0f0;
            border-radius: 12px; display: flex; align-items: center; justify-content: center;
            color: #999; font-size: 16px;
        }

        .cover-project {
            text-align: center; padding: 10px 40px 20px;
        }
        .cover-project .title { font-size: 18px; font-weight: 800; color: #333; }
        .cover-project .subtitle { font-size: 14px; color: #666; margin-top: 4px; }

        .cover-dims-table {
            width: 60%; margin: 0 auto 20px;
            border: 2px solid var(--primary);
            border-collapse: collapse;
            font-size: 14px;
        }
        .cover-dims-table td {
            padding: 10px 16px; border: 1px solid #ccc;
        }
        .cover-dims-table .dim-label {
            background: #f5f5f5; font-weight: 700; width: 40%;
        }

        .cover-meta {
            text-align: center; padding-bottom: 30px;
            font-size: 13px; color: #666;
        }

        /* ========== Info Page ========== */
        .info-page-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            padding: 40px;
            align-items: start;
        }
        .info-card-box {
            background: #fafafa;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 24px;
        }
        .info-card-box h3 {
            font-size: 14px; font-weight: 800; color: var(--primary);
            margin-bottom: 16px; padding-bottom: 8px;
            border-bottom: 2px solid var(--primary);
        }
        .info-card-row {
            display: flex; justify-content: space-between;
            padding: 8px 0; border-bottom: 1px dashed #e2e8f0;
            font-size: 13px;
        }
        .info-card-row:last-child { border-bottom: none; }
        .info-card-row .label { color: var(--text-muted); font-weight: 500; }
        .info-card-row .value { font-weight: 700; color: var(--text); }

        .company-info-box {
            background: linear-gradient(135deg, #fafbfc 0%, #f8fafc 100%);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 28px;
            text-align: center;
        }
        .company-info-box .name {
            font-size: 20px; font-weight: 900; color: var(--primary);
            margin-bottom: 4px;
        }
        .company-info-box .website {
            font-size: 13px; color: #666; margin-bottom: 12px;
        }
        .company-info-box .contact-item {
            font-size: 13px; color: var(--text); margin: 4px 0;
        }

        /* ========== Content Sections ========== */
        .section-content { padding: 30px 40px; }
        .section-content h2 {
            font-size: 18px; font-weight: 800; color: var(--primary);
            margin: 0 0 16px 0;
            padding: 10px 16px;
            background: #FFF5F5;
            border-bottom: 2px solid var(--primary);
        }
        .section-content h3 {
            font-size: 15px; font-weight: 700; color: #333;
            margin: 16px 0 8px;
        }
        .section-content p, .section-content li {
            font-size: 14px; color: #444; line-height: 1.8;
        }
        .section-content ul, .section-content ol {
            padding-right: 20px; margin: 8px 0;
        }
        .section-content li { margin: 4px 0; }

        .about-box {
            background: #FAFAFA;
            border-right: 4px solid var(--primary);
            padding: 16px 20px;
            margin: 16px 0;
            font-size: 14px; line-height: 1.8;
        }
        .highlight-box {
            margin-top: 20px; padding: 16px;
            background: #FFF5F5;
            border: 1px solid var(--primary);
            text-align: center;
            border-radius: 10px;
        }
        .highlight-box p {
            font-size: 14px; font-weight: 700; color: var(--primary); margin: 0;
        }

        /* ========== Tables ========== */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin: 16px 0;
        }
        .data-table th {
            background: var(--primary);
            color: white;
            padding: 10px 12px;
            font-weight: 700;
            text-align: center;
            border: 1px solid #900;
        }
        .data-table td {
            padding: 10px 12px;
            border: 1px solid #ccc;
            vertical-align: top;
            text-align: center;
        }
        .data-table tr:nth-child(even) { background: #FFF0F0; }
        .data-table tr:nth-child(odd) { background: #FFFFFF; }
        .data-table .section-row {
            background: #333 !important; color: white !important;
        }
        .data-table .section-row td {
            background: #333; color: white; font-weight: 700;
        }
        .data-table .text-right { text-align: right; }

        /* ========== Totals ========== */
        .totals-table {
            width: 70%; margin: 20px 0 20px auto;
            border: none;
        }
        .totals-table td {
            border: none; padding: 8px 12px;
            font-size: 14px;
        }
        .totals-table .grand-total {
            background: var(--primary); color: white;
            font-size: 16px; font-weight: 800;
        }

        .note-box {
            margin-top: 16px; padding: 14px;
            background: #FFF5F5;
            border: 1px solid var(--primary);
            border-radius: 10px;
            font-size: 13px;
        }

        /* ========== Terms ========== */
        .term-box {
            margin: 12px 0; padding: 16px;
            background: #FAFAFA;
            border: 1px solid var(--primary);
            border-radius: 10px;
        }
        .term-box h4 {
            color: var(--primary); margin: 0 0 8px;
            border-bottom: 1px solid #e0e0e0; padding-bottom: 6px;
        }

        /* ========== Disinfection ========== */
        .steps-table {
            width: 100%; border-collapse: collapse; margin: 12px 0;
        }
        .steps-table td {
            border: 1px solid #ccc; padding: 10px 12px;
            font-size: 14px;
        }
        .steps-table .step-num {
            background: #f2f2f2; font-weight: 700; width: 50px; text-align: center;
        }
        .warning-box {
            margin-top: 12px; padding: 12px;
            background: #E8F5E9;
            border-right: 3px solid #4CAF50;
            border-radius: 8px;
            font-size: 13px; color: #2E7D32;
        }

        /* ========== Watermark ========== */
        .watermark {
            text-align: center;
            color: #e8e8e8;
            font-size: 48px;
            font-weight: 900;
            padding: 40px 0;
        }

        /* ========== Print ========== */
        @media print {
            .toolbar { display: none !important; }
            .page-wrapper { margin: 0; padding: 0; max-width: 100%; gap: 0; }
            .page {
                box-shadow: none; border: none; border-radius: 0;
                page-break-after: always; min-height: auto;
            }
            .page:last-child { page-break-after: auto; }
            body { background: #fff; }
        }
    </style>
</head>
<body>

<!-- Toolbar -->
<div class="toolbar">
    <div class="toolbar-brand">
        <div class="icon">📄</div>
        <div class="info">
            <h4>معاينة عرض السعر</h4>
            <span>#{{ $quotation->quotation_number }} — {{ $quotation->quotation_date?->format('Y-m-d') }}</span>
        </div>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('quotations.download', $quotation) }}" class="btn btn-secondary" target="_blank">
            ⬇️ PDF
        </a>
        <a href="{{ route('quotations.public', $quotation) }}" class="btn btn-secondary" target="_blank">
            🔗 رابط
        </a>
        <a href="{{ url('/admin/quotations') }}" class="btn btn-ghost">
            رجوع
        </a>
        <button onclick="window.print()" class="btn btn-primary">
            🖨️ طباعة
        </button>
    </div>
</div>

<div class="page-wrapper">

    {{-- ========== PAGE 1: COVER ========== --}}
    <div class="page">
        <div class="cover-header-bar">
            <div class="cover-logo">
                <div class="logo-badge">إم آي</div>
            </div>
            <div class="logo-text">{{ settings('company.name_ar') }}</div>
            <div class="tagline">{{ settings('company.tagline_ar') }}</div>
        </div>

        <div class="cover-hero">
            <div class="cover-hero-text">
                <p class="main-title">{{ settings('company.name_ar') }}</p>
                <p class="sub-title">— {{ settings('company.tagline_ar') }}</p>
            </div>
            <div class="cover-hero-vertical">
                Technical<br>Proposal<br>For<br>Automatic<br>Poultry<br>Cages
            </div>
        </div>

        <div class="cover-image">
            @if($coverImage && $coverImage->file_path)
                <img src="{{ asset('storage/' . $coverImage->file_path) }}" alt="صورة العنبر">
            @else
                <div class="placeholder">[صورة العنبر 3D]</div>
            @endif
        </div>

        <div class="cover-project">
            <p class="title">عرض مالي وفني لتجهيز عنبر {{ $quotation->hall_type ?? 'تسمين' }}</p>
            <p class="subtitle">{{ $quotation->project_name }}</p>
        </div>

        <table class="cover-dims-table">
            <tr>
                <td class="dim-label">نوع العنبر</td>
                <td>{{ $quotation->hall_type ?? 'تسمين' }}</td>
            </tr>
            <tr>
                <td class="dim-label">طول العنبر</td>
                <td>{{ $quotation->hall_length ? number_format($quotation->hall_length, 0) . ' متر' : '-' }}</td>
            </tr>
            <tr>
                <td class="dim-label">عرض العنبر</td>
                <td>{{ $quotation->hall_width ? number_format($quotation->hall_width, 0) . ' متر' : '-' }}</td>
            </tr>
            <tr>
                <td class="dim-label">ارتفاع العنبر</td>
                <td>{{ $quotation->hall_height ? number_format($quotation->hall_height, 0) . ' متر' : '-' }}</td>
            </tr>
            @if($quotation->hall_count > 1)
            <tr>
                <td class="dim-label">عدد العنابر</td>
                <td>{{ $quotation->hall_count }}</td>
            </tr>
            @endif
            @if($quotation->bird_capacity)
            <tr>
                <td class="dim-label">السعة</td>
                <td>{{ number_format($quotation->bird_capacity) }} طائر</td>
            </tr>
            @endif
        </table>

        <div class="cover-meta">
            <p>تاريخ العرض: {{ $quotation->quotation_date?->format('Y-m-d') }}</p>
            <p>صالح حتى: {{ $quotation->valid_until?->format('Y-m-d') }}</p>
            <p>رقم العرض: {{ $quotation->quotation_number }}</p>
        </div>

        @if($quotation->status === 'draft')
        <div class="watermark">مسودة</div>
        @endif
    </div>

    {{-- ========== PAGE 2: QUOTATION INFO ========== --}}
    <div class="page">
        @include('quotations.partials.preview-header')

        <div class="section-content">
            <div class="info-page-grid">
                <div class="info-card-box">
                    <h3>بيانات عرض السعر</h3>
                    <div class="info-card-row">
                        <span class="label">تاريخ العرض</span>
                        <span class="value">{{ $quotation->quotation_date?->format('Y-m-d') ?? '—' }}</span>
                    </div>
                    <div class="info-card-row">
                        <span class="label">صالح حتى</span>
                        <span class="value">{{ $quotation->valid_until?->format('Y-m-d') ?? '—' }}</span>
                    </div>
                    <div class="info-card-row">
                        <span class="label">رقم العرض</span>
                        <span class="value" dir="ltr">{{ $quotation->quotation_number }}</span>
                    </div>
                    <div class="info-card-row">
                        <span class="label">العميل</span>
                        <span class="value">{{ $customer->name ?? '—' }}</span>
                    </div>
                    <div class="info-card-row">
                        <span class="label">المشروع</span>
                        <span class="value">{{ $quotation->project_name ?? '—' }}</span>
                    </div>
                </div>

                <div class="company-info-box">
                    <div class="name">{{ settings('company.name_ar') }}</div>
                    <div class="website">{{ settings('contact.website') }}</div>
                    <div class="contact-item">{{ settings('contact.email') }}</div>
                    @foreach(settings('contact.phones', []) as $phone)
                    <div class="contact-item" dir="ltr">+{{ ltrim($phone, '+') }}</div>
                    @endforeach
                </div>
            </div>

            <div style="padding: 20px 40px; text-align: center; font-size: 14px; color: #444;">
                <strong>العنوان:</strong> {{ settings('contact.address_ar') ?? '—' }}
            </div>
        </div>

        <div class="page-footer">
            <div class="page-footer-grid">
                <div>
                    @foreach(settings('contact.phones', []) as $phone)
                        {{ $phone }}@if(!$loop->last)<br>@endif
                    @endforeach
                </div>
                <div>
                    {{ settings('contact.website') }}<br>{{ settings('contact.email') }}
                </div>
                <div>
                    {{ settings('contact.address_ar') }}<br>{{ settings('company.name_ar') }}
                </div>
            </div>
            <div class="page-footer-meta">
                صفحة 2 | عرض سعر رقم: {{ $quotation->quotation_number }} | صالح حتى {{ $quotation->valid_until?->format('Y-m-d') }}
            </div>
        </div>
    </div>

    {{-- ========== PAGE 3: ABOUT COMPANY ========== --}}
    <div class="page">
        @include('quotations.partials.preview-header')

        <div class="section-content">
            <h2 style="text-align:center; border:none; background:none;">{{ settings('company.name_ar') }}</h2>
            <h3 style="text-align:center; color:#666; font-size:14px; margin-top:0;">{{ settings('company.tagline_ar') }}</h3>

            <div class="about-box">
                {!! nl2br(e(settings('company.about_ar'))) !!}
            </div>

            <h3>♦ رسالتنا</h3>
            <p>توفير حلول متكاملة لمزارع الدواجن بأعلى معايير الجودة العالمية وبأسعار تنافسية، مع ضمان استمرارية الدعم الفني ما بعد البيع.</p>

            <h3>♦ رؤيتنا</h3>
            <p>أن نكون الشريك الأول لكل مستثمر في مجال إنتاج الدواجن في مصر والشرق الأوسط وإفريقيا.</p>

            <h3>♦ منتجاتنا الرئيسية</h3>
            <ul>
                <li>بطاريات تسمين الدواجن (3 و 4 أدوار)</li>
                <li>بطاريات بياض الدواجن (شكل H)</li>
                <li>منظومات التغذية والشرب الأوتوماتيكية</li>
                <li>منظومات التهوية والتبريد والتدفئة</li>
                <li>الأعمال المعدنية والإنشائية للعنابر</li>
                <li>لوحات الكهرباء وأنظمة التحكم الآلي</li>
            </ul>

            <h3>♦ لماذا نحن؟</h3>
            <ul>
                <li><strong>جودة عالمية:</strong> خامات أوروبية وصاج مجلفن معالج ضد الصدأ</li>
                <li><strong>ضمان طويل:</strong> {{ settings('defaults.warranty_years_steel', 12) }} سنة للصاج والسلك ضد الصدأ</li>
                <li><strong>دعم فني:</strong> فريق متخصص للتركيب والتدريب والصيانة</li>
                <li><strong>سرعة التنفيذ:</strong> مدة تصنيع تبدأ من {{ settings('defaults.manufacturing_days', 105) }} يوم عمل</li>
                <li><strong>تجربة عملاء:</strong> مئات المشاريع المنفذة في مصر والسعودية والعراق والسودان</li>
            </ul>

            <div class="highlight-box">
                <p>{{ settings('company.name_ar') }} — شريكك الموثوق في نجاح مشروعك</p>
            </div>
        </div>

        <div class="page-footer">
            <div class="page-footer-grid">
                <div>
                    @foreach(settings('contact.phones', []) as $phone)
                        {{ $phone }}@if(!$loop->last)<br>@endif
                    @endforeach
                </div>
                <div>
                    {{ settings('contact.website') }}<br>{{ settings('contact.email') }}
                </div>
                <div>
                    {{ settings('contact.address_ar') }}<br>{{ settings('company.name_ar') }}
                </div>
            </div>
            <div class="page-footer-meta">
                صفحة 3 | عرض سعر رقم: {{ $quotation->quotation_number }} | صالح حتى {{ $quotation->valid_until?->format('Y-m-d') }}
            </div>
        </div>
    </div>

    {{-- ========== PAGE 4: TECHNICAL SECTIONS ========== --}}
    <div class="page">
        @include('quotations.partials.preview-header')

        <div class="section-content">
            @forelse($sectionAttachments as $att)
                @php $section = $att->section; @endphp
                @if(!$section) @continue @endif

                <div style="margin: 16px 0; padding: 16px; border: 1px solid #e0e0e0; border-radius: 10px;">
                    <div style="background: #f5f5f5; border-right: 4px solid var(--primary); padding: 8px 12px; margin-bottom: 12px; border-radius: 6px;">
                        <h3 style="margin:0; color: var(--primary);">{{ $section->title_ar ?: $section->title_en }}</h3>
                    </div>

                    @php
                        $sectionImages = $quotation->images
                            ->where('section_id', $section->id)
                            ->sortBy('sort_order');
                        $libImages = collect($section->default_images ?? [])
                            ->map(fn($id) => \App\Models\ImageLibrary::find($id))
                            ->filter();
                    @endphp

                    @if($sectionImages->count() > 0)
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin: 12px 0;">
                            @foreach($sectionImages->take(4) as $img)
                                @php $path = $img->file_path ?: ($img->imageLibrary?->file_path); @endphp
                                @if($path)
                                    <img src="{{ asset('storage/' . $path) }}" alt="" style="width:100%; max-height:200px; object-fit:cover; border-radius:8px; border:1px solid #ddd;">
                                @endif
                            @endforeach
                        </div>
                    @elseif($libImages->count() > 0)
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin: 12px 0;">
                            @foreach($libImages->take(4) as $img)
                                @if($img && $img->file_path)
                                    <img src="{{ asset('storage/' . $img->file_path) }}" alt="" style="width:100%; max-height:200px; object-fit:cover; border-radius:8px; border:1px solid #ddd;">
                                @endif
                            @endforeach
                        </div>
                    @endif

                    <div style="font-size: 14px; line-height: 1.8; color: #444;">
                        @if($att->content_override_ar)
                            {!! nl2br(e($att->content_override_ar)) !!}
                        @elseif($section->content_ar)
                            {!! nl2br(e($section->content_ar)) !!}
                        @elseif($att->content_override_en)
                            {!! nl2br(e($att->content_override_en)) !!}
                        @elseif($section->content_en)
                            {!! nl2br(e($section->content_en)) !!}
                        @endif
                    </div>
                </div>
            @empty
                <p style="text-align:center; color:#999; padding:40px 0;">لا توجد أقسام فنية محددة</p>
            @endforelse
        </div>

        <div class="page-footer">
            <div class="page-footer-grid">
                <div>
                    @foreach(settings('contact.phones', []) as $phone)
                        {{ $phone }}@if(!$loop->last)<br>@endif
                    @endforeach
                </div>
                <div>
                    {{ settings('contact.website') }}<br>{{ settings('contact.email') }}
                </div>
                <div>
                    {{ settings('contact.address_ar') }}<br>{{ settings('company.name_ar') }}
                </div>
            </div>
            <div class="page-footer-meta">
                صفحة 4 | عرض سعر رقم: {{ $quotation->quotation_number }} | صالح حتى {{ $quotation->valid_until?->format('Y-m-d') }}
            </div>
        </div>
    </div>

    {{-- ========== PAGE 5: PRICING ========== --}}
    <div class="page">
        @include('quotations.partials.preview-header')

        <div class="section-content">
            <h2>البند المالي والفني | Financial & Technical Proposal</h2>

            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:30%;">البند<br><span style="font-size:10px; font-weight:400;">Description</span></th>
                        <th style="width:14%;">سعر الوحدة<br><span style="font-size:10px; font-weight:400;">Unit price</span></th>
                        <th style="width:10%;">الوحدة<br><span style="font-size:10px; font-weight:400;">Unit</span></th>
                        <th style="width:10%;">الكمية<br><span style="font-size:10px; font-weight:400;">Each</span></th>
                        <th style="width:10%;">الضريبة<br><span style="font-size:10px; font-weight:400;">Taxed</span></th>
                        <th style="width:14%;">المبلغ<br><span style="font-size:10px; font-weight:400;">Amount</span></th>
                    </tr>
                </thead>
                <tbody>
                    @php $rowIdx = 0; @endphp
                    @foreach($groupedItems as $sectionName => $groupItems)
                        <tr class="section-row">
                            <td colspan="6">{{ $sectionName }}</td>
                        </tr>
                        @foreach($groupItems as $item)
                        <tr>
                            <td class="text-right">
                                {{ $item->description_ar ?: $item->description_en }}
                                @if($item->description_en && $item->description_ar)
                                    <br><span style="font-size:11px; color:#666;">{{ $item->description_en }}</span>
                                @endif
                            </td>
                            <td dir="ltr">{{ number_format((float) $item->unit_price, 2) }}</td>
                            <td>{{ \App\Support\QuotationPdfLabels::unit($item->unit) }}</td>
                            <td dir="ltr">{{ number_format((float) $item->quantity, 2) }}</td>
                            <td>{{ $item->is_taxable ? 'نعم' : 'لا' }}</td>
                            <td dir="ltr" style="font-weight:700;">{{ number_format((float) $item->total_price, 2) }}</td>
                        </tr>
                        @endforeach
                    @endforeach

                    @if(count($groupedItems) === 0)
                    <tr>
                        <td colspan="6" style="text-align:center; color:#999;">لا توجد بنود تسعير</td>
                    </tr>
                    @endif
                </tbody>
            </table>

            @php $cur = \App\Support\QuotationPdfLabels::currency($quotation->currency); @endphp
            <table class="totals-table">
                <tr>
                    <td style="text-align:right; font-weight:700;">المجموع الفرعي | SUBTOTAL:</td>
                    <td style="text-align:left;" dir="ltr">{{ number_format((float) $quotation->subtotal, 2) }}&nbsp;{{ $cur }}</td>
                </tr>
                @if((float) $quotation->discount_amount > 0)
                <tr>
                    <td style="text-align:right;">الخصم{{ $quotation->discount_percentage > 0 ? ' (' . number_format($quotation->discount_percentage, 0) . '%)' : '' }}:</td>
                    <td style="text-align:left; color:var(--primary);" dir="ltr">− {{ number_format((float) $quotation->discount_amount, 2) }}&nbsp;{{ $cur }}</td>
                </tr>
                @endif
                @if((float) $quotation->vat_amount > 0)
                <tr>
                    <td style="text-align:right;">ضريبة القيمة المضافة ({{ number_format((float) $quotation->vat_percentage, 0) }}%):</td>
                    <td style="text-align:left;" dir="ltr">{{ number_format((float) $quotation->vat_amount, 2) }}&nbsp;{{ $cur }}</td>
                </tr>
                @endif
                <tr class="grand-total">
                    <td style="text-align:right;"><strong>الإجمالي النهائي | TOTAL:</strong></td>
                    <td style="text-align:left;" dir="ltr"><strong>{{ number_format((float) $quotation->total_amount, 2) }}&nbsp;{{ $cur }}</strong></td>
                </tr>
            </table>

            <div class="note-box">
                <strong>ملاحظة:</strong> الأسعار المذكورة أعلاه صالحة لمدة
                <strong>{{ $quotation->validity_period_days }} أيام</strong>
                من تاريخ العرض ({{ $quotation->quotation_date?->format('Y-m-d') }}).
                الأسعار لا تشمل أي رسوم جمركية أو ضرائب محلية إلا إذا ذُكر خلاف ذلك.
            </div>
        </div>

        <div class="page-footer">
            <div class="page-footer-grid">
                <div>
                    @foreach(settings('contact.phones', []) as $phone)
                        {{ $phone }}@if(!$loop->last)<br>@endif
                    @endforeach
                </div>
                <div>
                    {{ settings('contact.website') }}<br>{{ settings('contact.email') }}
                </div>
                <div>
                    {{ settings('contact.address_ar') }}<br>{{ settings('company.name_ar') }}
                </div>
            </div>
            <div class="page-footer-meta">
                صفحة 5 | عرض سعر رقم: {{ $quotation->quotation_number }} | صالح حتى {{ $quotation->valid_until?->format('Y-m-d') }}
            </div>
        </div>
    </div>

    {{-- ========== PAGE 6: TERMS ========== --}}
    <div class="page">
        @include('quotations.partials.preview-header')

        <div class="section-content">
            <h2>البنود والشروط | Terms & Conditions</h2>

            @forelse($renderedTerms as $row)
                <div class="term-box">
                    <h4>{{ $row['term']->title_ar ?: $row['term']->title_en }}</h4>
                    <div style="font-size:14px; line-height:1.7;">
                        {!! $row['rendered_content'] !!}
                    </div>
                </div>
            @empty
                <div class="term-box">
                    <p style="text-align:center; color:#999;">لا توجد بنود محددة</p>
                </div>
            @endforelse
        </div>

        <div class="page-footer">
            <div class="page-footer-grid">
                <div>
                    @foreach(settings('contact.phones', []) as $phone)
                        {{ $phone }}@if(!$loop->last)<br>@endif
                    @endforeach
                </div>
                <div>
                    {{ settings('contact.website') }}<br>{{ settings('contact.email') }}
                </div>
                <div>
                    {{ settings('contact.address_ar') }}<br>{{ settings('company.name_ar') }}
                </div>
            </div>
            <div class="page-footer-meta">
                صفحة 6 | عرض سعر رقم: {{ $quotation->quotation_number }} | صالح حتى {{ $quotation->valid_until?->format('Y-m-d') }}
            </div>
        </div>
    </div>

    {{-- ========== PAGE 7: DISINFECTION GUIDE ========== --}}
    <div class="page">
        @include('quotations.partials.preview-header')

        <div class="section-content">
            @php
            $title = settings('pdf.disinfection_title', 'دليل التطهير');
            $subtitle = settings('pdf.disinfection_subtitle', 'إرشادات عامة لتطهير العنبر بين الدورات:');
            $steps = settings('pdf.disinfection_steps', [
                ['title' => 'التنظيف الجاف', 'desc' => 'إزالة كل الروث والغبار من الأقفاص والأرضية باستخدام المكنسة والمظلة.'],
                ['title' => 'الغسيل بالماء', 'desc' => 'غسيل جميع الأسطح بالماء تحت ضغط عالٍ للتأكد من إزالة الرواسب العضوية.'],
                ['title' => 'التطهير الكيميائي', 'desc' => 'رش محاليل مطهرة (مثل فورمالديهايد أو فينول) على جميع الأسطح وتركها 24 ساعة.'],
                ['title' => 'التعقيم بالحرارة', 'desc' => 'رفع درجة حرارة العنبر إلى 60 درجة مئوية لمدة 24 ساعة (إن أمكن).'],
                ['title' => 'الفترة الصحية', 'desc' => 'ترك العنبر فارغاً لمدة 7-14 يوماً قبل استقبال الدفعة الجديدة.'],
                ['title' => 'الفحص البكتيري', 'desc' => 'أخذ مسحات من الأسطح للتأكد من خلوها من البكتيريا الضارة.'],
            ]);
            $warningTitle = settings('pdf.disinfection_warning_title', 'تنبيه:');
            $warningText = settings('pdf.disinfection_warning_text', 'يوصى بتطهير العنبر بين كل دورة وأخرى. فشل التطهير السليم قد يؤدي إلى انتشار الأمراض وخسارة اقتصادية كبيرة.');
            @endphp

            <h2>{{ $title }} | Disinfection Guide</h2>

            <p style="font-size:14px; font-weight:700; color:var(--primary); margin:0 0 12px;">{{ $subtitle }}</p>

            <table class="steps-table">
                @foreach($steps as $idx => $step)
                <tr>
                    <td class="step-num">{{ $idx + 1 }}</td>
                    <td>
                        <strong>{{ $step['title'] ?? $step[0] ?? '' }}:</strong>
                        {{ $step['desc'] ?? $step[1] ?? '' }}
                    </td>
                </tr>
                @endforeach
            </table>

            <div class="warning-box">
                <strong>{{ $warningTitle }}</strong> {{ $warningText }}
            </div>
        </div>

        <div class="page-footer">
            <div class="page-footer-grid">
                <div>
                    @foreach(settings('contact.phones', []) as $phone)
                        {{ $phone }}@if(!$loop->last)<br>@endif
                    @endforeach
                </div>
                <div>
                    {{ settings('contact.website') }}<br>{{ settings('contact.email') }}
                </div>
                <div>
                    {{ settings('contact.address_ar') }}<br>{{ settings('company.name_ar') }}
                </div>
            </div>
            <div class="page-footer-meta">
                صفحة 7 | عرض سعر رقم: {{ $quotation->quotation_number }} | صالح حتى {{ $quotation->valid_until?->format('Y-m-d') }}
            </div>
        </div>
    </div>

    {{-- ========== PAGE 8: AFTER-SALES ========== --}}
    <div class="page">
        @include('quotations.partials.preview-header')

        <div class="section-content">
            <h2>سياسة خدمة ما بعد البيع | After-Sales Service Policy</h2>

            <h3>♦ الضمان</h3>
            <ul>
                <li>ضمان عيوب التصنيع: <strong>{{ settings('defaults.warranty_months', 12) }} شهراً</strong> من تاريخ التركيب.</li>
                <li>ضمان الصاج والسلك المجلفن: <strong>{{ settings('defaults.warranty_years_steel', 12) }} سنة</strong> ضد الصدأ والتآكل.</li>
                <li>تشمل الأجزاء المستبدلة خلال فترة الضمان بالكامل.</li>
            </ul>

            <h3>♦ خدمات ما بعد البيع</h3>
            <ul>
                <li><strong>التركيب والتشغيل:</strong> إرسال فريق فني متخصص لتركيب البطاريات والتشغيل التجريبي.</li>
                <li><strong>التدريب:</strong> تدريب عملي لعمالة المزرعة على التشغيل والصيانة الأساسية.</li>
                <li><strong>الصيانة الدورية:</strong> زيارات صيانة دورية كل 3 أشهر (بعد انتهاء الضمان).</li>
                <li><strong>الدعم الفني:</strong> خط ساخن للاستشارات الفنية على مدار الساعة.</li>
                <li><strong>قطع الغيار:</strong> توفير قطع غيار أصلية لمدة {{ settings('defaults.spare_parts_years', 12) }} سنة بأسعار التكلفة.</li>
            </ul>

            <h3>♦ التواصل معنا</h3>
            <table class="data-table" style="margin:12px 0;">
                <tr>
                    <td style="background:#f2f2f2; font-weight:700; width:30%;">العنوان</td>
                    <td class="text-right">{{ settings('contact.address_ar') }}</td>
                </tr>
                <tr>
                    <td style="background:#f2f2f2; font-weight:700;">الموبايل</td>
                    <td class="text-right" dir="ltr">{{ implode(' / ', settings('contact.phones', [])) }}</td>
                </tr>
                <tr>
                    <td style="background:#f2f2f2; font-weight:700;">البريد</td>
                    <td class="text-right" dir="ltr">{{ settings('contact.email') }}</td>
                </tr>
                <tr>
                    <td style="background:#f2f2f2; font-weight:700;">الموقع الإلكتروني</td>
                    <td class="text-right" dir="ltr">{{ settings('contact.website') }}</td>
                </tr>
            </table>

            <div class="highlight-box" style="margin-top:24px;">
                <p>شكراً لاختياركم {{ settings('company.name_ar') }}</p>
                <p style="font-size:13px; color:#666; margin-top:4px;">نتمنى لكم كل التوفيق في مشروعكم</p>
            </div>
        </div>

        <div class="page-footer">
            <div class="page-footer-grid">
                <div>
                    @foreach(settings('contact.phones', []) as $phone)
                        {{ $phone }}@if(!$loop->last)<br>@endif
                    @endforeach
                </div>
                <div>
                    {{ settings('contact.website') }}<br>{{ settings('contact.email') }}
                </div>
                <div>
                    {{ settings('contact.address_ar') }}<br>{{ settings('company.name_ar') }}
                </div>
            </div>
            <div class="page-footer-meta">
                صفحة 8 | عرض سعر رقم: {{ $quotation->quotation_number }} | صالح حتى {{ $quotation->valid_until?->format('Y-m-d') }}
            </div>
        </div>
    </div>

</div>

</body>
</html>
