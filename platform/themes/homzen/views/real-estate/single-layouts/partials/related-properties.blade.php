@php
    $model = $model ?? $property ?? null;
    $isProject = $model instanceof \Botble\RealEstate\Models\Project;

    if ($isProject) {
        // For projects, show related properties in the project
        $relatedProperties = app(\Botble\RealEstate\Repositories\Interfaces\PropertyInterface::class)
            ->getPropertiesByConditions(
                [
                    're_properties.project_id' => $model->getKey(),
                ],
                theme_option('number_of_related_properties', 8),
                \Botble\RealEstate\Facades\RealEstateHelper::getPropertyRelationsQuery(),
            );
    } else {
        // For properties, show related properties
        $relatedProperties = app(\Botble\RealEstate\Repositories\Interfaces\PropertyInterface::class)
            ->getRelatedProperties(
                $model->id,
                theme_option('number_of_related_properties', 8),
                \Botble\RealEstate\Facades\RealEstateHelper::getPropertyRelationsQuery()
            );
    }
@endphp

@if ($relatedProperties->isNotEmpty())
    <section class="flat-section pt-0 flat-latest-property">
        <div class="container">
            <div class="box-title">
                <div class="text-subtitle text-primary">{{ __('Latest Properties') }}</div>
                <h2 class="section-title mt-4">
                    @if ($isProject)
                        {{ __('Properties in project ":name"', ['name' => $model->name]) }}
                    @else
                        {{ __('The Most Recent Estate') }}
                    @endif
                </h2>
            </div>
            <div class="swiper tf-latest-property" data-preview-lg="3" data-preview-md="2" data-preview-sm="2" data-space="30" data-loop="true">
                <div class="swiper-wrapper">
                    @foreach($relatedProperties as $property)
                        <div class="swiper-slide">
                            @include(Theme::getThemeNamespace('views.real-estate.properties.item-grid'), ['property' => $property, 'class' => 'style-2'])
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif
