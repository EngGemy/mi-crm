<table style="width:100%; border:none; margin:5mm 0;">
    <tr style="background:none;">
        <td style="width:50%; border:none; vertical-align:top; padding:2mm;">
            <div style="background:#FAFAFA; border:1px solid #E0E0E0; padding:4mm; border-radius:3mm;">
                <h3 style="font-size:12pt; color:{{ settings('branding.primary_color') }}; margin:0 0 3mm 0; border-bottom:2px solid {{ settings('branding.primary_color') }}; padding-bottom:2mm;">بيانات عرض السعر</h3>
                <table style="width:100%; border:none; margin:0;">
                    <tr style="background:none;">
                        <td style="border:none; padding:1.5mm 0; font-size:10.5pt; width:45%;"><strong>تاريخ العرض:</strong></td>
                        <td style="border:none; padding:1.5mm 0; font-size:10.5pt;">{{ $quotation->quotation_date?->format('Y-m-d') ?? '—' }}</td>
                    </tr>
                    <tr style="background:none;">
                        <td style="border:none; padding:1.5mm 0; font-size:10.5pt;"><strong>صالح حتى:</strong></td>
                        <td style="border:none; padding:1.5mm 0; font-size:10.5pt;">{{ $quotation->valid_until?->format('Y-m-d') ?? '—' }}</td>
                    </tr>
                    <tr style="background:none;">
                        <td style="border:none; padding:1.5mm 0; font-size:10.5pt;"><strong>رقم العرض:</strong></td>
                        <td style="border:none; padding:1.5mm 0; font-size:10.5pt;" dir="ltr">{{ $quotation->quotation_number }}</td>
                    </tr>
                    <tr style="background:none;">
                        <td style="border:none; padding:1.5mm 0; font-size:10.5pt;"><strong>العميل:</strong></td>
                        <td style="border:none; padding:1.5mm 0; font-size:10.5pt;">{{ $customer->name ?? '—' }}</td>
                    </tr>
                    <tr style="background:none;">
                        <td style="border:none; padding:1.5mm 0; font-size:10.5pt;"><strong>المشروع:</strong></td>
                        <td style="border:none; padding:1.5mm 0; font-size:10.5pt;">{{ $quotation->project_name ?? '—' }}</td>
                    </tr>
                </table>
            </div>
        </td>
        <td style="width:50%; border:none; vertical-align:top; padding:2mm;">
            <div style="background: linear-gradient(135deg, #fafbfc 0%, #f8fafc 100%); border:1px solid #E0E0E0; padding:5mm; border-radius:3mm; text-align:center;">
                <p style="font-size:14pt; font-weight:bold; color:{{ settings('branding.primary_color') }}; margin:0 0 2mm 0;">@setting('company.name_ar')</p>
                <p style="font-size:10pt; color:#666; margin:0 0 3mm 0;">@setting('contact.website')</p>
                <p style="font-size:10pt; color:#333; margin:1mm 0;">@setting('contact.email')</p>
                @foreach(settings('contact.phones', []) as $phone)
                <p style="font-size:10pt; color:#333; margin:1mm 0;" dir="ltr">+{{ ltrim($phone, '+') }}</p>
                @endforeach
            </div>
        </td>
    </tr>
</table>

<div style="text-align:center; font-size:10.5pt; color:#444; margin:3mm 0;">
    <strong>العنوان:</strong> @setting('contact.address_ar')
</div>
