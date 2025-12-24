@php
    $model = $model ?? $property ?? null;
@endphp

<div class="header-property-detail">
    <div class="content-top d-flex justify-content-between align-items-center">
        <div class="box-name">
            {!! BaseHelper::clean($model->status_html) !!}
            <h1 class="h4 title">
                {!! BaseHelper::clean($model->name) !!}
            </h1>
        </div>

        @if (!setting('real_estate_hide_price', false) && (($model->price_html ?? null) || ($model->formatted_price ?? null)))
            <div class="box-price d-flex align-items-center">
                <h4>{{ $model->price_html ?? $model->formatted_price }}</h4>
            </div>
        @endif
    </div>
    @include(Theme::getThemeNamespace('views.real-estate.partials.meta'), ['model' => $model])
</div>
