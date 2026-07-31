{{-- ختم شركة — أزرق بالكامل وبالعربي --}}
@php
    $company = settings('company.name_ar', 'إم آي للصناعات المعدنية');
    $tagline = settings('company.tagline_ar', 'أنظمة تربية الدواجن');
    $sealColor = $sealColor ?? '#1e40af';
    $sealId = $sealId ?? (isset($contract) ? ($contract->contract_number ?? $contract->project_code ?? 'إم آي') : (isset($q) ? $q->quote_number : 'إم آي'));
    $sealDate = $sealDate ?? now()->format('Y');
@endphp

<div style="width:48mm; margin:0 auto; text-align:center; page-break-inside:avoid;">
    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="border:2.8pt double {{ $sealColor }}; padding:1.5mm; background:#fff;">
                <table style="width:100%; border-collapse:collapse;">
                    <tr>
                        <td style="border:1pt solid {{ $sealColor }}; padding:3.5mm 2.5mm; text-align:center; background:#f0f5ff;">
                            <div style="font-size:7pt; color:{{ $sealColor }}; font-weight:bold; margin-bottom:2mm;">
                                ★ ختم رسمي معتمد ★
                            </div>

                            <table style="margin:0 auto 2mm auto; border-collapse:collapse;">
                                <tr>
                                    <td style="width:14mm; height:12mm; background:{{ $sealColor }}; color:#fff; font-size:8pt; font-weight:bold; text-align:center; vertical-align:middle;">
                                        إم آي
                                    </td>
                                </tr>
                            </table>

                            <div style="font-size:8.5pt; font-weight:bold; color:#111; line-height:1.35; margin-bottom:1mm;">
                                {{ $company }}
                            </div>
                            <div style="font-size:6.5pt; color:#555; margin-bottom:2mm;">
                                {{ $tagline }}
                            </div>

                            <div style="border-top:0.9pt solid {{ $sealColor }}; border-bottom:0.9pt solid {{ $sealColor }}; padding:1.8mm 0; margin:1.5mm 0;">
                                <div style="font-size:11pt; font-weight:bold; color:{{ $sealColor }};">ختم الشركة</div>
                                <div style="font-size:7pt; color:#444; margin-top:0.5mm;">معتمد للتوقيع الرسمي</div>
                            </div>

                            <div style="font-size:6.5pt; color:#666; margin-top:1.5mm;">
                                {{ $sealId }} — {{ $sealDate }}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
