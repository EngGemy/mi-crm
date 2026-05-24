@foreach($sectionAttachments as $att)
@php $section = $att->section; @endphp
@if(!$section) @continue @endif

<div class="section-box">
    <div class="section-header">
        <h3>{{ $section->title_ar ?: $section->title_en }}</h3>
    </div>

    {{-- Images --}}
    @php
        $sectionImages = $quotation->images
            ->where('section_id', $section->id)
            ->sortBy('sort_order');
        $libImages = collect($section->default_images ?? [])
            ->map(fn($id) => \App\Models\ImageLibrary::find($id))
            ->filter();
    @endphp

    @if($sectionImages->count() > 0)
        <div class="img-grid" style="margin:3mm 0;">
            @foreach($sectionImages->take(4) as $img)
                @php $path = $img->file_path ?: ($img->imageLibrary?->file_path); @endphp
                @if($path)
                    <img src="{{ storage_path('app/public/' . $path) }}" alt="">
                @endif
            @endforeach
        </div>
    @elseif($libImages->count() > 0)
        <div class="img-grid" style="margin:3mm 0;">
            @foreach($libImages->take(4) as $img)
                @if($img && $img->file_path)
                    <img src="{{ storage_path('app/public/' . $img->file_path) }}" alt="">
                @endif
            @endforeach
        </div>
    @endif

    {{-- Content --}}
    <div style="margin-top:2mm;">
        @if($att->content_override_ar)
            {!! nl2br(e($att->content_override_ar)) !!}
        @elseif($section->content_ar)
            {!! nl2br(e($section->content_ar)) !!}
        @elseif($att->content_override_en)
            {!! nl2br(e($att->content_override_en)) !!}
        @elseif($section->content_en)
            {!! nl2br(e($section->content_en)) !!}
        @endif

    </div>
</div>
@endforeach
