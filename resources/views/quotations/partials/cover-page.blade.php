{{-- صفحة الغلاف --}}
<div class="cover-header-bar">
    <div style="margin-bottom:3mm;">
        <div style="width:18mm; height:18mm; background: linear-gradient(135deg, {{ settings('branding.primary_color') }} 0%, #8C0000 100%); border-radius:4mm; display:inline-flex; align-items:center; justify-content:center; color:#fff; font-size:12pt; font-weight:bold; box-shadow:0 2px 6px rgba(0,0,0,0.2);">
            إم آي
        </div>
    </div>
    <div class="logo-text">@setting('company.name_ar')</div>
    <div class="tagline">@setting('company.tagline_ar')</div>
</div>

<table style="width:100%; border:none; margin:5mm 0;">
    <tr style="background:none;">
        <td style="width:55%; border:none; text-align:center; vertical-align:middle;">
            <p style="font-size:16pt; font-weight:bold; color:#333; margin:0; line-height:1.4;">
                @setting('company.name_ar')<br>
                <span style="font-size:13pt; color:#666;">— @setting('company.tagline_ar')</span>
            </p>
        </td>
        <td style="width:45%; border:none; text-align:center; vertical-align:middle;">
            <p style="font-size:11pt; font-weight:bold; color:#333; margin:0; line-height:1.8; text-transform:uppercase; letter-spacing:1pt;">
                Technical<br>Proposal<br>For<br>Automatic<br>Poultry<br>Cages
            </p>
        </td>
    </tr>
</table>

<div class="img-block" style="margin:4mm 0;">
    @if($coverImage && $coverImage->file_path)
        <img src="{{ storage_path('app/public/' . $coverImage->file_path) }}" style="max-height:65mm; width:auto;" alt="صورة العنبر">
    @else
        <div style="width:100%; height:55mm; background:#F0F0F0; text-align:center; padding-top:22mm; color:#999; font-size:12pt;">
            [صورة العنبر 3D]
        </div>
    @endif
</div>

<div class="text-center" style="margin:3mm 0;">
    <p style="font-size:13pt; font-weight:bold; color:#333; margin:0;">
        عرض مالي وفني لتجهيز عنبر {{ $quotation->hall_type ?? 'تسمين' }}
    </p>
    <p style="font-size:10.5pt; color:#666; margin:1mm 0 0 0;">
        {{ $quotation->project_name }}
    </p>
</div>

<table class="cover-dims-table">
    <tr>
        <td class="dim-label">نوع العنبر</td>
        <td>{{ $quotation->hall_type ?? 'تسمين' }}</td>
    </tr>
    <tr>
        <td class="dim-label">طول العنبر</td>
        <td>{{ $quotation->hall_length ? number_format($quotation->hall_length, 0) . ' متر' : '-' }}</td>
    </tr>
    <tr>
        <td class="dim-label">عرض العنبر</td>
        <td>{{ $quotation->hall_width ? number_format($quotation->hall_width, 0) . ' متر' : '-' }}</td>
    </tr>
    <tr>
        <td class="dim-label">ارتفاع العنبر</td>
        <td>{{ $quotation->hall_height ? number_format($quotation->hall_height, 0) . ' متر' : '-' }}</td>
    </tr>
    @if($quotation->hall_count > 1)
    <tr>
        <td class="dim-label">عدد العنابر</td>
        <td>{{ $quotation->hall_count }}</td>
    </tr>
    @endif
    @if($quotation->bird_capacity)
    <tr>
        <td class="dim-label">السعة</td>
        <td>{{ number_format($quotation->bird_capacity) }} طائر</td>
    </tr>
    @endif
</table>

<div class="text-center" style="margin:3mm 0 5mm 0;">
    <p class="text-small text-gray">تاريخ العرض: {{ $quotation->quotation_date?->format('Y-m-d') }}</p>
    <p class="text-small text-gray">صالح حتى: {{ $quotation->valid_until?->format('Y-m-d') }}</p>
    <p class="text-small text-gray">رقم العرض: {{ $quotation->quotation_number }}</p>
</div>
