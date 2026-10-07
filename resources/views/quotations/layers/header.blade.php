@php $logo = $images['header'][1] ?? ($images['header'][0] ?? null); @endphp
<table width="100%" cellpadding="0" cellspacing="0" style="border-bottom:0.6pt solid {{ $accent }};">
    <tr>
        <td width="18%" style="padding-bottom:1.5mm;">
            @if ($logo)
                <img src="{{ $logo['path'] }}" width="22mm" height="8mm" />
            @endif
        </td>
        <td width="42%" style="padding-bottom:1.5mm;" class="num">{{ $quote_number }}</td>
        <td width="40%" style="padding-bottom:1.5mm; text-align:right; font-size:9pt;">{{ $client }}</td>
    </tr>
</table>
