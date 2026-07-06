<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ $quotation->quote_number }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        @page { size: 1080px 1080px; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body {
            width: 1080px;
            height: 1080px;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        body {
            font-family: 'Cairo', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            direction: rtl;
            background: linear-gradient(145deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            color: #fff;
            position: absolute;
            top: 0;
            left: 0;
        }

        .circle-1 {
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(192,0,0,0.12) 0%, transparent 70%);
            top: -200px;
            left: -200px;
        }
        .circle-2 {
            position: absolute;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,255,255,0.04) 0%, transparent 70%);
            bottom: -100px;
            right: -100px;
        }

        .header {
            padding: 42px 48px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
        }
        .company-name {
            font-size: 26px;
            font-weight: 800;
            color: #fff;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        .company-name span { color: #C00000; }
        .badge {
            background: #C00000;
            color: #fff;
            padding: 10px 22px;
            border-radius: 30px;
            font-size: 15px;
            font-weight: 700;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .client-section {
            padding: 10px 48px 0;
            position: relative;
            z-index: 1;
        }
        .client-label {
            font-size: 14px;
            color: rgba(255,255,255,0.55);
            margin-bottom: 4px;
        }
        .client-name {
            font-size: 36px;
            font-weight: 900;
            color: #fff;
            line-height: 1.2;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
            max-width: 900px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .client-phone {
            font-size: 17px;
            color: rgba(255,255,255,0.7);
            margin-top: 4px;
            direction: ltr;
            text-align: right;
            white-space: nowrap;
        }

        .divider {
            width: 90px;
            height: 4px;
            background: #C00000;
            margin: 22px 48px;
            border-radius: 2px;
        }

        /* Dimensions row — 3 separate pills */
        .dims-row {
            padding: 0 48px;
            display: flex;
            gap: 14px;
            position: relative;
            z-index: 1;
        }
        .dim-pill {
            flex: 1;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 16px;
            padding: 18px 12px;
            text-align: center;
        }
        .dim-pill .lbl {
            font-size: 12px;
            color: rgba(255,255,255,0.5);
            margin-bottom: 6px;
            white-space: nowrap;
        }
        .dim-pill .num {
            font-size: 32px;
            font-weight: 900;
            color: #fff;
            direction: ltr;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
            line-height: 1;
        }
        .dim-pill .unit {
            font-size: 13px;
            color: rgba(255,255,255,0.55);
            margin-top: 4px;
        }

        /* Stats row */
        .stats-row {
            padding: 14px 48px 0;
            display: flex;
            gap: 14px;
            position: relative;
            z-index: 1;
        }
        .stat-card {
            flex: 1;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 20px 16px;
            text-align: center;
        }
        .stat-card .lbl {
            font-size: 13px;
            color: rgba(255,255,255,0.5);
            margin-bottom: 8px;
            white-space: nowrap;
        }
        .stat-card .num {
            font-size: 28px;
            font-weight: 800;
            color: #fff;
            direction: ltr;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
            line-height: 1.1;
        }
        .stat-card .num.accent { color: #ff6b6b; }
        .stat-card .sub {
            font-size: 12px;
            color: rgba(255,255,255,0.45);
            margin-top: 4px;
            white-space: nowrap;
        }

        .total-section {
            position: absolute;
            bottom: 120px;
            left: 48px;
            right: 48px;
            background: linear-gradient(135deg, #C00000, #8B0000);
            border-radius: 22px;
            padding: 28px 36px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 16px 50px rgba(192,0,0,0.35);
            z-index: 1;
            gap: 24px;
        }
        .total-label {
            font-size: 17px;
            font-weight: 600;
            opacity: 0.92;
            line-height: 1.5;
            flex-shrink: 1;
        }
        .total-value {
            font-size: 48px;
            font-weight: 900;
            direction: ltr;
            text-align: left;
            white-space: nowrap;
            flex-shrink: 0;
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.5px;
        }
        .total-value .currency {
            font-size: 20px;
            font-weight: 600;
            opacity: 0.85;
            margin-right: 6px;
        }

        .footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 22px 48px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(0,0,0,0.25);
            z-index: 1;
        }
        .footer-text {
            font-size: 12px;
            color: rgba(255,255,255,0.5);
            line-height: 1.4;
        }
        .footer-contact {
            font-size: 13px;
            color: rgba(255,255,255,0.6);
            direction: ltr;
            text-align: left;
            white-space: nowrap;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-15deg);
            font-size: 160px;
            font-weight: 900;
            color: rgba(255,255,255,0.025);
            white-space: nowrap;
            pointer-events: none;
            z-index: 0;
        }
    </style>
</head>
<body>
    <div class="circle-1"></div>
    <div class="circle-2"></div>
    <div class="watermark">{{ $quotation->quote_number }}</div>

    <div class="header">
        <div class="company-name"><span>MI</span> Metal Industries</div>
        <div class="badge">عرض سعر</div>
    </div>

    <div class="client-section">
        <div class="client-label">السادة /</div>
        <div class="client-name">{{ $quotation->client_name ?: '—' }}</div>
        @if($quotation->client_phone)
            <div class="client-phone">📞 {{ $quotation->client_phone }}</div>
        @endif
    </div>

    <div class="divider"></div>

    <div class="dims-row">
        <div class="dim-pill">
            <div class="lbl">الطول</div>
            <div class="num">{{ number_format((float) $quotation->length, 0) }}</div>
            <div class="unit">متر</div>
        </div>
        <div class="dim-pill">
            <div class="lbl">العرض</div>
            <div class="num">{{ number_format((float) $quotation->width, 0) }}</div>
            <div class="unit">متر</div>
        </div>
        <div class="dim-pill">
            <div class="lbl">الارتفاع</div>
            <div class="num">{{ number_format((float) $quotation->height, 2) }}</div>
            <div class="unit">متر</div>
        </div>
    </div>

    <div class="stats-row">
        <div class="stat-card">
            <div class="lbl">الخطوط</div>
            <div class="num">{{ $quotation->lines }}</div>
            <div class="sub">خط تربية</div>
        </div>
        <div class="stat-card">
            <div class="lbl">الأدوار</div>
            <div class="num">{{ $quotation->tiers }}</div>
            <div class="sub">دور</div>
        </div>
        <div class="stat-card">
            <div class="lbl">السعة الإجمالية</div>
            <div class="num accent">{{ number_format((int) $quotation->bird_count) }}</div>
            <div class="sub">طائر</div>
        </div>
    </div>

    <div class="total-section">
        <div class="total-label">الإجمالي التقريبي<br>شامل التركيب والتشغيل</div>
        <div class="total-value">
            <span class="currency">ج.م</span>{{ number_format((float) $displayTotal, 0) }}
        </div>
    </div>

    <div class="footer">
        <div class="footer-text">
            {{ $companyName }}<br>
            <span style="font-size: 12px; color: rgba(255,255,255,0.4);">صناعة وتوريد بطاريات الدواجن الأوتوماتيكية</span>
        </div>
        <div class="footer-contact">
            {{ $quotation->quote_number }}<br>
            {{ $quotation->created_at->format('Y-m-d') }}
        </div>
    </div>
</body>
</html>
