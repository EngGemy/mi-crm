<h2 style="font-size: 14pt; border-bottom: 2px solid #1a1a1a; padding-bottom: 3mm; margin-top: 10mm;">نطاق العمل والمواصفات الفنية</h2>

<div style="margin: 5mm 0; text-align: justify;">
    <p style="margin: 2mm 0;">
        <span style="font-weight: bold;">١.</span>
        يلتزم الطرف الأول بتصنيع وتوريد وتركيب للطرف الثاني عدد ({{ $contract->hall_count ?? 1 }})
        عنبر من <strong>{{ $contract->contractType->name ?? 'بطاريات الدواجن الأوتوماتيكية' }}</strong>
        بالمواصفات التالية:
    </p>
</div>

<table style="width: 100%; border-collapse: collapse; margin: 5mm 0; font-size: 11pt;">
    <thead>
        <tr>
            <th style="border: 1px solid #1a1a1a; padding: 6px 10px; text-align: right; background: #f0f0f0; font-weight: bold; width: 30%;">البيان</th>
            <th style="border: 1px solid #1a1a1a; padding: 6px 10px; text-align: right; background: #f0f0f0; font-weight: bold;">القيمة</th>
        </tr>
    </thead>
    <tbody>
        @if($contract->project_name)
            <tr>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px;">اسم المشروع</td>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px;">{{ $contract->project_name }}</td>
            </tr>
        @endif
        @if($contract->project_description)
            <tr>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px;">الوصف</td>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px;">{{ $contract->project_description }}</td>
            </tr>
        @endif
        @if($contract->hall_type)
            <tr>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px;">نوع العنبر</td>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px;">{{ $contract->hall_type }}</td>
            </tr>
        @endif
        @if($contract->hall_length || $contract->hall_width || $contract->hall_height)
            <tr>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px;">أبعاد العنبر</td>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px; direction: ltr; text-align: right;">
                    {{ $contract->hall_length ?? '-' }} × {{ $contract->hall_width ?? '-' }}
                    @if($contract->hall_height) × {{ $contract->hall_height }} @endif
                    متر
                </td>
            </tr>
        @endif
        @if($contract->hall_count > 1)
            <tr>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px;">عدد العنابر</td>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px;">{{ $contract->hall_count }}</td>
            </tr>
        @endif
        @if($contract->cage_count)
            <tr>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px;">عدد الأقفاص</td>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px;">{{ number_format($contract->cage_count) }} قفص</td>
            </tr>
        @endif
        @if($contract->bird_capacity)
            <tr>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px;">السعة الإنتاجية</td>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px;">{{ number_format($contract->bird_capacity) }} طائر</td>
            </tr>
        @endif
        @if($contract->installation_location)
            <tr>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px;">موقع التركيب</td>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px;"><strong>{{ $contract->installation_location }}</strong></td>
            </tr>
        @endif
    </tbody>
</table>
