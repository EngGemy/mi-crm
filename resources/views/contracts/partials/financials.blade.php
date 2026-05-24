<h2 style="font-size: 14pt; border-bottom: 2px solid #1a1a1a; padding-bottom: 3mm; margin-top: 10mm;">القيمة المالية وطريقة الدفع</h2>

<table style="width: 100%; border-collapse: collapse; margin: 5mm 0; font-size: 11pt;">
    <thead>
        <tr>
            <th style="width: 60%; border: 1px solid #1a1a1a; padding: 6px 10px; text-align: right; background: #f0f0f0; font-weight: bold;">البيان</th>
            <th style="border: 1px solid #1a1a1a; padding: 6px 10px; text-align: left; direction: ltr; background: #f0f0f0; font-weight: bold;">القيمة ({{ $contract->currency ?? 'EGP' }})</th>
        </tr>
    </thead>
    <tbody>
        @if($contract->cages_cost > 0)
            <tr>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px;">تكلفة البطاريات</td>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px; text-align: left; direction: ltr;">{{ number_format((float) $contract->cages_cost, 2) }}</td>
            </tr>
        @endif
        @if($contract->construction_cost > 0)
            <tr>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px;">تكلفة الإنشاءات</td>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px; text-align: left; direction: ltr;">{{ number_format((float) $contract->construction_cost, 2) }}</td>
            </tr>
        @endif
        @if($contract->electricity_cost > 0)
            <tr>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px;">تكلفة الكهرباء</td>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px; text-align: left; direction: ltr;">{{ number_format((float) $contract->electricity_cost, 2) }}</td>
            </tr>
        @endif
        @if($contract->plumbing_cost > 0)
            <tr>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px;">تكلفة السباكة</td>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px; text-align: left; direction: ltr;">{{ number_format((float) $contract->plumbing_cost, 2) }}</td>
            </tr>
        @endif
        @if($contract->accessories_cost > 0)
            <tr>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px;">تكلفة المشتملات</td>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px; text-align: left; direction: ltr;">{{ number_format((float) $contract->accessories_cost, 2) }}</td>
            </tr>
        @endif
        @if($contract->subtotal)
            <tr>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px;">المجموع قبل الضريبة</td>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px; text-align: left; direction: ltr;">{{ number_format((float) $contract->subtotal, 2) }}</td>
            </tr>
        @endif
        @if($contract->discount_amount > 0)
            <tr>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px;">الخصم</td>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px; text-align: left; direction: ltr;">({{ number_format((float) $contract->discount_amount, 2) }})</td>
            </tr>
        @endif
        @if($contract->vat_amount > 0)
            <tr>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px;">ضريبة القيمة المضافة ({{ $contract->vat_percentage }}%)</td>
                <td style="border: 1px solid #1a1a1a; padding: 6px 10px; text-align: left; direction: ltr;">{{ number_format((float) $contract->vat_amount, 2) }}</td>
            </tr>
        @endif
        <tr style="background: #f0f0f0; font-weight: bold; font-size: 13pt;">
            <td style="border: 1px solid #1a1a1a; padding: 6px 10px; font-weight: bold;">إجمالي قيمة العقد</td>
            <td style="border: 1px solid #1a1a1a; padding: 6px 10px; text-align: left; direction: ltr; font-weight: bold;">{{ number_format((float) $contract->total_value, 2) }}</td>
        </tr>
    </tbody>
</table>

<p style="margin: 5mm 0; padding: 5mm; background: #f9f9f9; border-right: 4px solid #1a1a1a;">
    <strong>القيمة بالحروف:</strong>
    {{ app(\App\Services\ClauseRenderer::class)->numberToArabicWords((float) $contract->total_value) }}
    {{ $contract->currency === 'EGP' ? 'جنيهاً مصرياً' : ($contract->currency === 'USD' ? 'دولاراً أمريكياً' : '') }}
    لا غير.
</p>

@if($contract->payments && $contract->payments->count() > 0)
    <h3 style="font-size: 13pt; margin: 5mm 0 3mm 0;">جدول الدفعات</h3>
    <table style="width: 100%; border-collapse: collapse; margin: 5mm 0; font-size: 11pt;">
        <thead>
            <tr>
                <th style="width: 8%; border: 1px solid #1a1a1a; padding: 6px 10px; text-align: center; background: #f0f0f0; font-weight: bold;">م</th>
                <th style="width: 25%; border: 1px solid #1a1a1a; padding: 6px 10px; text-align: right; background: #f0f0f0; font-weight: bold;">البيان</th>
                <th style="width: 15%; border: 1px solid #1a1a1a; padding: 6px 10px; text-align: center; background: #f0f0f0; font-weight: bold;">النسبة</th>
                <th style="width: 22%; border: 1px solid #1a1a1a; padding: 6px 10px; text-align: center; background: #f0f0f0; font-weight: bold;">القيمة</th>
                <th style="width: 30%; border: 1px solid #1a1a1a; padding: 6px 10px; text-align: right; background: #f0f0f0; font-weight: bold;">الاستحقاق</th>
            </tr>
        </thead>
        <tbody>
            @foreach($contract->payments->sortBy('sort_order') as $idx => $payment)
                <tr>
                    <td style="border: 1px solid #1a1a1a; padding: 6px 10px; text-align: center;">{{ $idx + 1 }}</td>
                    <td style="border: 1px solid #1a1a1a; padding: 6px 10px;">{{ $payment->description ?? 'دفعة' }}</td>
                    <td style="border: 1px solid #1a1a1a; padding: 6px 10px; text-align: center;">
                        {{ number_format((float) $payment->percentage, 2) }}%
                    </td>
                    <td style="border: 1px solid #1a1a1a; padding: 6px 10px; text-align: center; direction: ltr; font-weight: bold;">
                        {{ number_format((float) $payment->expected_amount, 2) }}
                    </td>
                    <td style="border: 1px solid #1a1a1a; padding: 6px 10px;">{{ $payment->trigger_description ?? ($payment->due_date?->format('Y-m-d') ?? '-') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<h3 style="font-size: 13pt; margin: 5mm 0 3mm 0;">وسيلة الدفع</h3>
@php
    $bank = settings()->defaultBankAccount();
@endphp
<p style="margin: 2mm 0;">
    يتم سداد الدفعات عن طريق تحويل بنكي لحساب الطرف الأول:<br>
    <strong>البنك:</strong> {{ $bank?->bank_name_ar ?? '—' }}<br>
    <strong>رقم الحساب:</strong> {{ $bank?->account_number ?? '—' }}<br>
    <strong>اسم الحساب:</strong> {{ $bank?->account_name_en ?? '' }} @if($bank?->account_name_ar) ({{ $bank->account_name_ar }}) @endif
</p>

<h3 style="font-size: 13pt; margin: 5mm 0 3mm 0;">غرامات التأخير والشرط الجزائي</h3>
<p style="margin: 2mm 0; text-align: justify;">
    في حال تأخر الطرف الثاني عن سداد أي دفعة في موعدها، يحق للطرف الأول تأخير موعد التسليم
    بنفس فترة التأخير دون أي مسؤولية. وفي حالة الإخلال الجسيم بأي بند من بنود هذا العقد،
    يلتزم الطرف المخل بشرط جزائي قدره <strong>({{ number_format((float) settings('defaults.penalty_amount_per_day', 10000), 0) }}) جنيه مصري</strong> عن كل يوم تأخير.
</p>
