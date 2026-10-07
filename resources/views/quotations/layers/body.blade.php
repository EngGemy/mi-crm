<htmlpageheader name="layers">
    @include('quotations.layers.header')
</htmlpageheader>
<htmlpagefooter name="layers">
    @include('quotations.layers.footer')
</htmlpagefooter>
<sethtmlpageheader name="layers" value="on" show-this-page="1" />
<sethtmlpagefooter name="layers" value="on" show-this-page="1" />

@include('quotations.layers.intro')
@include('quotations.layers.specs')
@include('quotations.layers.financials')
@include('quotations.layers.warranty')
@include('quotations.layers.payments')
@include('quotations.layers.disinfection')
@include('quotations.layers.terms')
