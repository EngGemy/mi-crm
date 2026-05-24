@php
    $cur = \App\Support\QuotationPdfLabels::currency($quotation->currency);
@endphp
<div style="margin-top:4mm; page-break-inside: avoid;" dir="rtl">
    <table style="width:70%; border:none; margin-inline-start:0; margin-inline-end:auto;">
        <tr style="background:none;">
            <td style="border:none; text-align:right; font-weight:bold; width:55%;">المجموع الفرعي | SUBTOTAL:</td>
            <td style="border:none; text-align:left; font-weight:bold; width:45%;" dir="ltr">
                {{ number_format((float) $quotation->subtotal, 2) }}&nbsp;{{ $cur }}
            </td>
        </tr>
        @if((float) $quotation->discount_amount > 0)
        <tr style="background:none;">
            <td style="border:none; text-align:right;">
                الخصم{{ $quotation->discount_percentage > 0 ? ' (' . number_format($quotation->discount_percentage, 0) . '%)' : '' }}:
            </td>
            <td style="border:none; text-align:left; color:{{ settings('branding.primary_color') }};" dir="ltr">
                − {{ number_format((float) $quotation->discount_amount, 2) }}&nbsp;{{ $cur }}
            </td>
        </tr>
        @endif
        @if((float) $quotation->vat_amount > 0)
        <tr style="background:none;">
            <td style="border:none; text-align:right;">
                ضريبة القيمة المضافة ({{ number_format((float) $quotation->vat_percentage, 0) }}%):
            </td>
            <td style="border:none; text-align:left;" dir="ltr">
                {{ number_format((float) $quotation->vat_amount, 2) }}&nbsp;{{ $cur }}
            </td>
        </tr>
        @endif
        <tr class="total-row" style="background:{{ settings('branding.primary_color') }}; color:white;">
            <td style="background:{{ settings('branding.primary_color') }}; color:white; text-align:right; font-size:12pt;">
                <strong>الإجمالي النهائي | TOTAL:</strong>
            </td>
            <td style="background:{{ settings('branding.primary_color') }}; color:white; text-align:left; font-size:12pt;" dir="ltr">
                <strong>{{ number_format((float) $quotation->total_amount, 2) }}&nbsp;{{ $cur }}</strong>
            </td>
        </tr>
        @if($quotation->total_amount_secondary && $quotation->secondary_currency)
        <tr style="background:none;">
            <td style="border:none; text-align:right; font-size:9pt; color:#666;">
                بالعملة الثانوية ({{ \App\Support\QuotationPdfLabels::currency($quotation->secondary_currency) }}):
            </td>
            <td style="border:none; text-align:left; font-size:9pt; color:#666;" dir="ltr">
                ≈ {{ number_format((float) $quotation->total_amount_secondary, 2) }}&nbsp;{{ \App\Support\QuotationPdfLabels::currency($quotation->secondary_currency) }}
            </td>
        </tr>
        @endif
    </table>

    <div style="margin-top:4mm; padding:3mm; background:#FFF5F5; border:1px solid {{ settings('branding.primary_color') }};">
        <p style="margin:0; font-size:10pt;">
            <strong>ملاحظة:</strong> الأسعار المذكورة أعلاه صالحة لمدة
            <strong>{{ $quotation->validity_period_days }} أيام</strong>
            من تاريخ العرض ({{ $quotation->quotation_date?->format('Y-m-d') }}).
            الأسعار لا تشمل أي رسوم جمركية أو ضرائب محلية إلا إذا ذُكر خلاف ذلك.
        </p>
    </div>
</div>
