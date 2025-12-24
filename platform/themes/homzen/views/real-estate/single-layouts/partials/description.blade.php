@php
    $model = $model ?? $property ?? null;
@endphp

@if ($model->content || ($model->private_notes ?? null))
    <div @class(['single-property-desc', $class ?? null])>
        @if($model->content)
            <div class="h7 title fw-7">{{ __('Description') }}</div>
            <div class="body-2 text-variant-1">
                <div class="ck-content single-detail">
                    {!! BaseHelper::clean($model->content) !!}
                </div>
            </div>
        @endif

        @if(($model->can_see_private_notes ?? false) && ($model->private_notes ?? null))
            <div class="bd-callout bd-callout-info">
                <div class="h7 title fw-7 mb-2">{{ __('Private Notes') }}</div>

                {!! BaseHelper::clean(nl2br($model->private_notes)) !!}
            </div>
        @endif
    </div>
@endif

<div @class(['single-property-overview', $class ?? null])>
    <div class="h7 title fw-7">{{ __('Overview') }}</div>
    <div class="row row-cols-sm-2 row-cols-lg-3 g-3 g-lg-4 info-box">
        <div class="col item">
            <div class="box-icon w-52">
                <x-core::icon name="ti ti-home" />
            </div>
            <div class="content">
                <span class="label">
                    @if($model instanceof \Botble\RealEstate\Models\Project)
                        {{ __('Project ID:') }}
                    @else
                        {{ __('Property ID:') }}
                    @endif
                </span>
                <span>{{ $model->unique_id ?: $model->getKey() }}</span>
            </div>
        </div>
        @if ($model->categories->isNotEmpty())
            <div class="col item">
                <div class="box-icon w-52">
                    <x-core::icon name="ti ti-category" />
                </div>
                <div class="content">
                    <span class="label">{{ __('Type:') }}</span>
                    <span>
                        @foreach ($model->categories as $category)
                            <a href="{{ $category->url }}">{!! BaseHelper::clean($category->name) !!}</a>@if (!$loop->last),&nbsp;@endif
                        @endforeach
                    </span>
                </div>
            </div>
        @endif
        @if (($model->investor->name ?? null))
            <div class="col item">
                <div class="box-icon w-52">
                    <x-core::icon name="ti ti-category" />
                </div>
                <div class="content">
                    <span class="label">{{ __('Investor:') }}</span>
                    <span>{{ $model->investor->name }}</span>
                </div>
            </div>
        @endif
        @if (($model->number_block ?? null))
            <div class="col item">
                <div class="box-icon w-52">
                    <x-core::icon name="ti ti-packages" />
                </div>
                <div class="content">
                    <span class="label">{{ __('Blocks:') }}</span>
                    <span>{{ number_format($model->number_block) }}</span>
                </div>
            </div>
        @endif
        @if (($model->number_flat ?? null))
            <div class="col item">
                <div class="box-icon w-52">
                    <x-core::icon name="ti ti-building" />
                </div>
                <div class="content">
                    <span class="label">{{ __('Flats:') }}</span>
                    <span>{{ number_format($model->number_flat) }}</span>
                </div>
            </div>
        @endif
        @if (($model->number_bedroom ?? null))
            <div class="col item">
                <div class="box-icon w-52">
                    <x-core::icon name="ti ti-bed" />
                </div>
                <div class="content">
                    <span class="label">{{ __('Bedrooms:') }}</span>
                    <span>{{ number_format($model->number_bedroom) }}</span>
                </div>
            </div>
        @endif
        @if (($model->number_bathroom ?? null))
            <div class="col item">
                <div class="box-icon w-52">
                    <x-core::icon name="ti ti-bath" />
                </div>
                <div class="content">
                    <span class="label">{{ __('Bathrooms:') }}</span>
                    <span>{{ number_format($model->number_bathroom) }}</span>
                </div>
            </div>
        @endif
        @if (($model->number_floor ?? null))
            <div class="col item">
                <div class="box-icon w-52">
                    <x-core::icon name="ti ti-stairs" />
                </div>
                <div class="content">
                    <span class="label">{{ __('Floors:') }}</span>
                    <span>{{ number_format($model->number_floor) }}</span>
                </div>
            </div>
        @endif
        @if (($model->square ?? null))
            <div class="col item">
                <div class="box-icon w-52">
                    <x-core::icon name="ti ti-ruler-2" />
                </div>
                <div class="content">
                    <span class="label">{{ __('Square:') }}</span>
                    <span>{{ $model->square_text }}</span>
                </div>
            </div>
        @endif
        @if (($model->date_finish ?? null))
            <div class="col item">
                <div class="box-icon w-52">
                    <x-core::icon name="ti ti-calendar-check" />
                </div>
                <div class="content">
                    <span class="label">{{ __('Finish Date:') }}</span>
                    <span>{{ $model->date_finish->format('M d, Y') }}</span>
                </div>
            </div>
        @endif
        @if (($model->date_sell ?? null))
            <div class="col item">
                <div class="box-icon w-52">
                    <x-core::icon name="ti ti-calendar-dollar" />
                </div>
                <div class="content">
                    <span class="label">{{ __('Open Sell Date:') }}</span>
                    <span>{{ $model->date_sell->format('M d, Y') }}</span>
                </div>
            </div>
        @endif
        @foreach ($model->customFields as $customField)
            @continue(! $customField->value)
            <div class="col item">
                <div class="box-icon w-52">
                    <x-core::icon name="ti ti-box" />
                </div>
                <div class="content">
                    <span class="label">{!! BaseHelper::clean($customField->name) !!}:</span>
                    <span>{!! BaseHelper::clean($customField->value) !!}</span>
                </div>
            </div>
        @endforeach
    </div>
</div>
