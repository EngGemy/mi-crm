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

        /* Decorative circles */
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

        /* Header */
        .header {
            padding: 45px 50px 25px;
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
        .company-name span {
            color: #C00000;
        }
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

        /* Client section */
        .client-section {
            padding: 15px 50px;
            position: relative;
            z-index: 1;
        }
        .client-label {
            font-size: 15px;
            color: rgba(255,255,255,0.55);
            margin-bottom: 6px;
        }
        .client-name {
            font-size: 38px;
            font-weight: 900;
            color: #fff;
            line-height: 1.2;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
            min-height: 55px;
        }
        .client-phone {
            font-size: 18px;
            color: rgba(255,255,255,0.7);
            margin-top: 4px;
            direction: ltr;
            text-align: right;
        }

        /* Divider */
        .divider {
            width: 100px;
            height: 4px;
            background: #C00000;
            margin: 25px 50px;
            border-radius: 2px;
        }

        /* Project specs */
        .specs {
            padding: 0 50px;
            display: flex;
            gap: 16px;
            position: relative;
            z-index: 1;
        }
        .spec-box {
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 14px;
            padding: 16px 14px;
            text-align: center;
            flex: 1;
            min-width: 0;
        }
        .spec-box .label {
            font-size: 13px;
            color: rgba(255,255,255,0.5);
            margin-bottom: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .spec-box .value {
            font-size: 18px;
            font-weight: 700;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .spec-box .value.red {
            color: #ff6b6b;
        }

        /* Total section */
        .total-section {
            position: absolute;
            bottom: 130px;
            left: 50px;
            right: 50px;
            background: linear-gradient(135deg, #C00000, #8B0000);
            border-radius: 20px;
            padding: 30px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 16px 50px rgba(192,0,0,0.35);
            z-index: 1;
            gap: 20px;
        }
        .total-label {
            font-size: 18px;
            font-weight: 600;
            opacity: 0.9;
            line-height: 1.4;
        }
        .total-value {
            font-size: 44px;
            font-weight: 900;
            direction: ltr;
            text-align: left;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .total-value .currency {
            font-size: 22px;
            font-weight: 600;
            opacity: 0.8;
        }

        /* Footer */
        .footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 25px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(0,0,0,0.25);
            z-index: 1;
        }
        .footer-text {
            font-size: 13px;
            color: rgba(255,255,255,0.5);
            line-height: 1.4;
        }
        .footer-contact {
            font-size: 13px;
            color: rgba(255,255,255,0.6);
            direction: ltr;
            text-align: left;
        }

        /* Quote number watermark */
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

    <div class="specs">
        <div class="spec-box">
            <div class="label">الطول × العرض × الارتفاع</div>
            <div class="value">{{ $quotation->length }} × {{ $quotation->width }} × {{ $quotation->height }} م</div>
        </div>
        <div class="spec-box">
            <div class="label">الأدوار × الخطوط</div>
            <div class="value">{{ $quotation->tiers }} × {{ $quotation->lines }}</div>
        </div>
        <div class="spec-box">
            <div class="label">السعة الإجمالية</div>
            <div class="value red">{{ number_format($quotation->bird_count) }} طائر</div>
        </div>
    </div>

    <div class="total-section">
        <div class="total-label">الإجمالي التقريبي<br>شامل التركيب والتشغيل</div>
        <div class="total-value">
            {{ number_format((float) $quotation->total, 0) }}
            <span class="currency">ج.م</span>
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
