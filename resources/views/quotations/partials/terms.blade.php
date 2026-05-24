<h2>البنود والشروط</h2>

@foreach($renderedTerms as $row)
<div class="term-box">
    <h4>{{ $row['term']->title_ar ?: $row['term']->title_en }}</h4>
    <div style="font-size:10.5pt; line-height:1.7;">
        {!! $row['rendered_content'] !!}
    </div>
</div>
@endforeach

@if(count($renderedTerms) === 0)
<div class="term-box">
    <p class="text-gray text-center" style="text-align:center;">لا توجد بنود محددة</p>
</div>
@endif
