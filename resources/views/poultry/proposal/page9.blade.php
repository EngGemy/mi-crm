@php
    $sections = $sections ?? [];
    $hasCare = collect($sections)->contains('kind', 'care');
@endphp
<div class="p9">
    @foreach ($sections as $section)
        <table>
            <tr class="bar {{ !empty($section['highlight']) ? 'bar-sel' : '' }}">
                <td colspan="2">{{ $section['title'] }}</td>
            </tr>
            @foreach ($section['rows'] as $i => $row)
                <tr class="r {{ $i % 2 === 0 ? 'odd' : 'even' }} {{ !empty($section['highlight']) ? 'sel-row' : '' }}">
                    <td class="lbl">{{ $row['label'] }}</td>
                    <td class="val">{{ $row['value'] }}</td>
                </tr>
            @endforeach
        </table>
    @endforeach

    @unless ($hasCare)
        <p class="note">لا تتوفر سيناريوهات وزن لهذا العرض.</p>
    @endunless
</div>
