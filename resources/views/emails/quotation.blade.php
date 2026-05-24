<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, Tahoma, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #C00000; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .button { display: inline-block; background: #C00000; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 10px 0; }
        .info-box { background: white; border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-right: 4px solid #C00000; }
        .footer { text-align: center; padding: 15px; color: #666; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        td:first-child { font-weight: bold; color: #555; }
        td:last-child { text-align: left; direction: ltr; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ settings('company.name_ar') }}</h1>
            <p>{{ settings('company.tagline_ar') }}</p>
        </div>

        <div class="content">
            <h2>السيد/ {{ $quotation->customer->name }}</h2>

            <p>السلام عليكم ورحمة الله وبركاته،</p>

            @if($customMessage)
                <div class="info-box">
                    {!! nl2br(e($customMessage)) !!}
                </div>
            @endif

            <p>نرفق لكم عرض السعر الخاص بمشروعكم:</p>

            <table>
                <tr>
                    <td>رقم العرض</td>
                    <td>{{ $quotation->quotation_number }}</td>
                </tr>
                <tr>
                    <td>المشروع</td>
                    <td>{{ $quotation->project_name }}</td>
                </tr>
                <tr>
                    <td>تاريخ العرض</td>
                    <td>{{ $quotation->quotation_date?->format('Y-m-d') }}</td>
                </tr>
                <tr>
                    <td>صالح حتى</td>
                    <td>{{ $quotation->valid_until?->format('Y-m-d') }}</td>
                </tr>
                <tr>
                    <td>الإجمالي</td>
                    <td>
                        <strong style="color: #C00000; font-size: 18px;">
                            {{ number_format((float) $quotation->total_amount, 2) }}
                            {{ $quotation->currency }}
                        </strong>
                    </td>
                </tr>
            </table>

            <p style="text-align: center; margin-top: 30px;">
                <a href="{{ $publicUrl }}" class="button">
                    عرض التفاصيل الكاملة
                </a>
            </p>

            <p>التفاصيل الكاملة مرفقة بملف PDF.</p>

            <p>للاستفسار أو المتابعة، تواصل معنا:</p>
            <ul>
                @foreach(settings('contact.phones', []) as $phone)
                    <li dir="ltr">{{ $phone }}</li>
                @endforeach
                <li>{{ settings('contact.email') }}</li>
            </ul>

            <p>نشكر لكم ثقتكم.</p>
        </div>

        <div class="footer">
            <p>{{ settings('company.name_ar') }} - {{ settings('contact.address_ar') }}</p>
            <p>{{ settings('contact.website') }}</p>
        </div>
    </div>
</body>
</html>
