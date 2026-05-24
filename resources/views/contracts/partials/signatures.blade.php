<h2 style="font-size: 14pt; border-bottom: 2px solid #1a1a1a; padding-bottom: 3mm; margin-top: 10mm;">التوقيعات</h2>

<p style="margin: 5mm 0;">
    تم تحرير هذا العقد من نسختين أصليتين، استلم كل طرف نسخة للعمل بمقتضاها.
</p>

<table style="width: 100%; border-collapse: collapse; margin-top: 30mm; page-break-inside: avoid;">
    <tr>
        <td style="text-align: center; width: 50%; vertical-align: top; border: 2px solid #1a1a1a; padding: 5mm;">
            <div style="background: #f0f0f0; padding: 3mm; margin: -5mm -5mm 5mm -5mm; font-weight: bold; font-size: 12pt;">
                الطرف الأول (البائع)
            </div>
            <p style="margin: 2mm 0;"><strong>الاسم:</strong> @setting('company.owner_name_ar')</p>
            <p style="margin: 2mm 0;"><strong>الصفة:</strong> @setting('company.owner_title_ar')</p>
            <div style="border-bottom: 1px solid #1a1a1a; margin: 10mm 0 2mm 0;"></div>
            <p style="margin: 2mm 0;">التوقيع وختم الشركة</p>
            <p style="font-size: 9pt; color: #666; margin: 2mm 0;">التاريخ: ......................</p>
        </td>
        <td style="text-align: center; width: 50%; vertical-align: top; border: 2px solid #1a1a1a; padding: 5mm;">
            <div style="background: #f0f0f0; padding: 3mm; margin: -5mm -5mm 5mm -5mm; font-weight: bold; font-size: 12pt;">
                الطرف الثاني (المشتري)
            </div>
            <p style="margin: 2mm 0;"><strong>الاسم:</strong> {{ $contract->customer->name }}</p>
            <p style="margin: 2mm 0;"><strong>رقم الهوية:</strong> {{ $contract->customer->national_id ?? '-' }}</p>
            <div style="border-bottom: 1px solid #1a1a1a; margin: 10mm 0 2mm 0;"></div>
            <p style="margin: 2mm 0;">التوقيع</p>
            <p style="font-size: 9pt; color: #666; margin: 2mm 0;">التاريخ: ......................</p>
        </td>
    </tr>
</table>

<div style="margin-top: 20mm; text-align: center; padding: 5mm; border: 1px dashed #999; font-size: 10pt; color: #666;">
    حُرر هذا العقد بتاريخ {{ $contract->contract_date?->format('Y-m-d') }}
    في {{ settings('contact.city', 'دمياط') }}
</div>
