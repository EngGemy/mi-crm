{{-- ختم شركة مميز — متوافق مع mPDF --}}
@php
    $company = settings('company.name_ar', 'إم آي للصناعات المعدنية');
    $tagline = settings('company.tagline_ar', 'أنظمة تربية الدواجن');
    $primary = settings('branding.primary_color', '#C00000');
    $sealId = $sealId ?? (isset($contract) ? ($contract->contract_number ?? $contract->project_code ?? 'MI') : (isset($q) ? $q->quote_number : 'MI'));
    $sealDate = $sealDate ?? now()->format('Y');
@endphp

<div style="width:48mm; margin:0 auto; text-align:center; page-break-inside:avoid;">
    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="border:2.8pt double {{ $primary }}; padding:1.5mm; background:#fff;">
                <table style="width:100%; border-collapse:collapse;">
                    <tr>
                        <td style="border:1pt solid {{ $primary }}; padding:3.5mm 2.5mm; text-align:center; background:#fffafa;">
                            <div style="font-size:6.5pt; color:{{ $primary }}; font-weight:bold; letter-spacing:0.8pt; margin-bottom:2mm;">
                                ★ OFFICIAL SEAL ★
                            </div>

                            <table style="margin:0 auto 2mm auto; border-collapse:collapse;">
                                <tr>
                                    <td style="width:12mm; height:12mm; background:{{ $primary }}; color:#fff; font-size:9pt; font-weight:bold; text-align:center; vertical-align:middle;">
                                        MI
                                    </td>
                                </tr>
                            </table>

                            <div style="font-size:8.5pt; font-weight:bold; color:#111; line-height:1.35; margin-bottom:1mm;">
                                {{ $company }}
                            </div>
                            <div style="font-size:6.5pt; color:#666; margin-bottom:2mm;">
                                {{ $tagline }}
                            </div>

                            <div style="border-top:0.9pt solid {{ $primary }}; border-bottom:0.9pt solid {{ $primary }}; padding:1.8mm 0; margin:1.5mm 0;">
                                <div style="font-size:11pt; font-weight:bold; color:{{ $primary }};">ختم معتمد</div>
                                <div style="font-size:6.5pt; color:#555; margin-top:0.5mm;">AUTHORIZED STAMP</div>
                            </div>

                            <div style="font-size:6pt; color:#888; direction:ltr; margin-top:1.5mm;">
                                {{ $sealId }} · {{ $sealDate }}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
