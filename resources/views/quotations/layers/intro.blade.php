<table width="100%" cellpadding="0" cellspacing="0" class="card" style="margin-bottom:8mm;">
    <tr>
        @foreach ($figures as $figure)
            <td width="25%" style="padding:3mm 2mm; border-top:1.2pt solid {{ $accent }}; border-bottom:0.4pt solid #E5E5E5;">
                <div class="big num">{{ $figure['value'] }}</div>
                <div class="muted">{{ $figure['label'] }}</div>
            </td>
        @endforeach
    </tr>
</table>

@foreach ($blocks as $block)
    <div class="card" style="margin-bottom:6mm;">
        <table width="100%" cellpadding="0" cellspacing="0" class="rule">
            <tr>
                <td width="12%" class="idx" style="padding-bottom:1mm;">{{ $block['index'] }}</td>
                <td width="58%" class="title" style="padding-bottom:1mm;">{{ $block['title'] }}</td>
                <td width="30%" class="en" style="padding-bottom:1mm; text-align:left; direction:ltr;">{{ $block['title_en'] }}</td>
            </tr>
        </table>
        @php $shots = $block['images'] ? ($images[$block['images']] ?? []) : []; @endphp
        @if ($shots !== [])
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:2mm;">
                @foreach (array_chunk($shots, 2) as $pair)
                    <tr>
                        @foreach ($pair as $shot)
                            <td width="50%" style="padding:1mm 1mm 2mm 1mm;">
                                <img src="{{ $shot['path'] }}" width="{{ $shot['w'] }}mm" height="{{ $shot['h'] }}mm" />
                            </td>
                        @endforeach
                        @if (count($pair) === 1)
                            <td width="50%"></td>
                        @endif
                    </tr>
                @endforeach
            </table>
        @endif
        @foreach ($block['paragraphs'] as $paragraph)
            <div style="margin-top:1.5mm;">{{ $paragraph }}</div>
        @endforeach
    </div>
@endforeach
