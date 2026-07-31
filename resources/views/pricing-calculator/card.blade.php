<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
body {
    font-family: 'cairo', sans-serif;
    direction: rtl;
    margin: 0;
    padding: 0;
    background: #16213e;
    color: #fff;
}
.wrap {
    width: 100%;
    background: #16213e;
}
.pad { padding: 8mm 9mm 0 9mm; }
.header { width: 100%; }
.header td { border: none; vertical-align: middle; }
.brand { font-size: 16pt; font-weight: bold; color: #fff; }
.brand span { color: #C00000; }
.badge {
    background: #C00000;
    color: #fff;
    font-size: 10pt;
    font-weight: bold;
    padding: 2.5mm 5mm;
    text-align: center;
}
.client-lbl { font-size: 9pt; color: #aab; margin-top: 5mm; }
.client-name { font-size: 20pt; font-weight: bold; color: #fff; margin-top: 1mm; }
.client-phone { font-size: 11pt; color: #99a; direction: ltr; text-align: right; margin-top: 1mm; }
.line {
    width: 18mm;
    height: 1.2mm;
    background: #C00000;
    margin: 5mm 0 4mm 0;
}
.grid { width: 100%; border-spacing: 2.5mm; border-collapse: separate; }
.grid td {
    background: #1f2b4d;
    border: 0.4pt solid #2e3d66;
    text-align: center;
    padding: 3.5mm 2mm;
    width: 33%;
}
.grid .lbl { font-size: 8pt; color: #99a; }
.grid .num { font-size: 18pt; font-weight: bold; color: #fff; direction: ltr; margin-top: 1mm; }
.grid .num.red { color: #ff6b6b; }
.grid .unit { font-size: 8pt; color: #889; margin-top: 0.5mm; }
.total {
    margin: 5mm 9mm 0 9mm;
    background: #C00000;
    padding: 5mm 6mm;
}
.total table { width: 100%; }
.total td { border: none; color: #fff; vertical-align: middle; }
.total .t-lbl { font-size: 10pt; font-weight: bold; }
.total .t-sub { font-size: 8pt; opacity: 0.9; margin-top: 1mm; }
.total .t-val { font-size: 22pt; font-weight: bold; direction: ltr; text-align: left; }
.footer {
    margin-top: 5mm;
    background: #0f1730;
    padding: 3.5mm 9mm;
}
.footer table { width: 100%; }
.footer td { border: none; font-size: 8pt; color: #889; vertical-align: middle; }
.footer .ltr { direction: ltr; text-align: left; }
</style>
</head>
<body>
<div class="wrap">
    <div class="pad">
        <table class="header">
            <tr>
                <td style="width:70%;" class="brand"><span>MI</span> Metal Industries</td>
                <td style="width:30%; text-align:left;"><div class="badge">عرض سعر</div></td>
            </tr>
        </table>

        <div class="client-lbl">السادة /</div>
        <div class="client-name">{{ $quotation->client_name ?: '—' }}</div>
        @if($quotation->client_phone)
            <div class="client-phone">{{ $quotation->client_phone }}</div>
        @endif
        <div class="line"></div>

        <table class="grid">
            <tr>
                <td>
                    <div class="lbl">الطول</div>
                    <div class="num">{{ number_format((float) $quotation->length, 0) }}</div>
                    <div class="unit">متر</div>
                </td>
                <td>
                    <div class="lbl">العرض</div>
                    <div class="num">{{ number_format((float) $quotation->width, 0) }}</div>
                    <div class="unit">متر</div>
                </td>
                <td>
                    <div class="lbl">الارتفاع</div>
                    <div class="num">{{ number_format((float) $quotation->height, 2) }}</div>
                    <div class="unit">متر</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="lbl">الخطوط</div>
                    <div class="num">{{ $quotation->lines }}</div>
                    <div class="unit">خط</div>
                </td>
                <td>
                    <div class="lbl">الأدوار</div>
                    <div class="num">{{ $quotation->tiers }}</div>
                    <div class="unit">دور</div>
                </td>
                <td>
                    <div class="lbl">السعة</div>
                    <div class="num red">{{ number_format((int) $quotation->bird_count) }}</div>
                    <div class="unit">طائر</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="total">
        <table>
            <tr>
                <td style="width:45%;">
                    <div class="t-lbl">الإجمالي التقريبي</div>
                    <div class="t-sub">شامل التركيب والتشغيل</div>
                </td>
                <td class="t-val">{{ number_format((float) $displayTotal, 0) }} ج.م</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <table>
            <tr>
                <td style="width:55%;">{{ $companyName }}</td>
                <td class="ltr" style="width:45%;">{{ $quotation->quote_number }} · {{ $quotation->created_at?->format('Y-m-d') }}</td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>
