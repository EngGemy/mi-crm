@php
    $fmt2 = fn ($n) => number_format((float) $n, 2, '.', ',');
    $sections = $sections ?? [];
@endphp
<div class="p10">
    @foreach ($sections as $section)
        @switch($section['kind'])
            @case('title')
                <div class="title">{{ $section['title'] }}</div>
                @break

            @case('meta')
                <table class="meta">
                    <tr>
                        <td class="right">{{ $section['clientName'] }}</td>
                        <td class="left">
                            CUSTOMER ID : {{ $section['customerId'] }}<br>
                            DATE : {{ $section['date'] }}
                        </td>
                    </tr>
                </table>
                @break

            @case('offer')
                <table class="fin">
                    <thead>
                        <tr>
                            <th style="width:38%">Description</th>
                            <th style="width:14%">Unit</th>
                            <th style="width:18%">Taxed</th>
                            <th style="width:15%">Amount $</th>
                            <th style="width:15%">Amount ج.م</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="desc">{!! nl2br(e($section['description'])) !!}</td>
                            <td class="c">{{ $section['unit'] }}</td>
                            <td class="c">{{ $section['taxed'] }}</td>
                            <td class="amt">{{ $fmt2($section['line']['usd']) }}</td>
                            <td class="amt">{{ $fmt2($section['line']['egp']) }}</td>
                        </tr>
                        @foreach ($section['rows'] as $row)
                            <tr class="{{ $row['key'] === 'total' ? 'grand' : 'sum' }}">
                                <td class="desc" colspan="3">{{ $row['label'] }}</td>
                                <td class="amt">{{ $fmt2($row['usd']) }}</td>
                                <td class="amt">{{ $fmt2($row['egp']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @break

            @case('rate')
                <div class="rate">{{ $section['text'] }} — {{ $section['date'] }}</div>
                @break

            @case('validity')
                <div class="validity">{{ $section['text'] }}</div>
                @break

            @case('includes')
                <div class="includes-title">{{ $section['title'] }}</div>
                <ul class="includes">
                    @foreach ($section['items'] as $item)
                        <li>- {!! $item !!}</li>
                    @endforeach
                </ul>
                @break
        @endswitch
    @endforeach
</div>
