<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>{{ $title }}</title>
<style>
@php $primary = settings('branding.primary_color', '#C00000'); @endphp
body { font-family: cairo, sans-serif; direction: rtl; color: #1a1a1a; font-size: 11pt; }
.banner { background: {{ $primary }}; color: #fff; text-align: center; padding: 8mm 4mm; }
.banner h1 { font-size: 18pt; margin: 0; }
.banner p { margin: 2mm 0 0; font-size: 10pt; }
.meta { width: 100%; margin: 6mm 0; }
.meta td { border: none; padding: 1.5mm 2mm; vertical-align: top; }
.lbl { color: #777; font-size: 8pt; }
.val { font-weight: bold; }
table.lines { width: 100%; border-collapse: collapse; margin-top: 4mm; }
table.lines th { background: #222; color: #fff; padding: 2.5mm; font-size: 10pt; }
table.lines td { border-bottom: 0.4pt solid #e5e5e5; padding: 2.5mm; }
.num { direction: ltr; text-align: left; font-weight: bold; }
.total td { background: #fff5f5; color: {{ $primary }}; font-size: 13pt; font-weight: bold; border-bottom: none; padding-top: 4mm; }
</style>
</head>
<body>
    <div class="banner">
        <h1>{{ $title }}</h1>
        <p>@setting('company.name_ar', 'إم آي للصناعات المعدنية')</p>
    </div>

    <table class="meta">
        <tr>
            <td><span class="lbl">رقم العرض</span><div class="val">{{ $quotation->quote_number ?: '—' }}</div></td>
            <td><span class="lbl">العميل</span><div class="val">{{ $quotation->client_name ?: '—' }}</div></td>
            <td><span class="lbl">الموبايل</span><div class="val">{{ $quotation->client_phone ?: '—' }}</div></td>
        </tr>
        <tr>
            <td><span class="lbl">نوع العنبر</span><div class="val">{{ $barn }}</div></td>
            <td><span class="lbl">الأبعاد</span><div class="val">{{ \App\Services\Poultry\VentilationOffer::plain((float) $quotation->length) }} × {{ \App\Services\Poultry\VentilationOffer::plain((float) $quotation->width) }} × {{ \App\Services\Poultry\VentilationOffer::plain((float) $quotation->height) }} متر</div></td>
            <td><span class="lbl">عدد الطيور</span><div class="val">{{ number_format((int) $quotation->bird_count) }}</div></td>
        </tr>
    </table>

    <table class="lines">
        <thead>
            <tr>
                <th>البند</th>
                <th>العدد</th>
                <th>سعر الوحدة</th>
                <th>المبلغ</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as [$label, $qty, $unit, $amount])
                <tr>
                    <td>{{ $label }}</td>
                    <td class="num">{{ $qty }}</td>
                    <td class="num">{{ $unit }}</td>
                    <td class="num">{{ $amount }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td colspan="3">الإجمالي</td>
                <td class="num">{{ $total }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
