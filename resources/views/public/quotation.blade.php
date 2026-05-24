<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>عرض سعر {{ $quotation->quotation_number }} - {{ settings('company.name_ar') }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Tahoma', sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #C00000, #800000); color: white; padding: 30px; text-align: center; border-radius: 8px; margin-bottom: 30px; }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin: 20px 0; }
        .info-box { background: #f9f9f9; padding: 15px; border-right: 4px solid #C00000; border-radius: 4px; }
        .info-box label { display: block; font-size: 12px; color: #666; margin-bottom: 5px; }
        .info-box .value { font-size: 16px; font-weight: bold; color: #333; }
        .total-box { background: linear-gradient(135deg, #C00000, #800000); color: white; padding: 30px; text-align: center; border-radius: 8px; margin: 30px 0; }
        .total-box .label { font-size: 14px; opacity: 0.9; }
        .total-box .amount { font-size: 36px; font-weight: bold; margin: 10px 0; direction: ltr; }
        .actions { text-align: center; margin: 30px 0; }
        .btn { display: inline-block; background: #C00000; color: white; padding: 14px 32px; text-decoration: none; border-radius: 6px; margin: 5px; font-weight: bold; }
        .btn:hover { background: #800000; }
        .btn-secondary { background: #25D366; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #C00000; color: white; padding: 12px; text-align: right; }
        td { padding: 10px; border-bottom: 1px solid #eee; }
        tr:nth-child(even) { background: #FFEBEB; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ settings('company.name_ar') }}</h1>
            <p>{{ settings('company.tagline_ar') }}</p>
            <h2 style="margin-top: 20px;">عرض سعر #{{ $quotation->quotation_number }}</h2>
        </div>

        <h3 style="color: #C00000; margin-bottom: 15px;">بيانات العميل</h3>
        <div class="info-grid">
            <div class="info-box">
                <label>الاسم</label>
                <div class="value">{{ $quotation->customer->name }}</div>
            </div>
            <div class="info-box">
                <label>رقم الهاتف</label>
                <div class="value" dir="ltr">{{ $quotation->customer->phone }}</div>
            </div>
        </div>

        <h3 style="color: #C00000; margin: 30px 0 15px;">تفاصيل المشروع</h3>
        <div class="info-grid">
            <div class="info-box">
                <label>اسم المشروع</label>
                <div class="value">{{ $quotation->project_name }}</div>
            </div>
            <div class="info-box">
                <label>الموقع</label>
                <div class="value">{{ $quotation->installation_location }}</div>
            </div>
            <div class="info-box">
                <label>الأبعاد</label>
                <div class="value">
                    {{ $quotation->hall_length }} ×
                    {{ $quotation->hall_width }} ×
                    {{ $quotation->hall_height }} متر
                </div>
            </div>
            <div class="info-box">
                <label>عدد العنابر</label>
                <div class="value">{{ $quotation->hall_count }}</div>
            </div>
        </div>

        @if($quotation->items->isNotEmpty())
            <h3 style="color: #C00000; margin: 30px 0 15px;">بنود العرض</h3>
            <table>
                <thead>
                    <tr>
                        <th>الوصف</th>
                        <th>الكمية</th>
                        <th>السعر</th>
                        <th>الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quotation->items as $item)
                        <tr>
                            <td>{{ $item->description_ar }}</td>
                            <td>{{ $item->quantity }} {{ $item->unit }}</td>
                            <td dir="ltr">{{ number_format((float) $item->unit_price, 2) }}</td>
                            <td dir="ltr">{{ number_format((float) $item->total_price, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="total-box">
            <div class="label">الإجمالي النهائي شامل الضريبة</div>
            <div class="amount">
                {{ number_format((float) $quotation->total_amount, 2) }}
                @php
                    $symbol = match($quotation->currency) {
                        'EGP' => 'ج.م', 'USD' => '$', 'SAR' => 'ر.س', default => $quotation->currency
                    };
                @endphp
                {{ $symbol }}
            </div>
            <div class="label">صالح حتى: {{ $quotation->valid_until?->format('Y-m-d') }}</div>
        </div>

        <div class="actions">
            <a href="{{ route('quotations.public.pdf', ['quotation' => $quotation->quotation_number]) . '?' . request()->getQueryString() }}" class="btn">
                تحميل PDF كامل
            </a>
            <a href="https://wa.me/{{ preg_replace('/[^\d]/', '', settings('contact.phones')[0] ?? '') }}" class="btn btn-secondary">
                تواصل واتساب
            </a>
        </div>

        <div style="text-align: center; padding: 30px 0; border-top: 1px solid #eee; margin-top: 30px; color: #666;">
            <p>{{ settings('company.name_ar') }}</p>
            <p>{{ settings('contact.address_ar') }}</p>
            <p dir="ltr">{{ settings('contact.email') }} | {{ settings('contact.website') }}</p>
        </div>
    </div>
</body>
</html>
