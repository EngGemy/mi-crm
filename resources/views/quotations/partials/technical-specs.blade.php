<h2>المواصفات الفنية</h2>

@foreach($technicalSpecs as $spec)
<div class="section-box" style="page-break-inside: avoid;">
    <h4>{{ $spec->title_ar ?: $spec->title_en }}</h4>

    @if(!empty($spec->data) && is_array($spec->data))
        <table class="info-table">
            @foreach($spec->data as $row)
                @if(is_array($row) && isset($row['key']))
                <tr>
                    <td class="label">{{ $row['key'] }}</td>
                    <td class="value">{{ $row['value'] ?? '-' }}</td>
                </tr>
                @elseif(is_array($row))
                <tr>
                    <td class="label">{{ array_values($row)[0] ?? '-' }}</td>
                    <td class="value">{{ array_values($row)[1] ?? '-' }}</td>
                </tr>
                @else
                <tr>
                    <td colspan="2">{{ $row }}</td>
                </tr>
                @endif
            @endforeach
        </table>
    @else
        <p class="text-gray text-small">لا توجد بيانات</p>
    @endif
</div>
@endforeach
