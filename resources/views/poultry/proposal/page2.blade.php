@php
    $sections = $sections ?? [];
@endphp
<div class="p2">
    @foreach ($sections as $section)
        @switch($section['kind'])
            @case('title')
                <div class="title">{{ $section['title'] }}</div>
                @break

            @case('intro')
                <div class="intro">{{ $section['text'] }}</div>
                @break

            @case('barn')
                <table>
                    <tr class="bar">
                        <td colspan="2">{{ $section['title'] }}</td>
                    </tr>
                    @foreach ($section['rows'] as $i => $row)
                        <tr class="r {{ $i % 2 === 0 ? 'odd' : 'even' }}">
                            <td class="lbl">{{ $row['label'] }}</td>
                            <td class="val">{{ $row['value'] }}</td>
                        </tr>
                    @endforeach
                </table>
                @break
        @endswitch
    @endforeach
</div>
