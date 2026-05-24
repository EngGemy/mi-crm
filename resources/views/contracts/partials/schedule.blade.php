<h2 style="font-size: 14pt; border-bottom: 2px solid #1a1a1a; padding-bottom: 3mm; margin-top: 10mm;">الجدول الزمني</h2>

<ul style="padding-right: 6mm; margin: 3mm 0;">
    <li style="margin: 1mm 0;"><strong>مدة التصنيع:</strong> ({{ $contract->manufacturing_days ?? settings('defaults.manufacturing_days', 105) }}) يوم عمل من تاريخ سداد الدفعة المقدمة.</li>
    <li style="margin: 1mm 0;"><strong>تاريخ التسليم المتوقع:</strong> {{ $contract->expected_delivery_date?->format('Y/m/d') ?? '-' }}</li>
    <li style="margin: 1mm 0;"><strong>مكان التسليم:</strong> {{ $contract->installation_location }}</li>
    <li style="margin: 1mm 0;">يتحمل الطرف الأول تكلفة الشحن وتبعة الهلاك حتى التسليم النهائي.</li>
</ul>

@if ($contract->milestones && $contract->milestones->count() > 0)
    <h3 style="font-size: 13pt; margin: 5mm 0 3mm 0;">المراحل التفصيلية</h3>
    <table style="width: 100%; border-collapse: collapse; margin: 5mm 0; font-size: 11pt;">
        <thead>
            <tr>
                <th style="border: 1px solid #1a1a1a; padding: 6px 10px; text-align: center; background: #f0f0f0; font-weight: bold;">#</th>
                <th style="border: 1px solid #1a1a1a; padding: 6px 10px; text-align: right; background: #f0f0f0; font-weight: bold;">المرحلة</th>
                <th style="border: 1px solid #1a1a1a; padding: 6px 10px; text-align: center; background: #f0f0f0; font-weight: bold;">التاريخ المتوقع</th>
                <th style="border: 1px solid #1a1a1a; padding: 6px 10px; text-align: center; background: #f0f0f0; font-weight: bold;">الحالة</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($contract->milestones as $idx => $milestone)
                <tr>
                    <td style="border: 1px solid #1a1a1a; padding: 6px 10px; text-align: center;">{{ $idx + 1 }}</td>
                    <td style="border: 1px solid #1a1a1a; padding: 6px 10px;">{{ $milestone->title }}</td>
                    <td style="border: 1px solid #1a1a1a; padding: 6px 10px; text-align: center;">{{ $milestone->expected_date?->format('Y/m/d') }}</td>
                    <td style="border: 1px solid #1a1a1a; padding: 6px 10px; text-align: center;">{{ $milestone->status_label ?? $milestone->status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
