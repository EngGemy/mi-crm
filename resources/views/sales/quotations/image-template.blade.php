<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ $quotation->quote_number }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        @page { size: 1240px 1754px; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            width: 1240px;
            height: 1754px;
            font-family: 'Cairo', sans-serif;
            direction: rtl;
            background: #fff;
            color: #1a1a1a;
            position: relative;
            overflow: hidden;
        }

        /* Watermark */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 120px;
            font-weight: 800;
            color: rgba(0,0,0,0.04);
            white-space: nowrap;
            pointer-events: none;
            z-index: 0;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #C00000, #800000);
            color: white;
            padding: 40px 60px;
            position: relative;
            z-index: 1;
        }
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.3);
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 32px;
            font-weight: 800;
        }
        .quote-number {
            font-size: 18px;
            font-weight: 600;
            background: rgba(255,255,255,0.15);
            padding: 8px 20px;
            border-radius: 6px;
        }
        .header-bottom {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            opacity: 0.9;
        }

        /* Client Info */
        .client-section {
            padding: 30px 60px;
            background: #fafafa;
            border-bottom: 2px solid #eee;
            position: relative;
            z-index: 1;
        }
        .client-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .client-item label {
            font-size: 12px;
            color: #888;
            margin-bottom: 4px;
            display: block;
        }
        .client-item .value {
            font-size: 16px;
            font-weight: 700;
            color: #333;
        }

        /* Dimensions */
        .dims-section {
            padding: 20px 60px;
            display: flex;
            gap: 30px;
            background: #fff;
            position: relative;
            z-index: 1;
        }
        .dim-box {
            background: #f5f5f5;
            padding: 15px 25px;
            border-radius: 8px;
            text-align: center;
            flex: 1;
        }
        .dim-box .label {
            font-size: 12px;
            color: #888;
        }
        .dim-box .value {
            font-size: 20px;
            font-weight: 700;
            color: #C00000;
        }

        /* Table */
        .table-section {
            padding: 20px 60px;
            position: relative;
            z-index: 1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        thead th {
            background: #C00000;
            color: white;
            padding: 14px 12px;
            font-weight: 700;
            text-align: right;
        }
        thead th.num { text-align: center; }
        tbody td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            background: #fff;
        }
        tbody td.num {
            text-align: center;
            font-family: 'Cairo', sans-serif;
            direction: ltr;
        }
        tbody tr:nth-child(even) td {
            background: #fdf2f2;
        }
        tbody tr.total-row td {
            background: #f5f5f5;
            font-weight: 700;
            border-top: 2px solid #C00000;
        }
        tbody tr.grand-total td {
            background: #C00000;
            color: white;
            font-weight: 800;
            font-size: 16px;
        }

        /* Summary */
        .summary-section {
            padding: 20px 60px;
            position: relative;
            z-index: 1;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 20px;
            background: #fafafa;
            border-radius: 6px;
        }
        .summary-item .label { color: #666; font-size: 14px; }
        .summary-item .value { font-weight: 700; font-size: 15px; direction: ltr; }
        .summary-item.total {
            background: #C00000;
            color: white;
            grid-column: 1 / -1;
        }
        .summary-item.total .label,
        .summary-item.total .value {
            color: white;
            font-size: 18px;
            font-weight: 800;
        }

        /* Footer */
        .footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 30px 60px;
            background: #fafafa;
            border-top: 2px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1;
        }
        .footer-terms {
            font-size: 12px;
            color: #888;
            max-width: 60%;
        }
        .footer-signature {
            text-align: center;
        }
        .footer-signature .name {
            font-weight: 700;
            font-size: 16px;
            color: #333;
        }
        .footer-signature .title {
            font-size: 12px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="watermark">{{ $companyName }}</div>

    <div class="header">
        <div class="header-top">
            <div class="company-name">{{ $companyName }}</div>
            <div class="quote-number">{{ $quotation->quote_number }}</div>
        </div>
        <div class="header-bottom">
            <div>تاريخ العرض: {{ $quotation->created_at->format('Y-m-d') }}</div>
            <div>صالح لمدة 30 يوماً</div>
        </div>
    </div>

    <div class="client-section">
        <div class="client-grid">
            <div class="client-item">
                <label>اسم العميل</label>
                <div class="value">{{ $quotation->client_name }}</div>
            </div>
            <div class="client-item">
                <label>الهاتف</label>
                <div class="value">{{ $quotation->client_phone ?: '-' }}</div>
            </div>
            <div class="client-item">
                <label>العنوان</label>
                <div class="value">{{ $quotation->client_address ?: '-' }}</div>
            </div>
        </div>
    </div>

    <div class="dims-section">
        <div class="dim-box">
            <div class="label">الطول</div>
            <div class="value">{{ $quotation->length }} م</div>
        </div>
        <div class="dim-box">
            <div class="label">العرض</div>
            <div class="value">{{ $quotation->width }} م</div>
        </div>
        <div class="dim-box">
            <div class="label">الارتفاع</div>
            <div class="value">{{ $quotation->height }} م</div>
        </div>
        <div class="dim-box">
            <div class="label">الأدوار</div>
            <div class="value">{{ $quotation->tiers }}</div>
        </div>
        <div class="dim-box">
            <div class="label">الخطوط</div>
            <div class="value">{{ $quotation->lines }}</div>
        </div>
        <div class="dim-box">
            <div class="label">عدد الطيور</div>
            <div class="value">{{ number_format($result->birdCount) }}</div>
        </div>
    </div>

    <div class="table-section">
        <table>
            <thead>
                <tr>
                    <th>البند</th>
                    <th class="num">الكمية</th>
                    <th class="num">الوحدة</th>
                    <th class="num">سعر الوحدة</th>
                    <th class="num">الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @foreach($breakdown as $item)
                <tr>
                    <td>{{ $item['label_ar'] }}</td>
                    <td class="num">{{ number_format($item['quantity'], 2) }}</td>
                    <td class="num">{{ $item['unit'] }}</td>
                    <td class="num">{{ number_format((float) $item['unit_price'], 2) }}</td>
                    <td class="num">{{ number_format((float) $item['total'], 2) }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="4">المجموع الفرعي</td>
                    <td class="num">{{ number_format((float) $result->subtotal, 2) }}</td>
                </tr>
                @if((float) $result->vatAmount > 0)
                <tr class="total-row">
                    <td colspan="4">ضريبة القيمة المضافة ({{ $quotation->vat_percentage }}%)</td>
                    <td class="num">{{ number_format((float) $result->vatAmount, 2) }}</td>
                </tr>
                @endif
                <tr class="grand-total">
                    <td colspan="4">الإجمالي النهائي</td>
                    <td class="num">{{ number_format((float) $result->total, 2) }} EGP</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="summary-section">
        <div class="summary-grid">
            <div class="summary-item">
                <span class="label">الشفاطات الخلفية</span>
                <span class="value">{{ $result->backFansCount }} مروحة</span>
            </div>
            <div class="summary-item">
                <span class="label">وحدات التبريد</span>
                <span class="value">{{ $result->coolingUnits }} م</span>
            </div>
            <div class="summary-item">
                <span class="label">الشبابيك</span>
                <span class="value">{{ $result->windowsCount }} شباك</span>
            </div>
            <div class="summary-item">
                <span class="label">الشفاطات الجانبية</span>
                <span class="value">{{ $quotation->side_fans_count }} مروحة</span>
            </div>
            <div class="summary-item total">
                <span class="label">الإجمالي النهائي</span>
                <span class="value">{{ number_format((float) $result->total, 2) }} EGP</span>
            </div>
        </div>
    </div>

    <div class="footer">
        <div class="footer-terms">
            * الأسعار غير شاملة أعمال الموقع والحفر والردم إلا إذا تم الاتفاق عليها خطياً.<br>
            * تسديد 50% من قيمة العقد عند التعاقد والباقي قبل التسليم.<br>
            * العرض صالح لمدة 30 يوماً من تاريخ الإصدار.
        </div>
        <div class="footer-signature">
            <div class="name">{{ $managerName }}</div>
            <div class="title">مدير المبيعات</div>
        </div>
    </div>
</body>
</html>
