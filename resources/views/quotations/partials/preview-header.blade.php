<div class="page-header">
    <div class="page-header-inner">
        <div class="header-logo">
            <div class="logo-badge">إم آي</div>
        </div>
        <div class="header-brand">
            <div class="brand">{{ settings('company.name_ar') }}</div>
            <div class="sub-brand">{{ settings('company.tagline_ar') }}</div>
        </div>
        <div class="header-contact" dir="ltr">
            <strong>اتصل بنا</strong><br>
            @foreach(settings('contact.phones', []) as $phone)
                +{{ ltrim($phone, '+') }}@if(!$loop->last)<br>@endif
            @endforeach
            <br>{{ settings('contact.website') }}
        </div>
    </div>
</div>
