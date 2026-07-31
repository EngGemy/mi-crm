<h2 style="font-size: 14pt; border-bottom: 2px solid {{ settings('branding.primary_color', '#C00000') }}; padding-bottom: 3mm; margin-top: 10mm; color: {{ settings('branding.primary_color', '#C00000') }};">التوقيعات والختم المعتمد</h2>

<p style="margin: 4mm 0; font-size: 10pt; color: #444;">
    تم تحرير هذا العقد من نسختين أصليتين، استلم كل طرف نسخة للعمل بمقتضاها.
    ويُعد الختم أدناه جزءاً أصيلاً من اعتماد الطرف الأول.
</p>

<table style="width: 100%; border-collapse: collapse; margin-top: 8mm; page-break-inside: avoid;">
    <tr>
        <td style="text-align: center; width: 50%; vertical-align: top; border: 1.5pt solid {{ settings('branding.primary_color', '#C00000') }}; padding: 5mm; background: #fffafa;">
            <div style="background: {{ settings('branding.primary_color', '#C00000') }}; color: #fff; padding: 2.5mm; margin: 0 0 4mm 0; font-weight: bold; font-size: 11pt;">
                الطرف الأول (البائع)
            </div>
            <p style="margin: 2mm 0; font-size: 10pt;"><strong>الاسم:</strong> @setting('company.owner_name_ar')</p>
            <p style="margin: 2mm 0 4mm 0; font-size: 10pt;"><strong>الصفة:</strong> @setting('company.owner_title_ar')</p>

            @include('components.company-seal', [
                'contract' => $contract,
                'sealId' => $contract->contract_number ?? $contract->project_code ?? 'إم آي',
                'sealDate' => $contract->contract_date?->format('Y') ?? now()->format('Y'),
                'sealColor' => '#1e40af',
            ])

            <div style="border-bottom: 1px solid #999; margin: 8mm 8mm 2mm 8mm;"></div>
            <p style="margin: 2mm 0; font-size: 9pt; color: #555;">التوقيع اليدوي (إن وُجد)</p>
            <p style="font-size: 8.5pt; color: #888; margin: 2mm 0;">التاريخ: ......................</p>
        </td>
        <td style="width: 2%; border: none;"></td>
        <td style="text-align: center; width: 48%; vertical-align: top; border: 1.5pt solid #333; padding: 5mm;">
            <div style="background: #222; color: #fff; padding: 2.5mm; margin: 0 0 4mm 0; font-weight: bold; font-size: 11pt;">
                الطرف الثاني (المشتري)
            </div>
            <p style="margin: 2mm 0; font-size: 10pt;"><strong>الاسم:</strong> {{ $contract->customer->name }}</p>
            <p style="margin: 2mm 0; font-size: 10pt;"><strong>رقم الهوية:</strong> {{ $contract->customer->national_id ?? '-' }}</p>

            <div style="height: 42mm; margin: 6mm auto 0 auto; width: 42mm; border: 1.2pt dashed #bbb; border-radius: 2mm; text-align: center; padding-top: 14mm; color: #999; font-size: 8pt;">
                مكان التوقيع / الختم
            </div>

            <div style="border-bottom: 1px solid #999; margin: 8mm 8mm 2mm 8mm;"></div>
            <p style="margin: 2mm 0; font-size: 9pt; color: #555;">التوقيع</p>
            <p style="font-size: 8.5pt; color: #888; margin: 2mm 0;">التاريخ: ......................</p>
        </td>
    </tr>
</table>

<div style="margin-top: 12mm; text-align: center; padding: 4mm; border: 1px dashed {{ settings('branding.primary_color', '#C00000') }}; font-size: 9.5pt; color: #555; background: #fffafa;">
    حُرر هذا العقد بتاريخ <strong>{{ $contract->contract_date?->format('Y-m-d') }}</strong>
    في <strong>{{ settings('contact.city', 'دمياط') }}</strong>
    — ومعتمد بختم الشركة أعلاه
</div>
