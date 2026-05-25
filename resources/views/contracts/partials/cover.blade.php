<div style="text-align: center; padding: 15mm 0;">
    <h1 style="font-size: 28pt; margin-bottom: 8mm; color: #1a1a1a; border: none;">
        عقد توريد وتركيب
    </h1>

    <h2 style="font-size: 18pt; border: none; margin: 8mm 0; color: #1a1a1a;">
        {{ $contract->contractType->name ?? 'مشروع دواجن' }}
    </h2>

    <div style="border: 2px solid #1a1a1a; padding: 10mm; margin: 10mm auto; max-width: 120mm;">
        <table style="width: 100%; font-size: 12pt; border-collapse: collapse;">
            <tr>
                <td style="padding: 3mm 0; font-weight: bold; width: 40%; border: none;">رقم العقد:</td>
                <td style="padding: 3mm 0; border: none;"><bdi dir="ltr">{{ $contract->contract_number ?? $contract->project_code }}</bdi></td>
            </tr>
            <tr>
                <td style="padding: 3mm 0; font-weight: bold; border: none;">تاريخ التحرير:</td>
                <td style="padding: 3mm 0; border: none;">{{ $contract->contract_date?->format('Y-m-d') }}</td>
            </tr>
            <tr>
                <td style="padding: 3mm 0; font-weight: bold; border: none;">العميل:</td>
                <td style="padding: 3mm 0; border: none;">{{ $contract->customer->name }}</td>
            </tr>
            <tr>
                <td style="padding: 3mm 0; font-weight: bold; border: none;">إجمالي القيمة:</td>
                <td style="padding: 3mm 0; border: none;">
                    <span style="font-weight: bold; font-family: monospace; direction: ltr; display: inline-block;">
                        {{ number_format((float) $contract->total_value, 2) }}
                    </span>
                    {{ $contract->currency ?? 'ج.م' }}
                </td>
            </tr>
        </table>
    </div>
</div>
