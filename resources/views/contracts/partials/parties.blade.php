<h2 style="font-size: 14pt; border-bottom: 2px solid #1a1a1a; padding-bottom: 3mm; margin-top: 10mm;">الأطراف المتعاقدة</h2>

<p style="margin: 5mm 0;">
    إنه في يوم {{ $contract->contract_date?->translatedFormat('l') }}
    الموافق {{ $contract->contract_date?->format('Y-m-d') }}
    تم الاتفاق بين كل من:
</p>

<table style="width: 100%; border-collapse: collapse; margin: 5mm 0; font-size: 11pt;">
    <tr>
        <td style="vertical-align: top; width: 50%; border: 1px solid #1a1a1a; padding: 6mm;">
            <h3 style="font-size: 13pt; margin: 0 0 3mm 0;">الطرف الأول (البائع/المُصنّع)</h3>
            <p style="margin: 2mm 0;">
                <strong>الاسم:</strong> {{ settings('company.name_ar') }}<br>
                <strong>الممثل:</strong> {{ settings('company.owner_name_ar') }}<br>
                <strong>الصفة:</strong> {{ settings('company.owner_title_ar') }}<br>
                <strong>السجل التجاري:</strong> {{ settings('legal.commercial_register') }}<br>
                <strong>الرقم الضريبي:</strong> {{ settings('legal.tax_number') }}<br>
                <strong>العنوان:</strong> {{ settings('contact.address_ar') }}<br>
                <strong>التليفون:</strong>
                <span dir="ltr">{{ settings('contact.phones', [])[0] ?? '' }}</span>
            </p>
        </td>
        <td style="vertical-align: top; width: 50%; border: 1px solid #1a1a1a; padding: 6mm;">
            <h3 style="font-size: 13pt; margin: 0 0 3mm 0;">الطرف الثاني (المشتري/العميل)</h3>
            <p style="margin: 2mm 0;">
                <strong>الاسم:</strong> {{ $contract->customer->name }}<br>
                @if($contract->customer->national_id)
                    <strong>رقم الهوية:</strong>
                    <span dir="ltr">{{ $contract->customer->national_id }}</span><br>
                @endif
                @if($contract->customer->nationality)
                    <strong>الجنسية:</strong> {{ $contract->customer->nationality }}<br>
                @endif
                <strong>العنوان:</strong> {{ $contract->customer->address ?? '-' }}<br>
                <strong>التليفون:</strong>
                <span dir="ltr">{{ $contract->customer->phone ?? '-' }}</span>
                @if($contract->customer->email)
                    <br><strong>البريد:</strong> {{ $contract->customer->email }}
                @endif
            </p>
        </td>
    </tr>
</table>

<p style="margin: 5mm 0;">
    وقد اتفق الطرفان وهما بكامل الأهلية المعتبرة شرعاً وقانوناً على ما يلي:
</p>
