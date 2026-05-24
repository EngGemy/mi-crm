<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرض سعر {{ $quotation->quote_number }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Cairo', sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            line-height: 1.7;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            padding: 28px;
            margin-bottom: 20px;
        }
        .card-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #0f172a;
            padding-bottom: 12px;
            border-bottom: 2px solid #e2e8f0;
        }
        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .quote-num {
            background: linear-gradient(135deg, #C00000, #800000);
            color: white;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 16px;
        }
        .status {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        .status-draft { background: #fef3c7; color: #92400e; }
        .status-sent { background: #dbeafe; color: #1e40af; }
        .status-accepted { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        .info-item label {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 4px;
            display: block;
        }
        .info-item .value {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
        }
        .image-preview {
            text-align: center;
        }
        .image-preview img {
            max-width: 100%;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            font-family: 'Cairo', sans-serif;
            cursor: pointer;
            text-decoration: none;
            transition: opacity 0.2s;
        }
        .btn:hover { opacity: 0.9; }
        .btn-primary { background: #C00000; color: white; }
        .btn-secondary { background: #475569; color: white; }
        .btn-success { background: #25D366; color: white; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        th {
            background: #C00000;
            color: white;
            padding: 12px;
            text-align: right;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        tr:nth-child(even) { background: #f8fafc; }
        td.num { text-align: center; direction: ltr; }
        .total-row td { font-weight: 700; background: #f1f5f9; border-top: 2px solid #C00000; }
        @media (max-width: 600px) {
            .info-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-bar">
            <div class="quote-num">{{ $quotation->quote_number }}</div>
            <div class="status status-{{ $quotation->status }}">{{ $quotation->status_label }}</div>
        </div>

        <div class="card">
            <div class="card-title">بيانات العميل والعنبر</div>
            <div class="info-grid">
                <div class="info-item">
                    <label>اسم العميل</label>
                    <div class="value">{{ $quotation->client_name }}</div>
                </div>
                <div class="info-item">
                    <label>الهاتف</label>
                    <div class="value">{{ $quotation->client_phone ?: '-' }}</div>
                </div>
                <div class="info-item">
                    <label>العنوان</label>
                    <div class="value">{{ $quotation->client_address ?: '-' }}</div>
                </div>
                <div class="info-item">
                    <label>الطول</label>
                    <div class="value">{{ $quotation->length }} م</div>
                </div>
                <div class="info-item">
                    <label>العرض</label>
                    <div class="value">{{ $quotation->width }} م</div>
                </div>
                <div class="info-item">
                    <label>الارتفاع</label>
                    <div class="value">{{ $quotation->height }} م</div>
                </div>
                <div class="info-item">
                    <label>الأدوار</label>
                    <div class="value">{{ $quotation->tiers }}</div>
                </div>
                <div class="info-item">
                    <label>الخطوط</label>
                    <div class="value">{{ $quotation->lines }}</div>
                </div>
                <div class="info-item">
                    <label>عدد الطيور</label>
                    <div class="value">{{ number_format($quotation->bird_count) }}</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-title">تفاصيل التسعير</div>
            <table>
                <thead>
                    <tr>
                        <th>البند</th>
                        <th class="num">الكمية</th>
                        <th class="num">الوحدة</th>
                        <th class="num">الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>الخرسانات</td><td class="num">{{ number_format($quotation->length * $quotation->width, 2) }}</td><td class="num">م²</td><td class="num">{{ number_format($quotation->concrete_cost, 2) }}</td></tr>
                    <tr><td>الاستيل</td><td class="num">{{ number_format($quotation->length * $quotation->width, 2) }}</td><td class="num">م²</td><td class="num">{{ number_format($quotation->steel_cost, 2) }}</td></tr>
                    <tr><td>الحوائط</td><td class="num">{{ number_format($quotation->length * $quotation->height * 2, 2) }}</td><td class="num">م²</td><td class="num">{{ number_format($quotation->walls_cost, 2) }}</td></tr>
                    <tr><td>الخزانات</td><td class="num">1</td><td class="num">ثابت</td><td class="num">{{ number_format($quotation->tanks_cost, 2) }}</td></tr>
                    <tr><td>بند البطارية</td><td class="num">{{ number_format($quotation->bird_count) }}</td><td class="num">طائر</td><td class="num">{{ number_format($quotation->battery_cost, 2) }}</td></tr>
                    <tr><td>الشفاطات الخلفية</td><td class="num">{{ $quotation->back_fans_count }}</td><td class="num">مروحة</td><td class="num">{{ number_format($quotation->back_fans_cost, 2) }}</td></tr>
                    <tr><td>التبريد</td><td class="num">{{ number_format($quotation->cooling_units, 1) }}</td><td class="num">م</td><td class="num">{{ number_format($quotation->cooling_cost, 2) }}</td></tr>
                    <tr><td>الشبابيك</td><td class="num">{{ $quotation->windows_count }}</td><td class="num">شباك</td><td class="num">{{ number_format($quotation->windows_cost, 2) }}</td></tr>
                    <tr><td>الشفاطات الجانبية</td><td class="num">{{ $quotation->side_fans_count }}</td><td class="num">مروحة</td><td class="num">{{ number_format($quotation->side_fans_cost, 2) }}</td></tr>
                    <tr><td>الدفايات</td><td class="num">{{ $quotation->heaters_count }}</td><td class="num">دفاية</td><td class="num">{{ number_format($quotation->heaters_cost, 2) }}</td></tr>
                    <tr><td>نظام التحكم</td><td class="num">1</td><td class="num">ثابت</td><td class="num">{{ number_format($quotation->control_cost, 2) }}</td></tr>
                    <tr class="total-row">
                        <td colspan="3">المجموع الفرعي</td>
                        <td class="num">{{ number_format($quotation->subtotal, 2) }}</td>
                    </tr>
                    @if($quotation->vat_amount > 0)
                    <tr class="total-row">
                        <td colspan="3">ضريبة القيمة المضافة ({{ $quotation->vat_percentage }}%)</td>
                        <td class="num">{{ number_format($quotation->vat_amount, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="total-row" style="background:#C00000;color:white;">
                        <td colspan="3">الإجمالي النهائي</td>
                        <td class="num">{{ number_format($quotation->total, 2) }} EGP</td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if($quotation->image_path)
        <div class="card">
            <div class="card-title">صورة عرض السعر</div>
            <div class="image-preview">
                <img src="{{ $quotation->image_url }}" alt="{{ $quotation->quote_number }}">
            </div>
        </div>
        @endif

        <div class="card">
            <div class="actions">
                <a href="{{ route('sales.quotations.image', $quotation) }}" class="btn btn-primary" target="_blank">عرض الصورة</a>
                <a href="{{ route('sales.quotations.contract', $quotation) }}" class="btn btn-secondary">تحميل العقد</a>
                <a href="{{ route('sales.quotations.whatsapp', $quotation) }}" class="btn btn-success" target="_blank">مشاركة واتساب</a>
            </div>
        </div>
    </div>
</body>
</html>
