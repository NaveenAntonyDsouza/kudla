@props(['position'])

@php
    $ad = \App\Models\Advertisement::getForSlot($position);
@endphp

@if($ad)
    @php $ad->recordImpression(); @endphp
    <div style="text-align: center; margin: 0.75rem 0;">
        @if($ad->type === 'image' && $ad->image_path)
            <a href="{{ url('/ad/click/' . $ad->id) }}" target="_blank" rel="noopener sponsored">
                <img src="{{ asset('storage/' . $ad->image_path) }}" alt="{{ $ad->title }}" loading="lazy"
                     style="max-width: 100%; height: auto; border-radius: 0.25rem;">
            </a>
        @elseif($ad->type === 'html' && $ad->html_code)
            {!! $ad->html_code !!}
        @endif
    </div>
@endif
