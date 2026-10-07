<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>عرض سعر {{ $quotation->client_name }} — {{ $company }}</title>
    <meta name="description" content="عرض سعر {{ $quotation->project_type_label }} بمبلغ {{ number_format($total, 0) }} جنيه.">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="ar_EG">
    <meta property="og:title" content="عرض سعر {{ $quotation->client_name ?: 'خاص' }} — {{ $company }}">
    <meta property="og:description" content="{{ $quotation->project_type_label }} · {{ number_format((float) $quotation->length, 0) }}×{{ number_format((float) $quotation->width, 0) }} م · {{ number_format($total, 0) }} جنيه">
    @if($imageUrl)
        <meta property="og:image" content="{{ $imageUrl }}">
        <meta property="og:image:secure_url" content="{{ $imageUrl }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:type" content="image/png">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image" content="{{ $imageUrl }}">
    @endif
    <style>
        :root { color-scheme: dark; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Cairo", "Segoe UI", Tahoma, sans-serif;
            background:
                radial-gradient(900px 420px at 100% 0%, rgba(192,0,0,.28), transparent 55%),
                linear-gradient(180deg, #1a1412 0%, #0c0a09 100%);
            color: #f6f1e8;
        }
        .wrap { max-width: 880px; margin: 0 auto; padding: 36px 20px 56px; }
        .brand { display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; }
        .mark { font-weight: 800; letter-spacing: .18em; color: #e4c98a; font-size: 13px; }
        .mark span { color: #f6f1e8; letter-spacing: .08em; font-weight: 600; }
        .card {
            background: #14110f;
            border: 1px solid rgba(228,201,138,.28);
            box-shadow: 0 30px 80px rgba(0,0,0,.45);
        }
        .card img { display: block; width: 100%; height: auto; }
        .panel { margin-top: 22px; padding: 8px 4px 0; }
        h1 { margin: 0 0 6px; font-size: 28px; font-weight: 700; }
        .sub { margin: 0; color: #b7a89a; font-size: 15px; }
        .actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 22px; }
        a.btn {
            display: inline-block;
            text-decoration: none;
            background: #c00000;
            color: #fff;
            padding: 14px 28px;
            font-weight: 700;
            font-size: 16px;
        }
        a.ghost {
            display: inline-block;
            text-decoration: none;
            color: #e4c98a;
            border: 1px solid rgba(228,201,138,.45);
            padding: 13px 22px;
            font-weight: 600;
        }
        .note { margin-top: 28px; color: #8d8278; font-size: 13px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="brand">
            <div class="mark">MI <span>METAL INDUSTRIES</span></div>
            <div>{{ $quotation->quote_number }}</div>
        </div>

        @if($imageUrl)
            <div class="card">
                <img src="{{ $imageUrl }}" alt="كارت عرض السعر لـ {{ $quotation->client_name }}">
            </div>
        @endif

        <div class="panel">
            <h1>{{ $quotation->client_name ?: 'عرض السعر' }}</h1>
            <p class="sub">{{ $quotation->project_type_label }} · {{ number_format($total, 0) }} جنيه</p>
            <div class="actions">
                <a class="btn" href="{{ $pdfUrl }}">فتح عرض السعر</a>
                @if($imageUrl)
                    <a class="ghost" href="{{ $imageUrl }}" download>حفظ صورة الكارت</a>
                @endif
            </div>
            <p class="note">{{ $company }} — الرابط خاص بهذا العرض.</p>
        </div>
    </div>
</body>
</html>
