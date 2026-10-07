@php
    $hero = $images['cover'][0] ?? null;
@endphp
<sethtmlpageheader name="layers" value="off" />
<sethtmlpagefooter name="layers" value="off" />
@if ($hero)
    <img src="{{ $hero['path'] }}" width="210mm" height="297mm" />
@endif
<pagebreak margin-left="12mm" margin-right="12mm" margin-top="16mm" margin-bottom="14mm" margin-header="6mm" margin-footer="6mm" />
