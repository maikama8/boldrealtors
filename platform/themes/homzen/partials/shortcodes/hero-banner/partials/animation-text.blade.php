@php
    $animationText = array_filter(explode(',', ($shortcode->animation_text ?: '')));
    $animationTextColor = $shortcode->animation_text_color ?? null;
@endphp

@if($animationText)
    <span class="tf-text s1 cd-words-wrapper" @if($animationTextColor) style="color: {{ $animationTextColor }} !important;" @endif>
        @foreach($animationText as $text)
            <span @class(['item-text', 'is-hidden' => ! $loop->first, 'is-visible' => $loop->first])>
                {{ $text }}
            </span>
        @endforeach
    </span>
@endif
