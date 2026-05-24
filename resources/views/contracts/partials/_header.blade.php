<htmlpageheader name="contract_header">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 25%; vertical-align: middle;">
                @php
                    $logoPath = public_path(settings('branding.logo_main', 'images/brand/logo.png'));
                @endphp
                @if(file_exists($logoPath))
                    <img src="{{ $logoPath }}" style="height: 50px;">
                @endif
            </td>
            <td style="width: 50%; text-align: center; vertical-align: middle;">
                <div style="font-size: 14pt; font-weight: bold;">
                    {{ settings('company.name_ar', 'إم آي للصناعات المعدنية') }}
                </div>
                <div style="font-size: 10pt; color: #666;">
                    {{ settings('company.tagline_ar', 'بطاريات الدواجن الأوتوماتيك') }}
                </div>
            </td>
            <td style="width: 25%; text-align: left; vertical-align: middle; font-size: 9pt; direction: ltr;">
                <div>رقم: {{ $contract->contract_number ?? $contract->project_code }}</div>
                <div>التاريخ: {{ $contract->contract_date?->format('Y-m-d') }}</div>
            </td>
        </tr>
    </table>
    <hr style="border-top: 2px solid #1a1a1a; margin-top: 5mm;">
</htmlpageheader>
