<?php

namespace Botble\RealEstate\Models;

use Botble\Base\Casts\SafeContent;
use Botble\Base\Facades\Html;
use Botble\Base\Models\BaseModel;
use Botble\Location\Models\City;
use Botble\Location\Models\Country;
use Botble\Location\Models\State;
use Botble\Media\Facades\RvMedia;
use Botble\RealEstate\Enums\ModerationStatusEnum;
use Botble\RealEstate\Enums\PropertyPeriodEnum;
use Botble\RealEstate\Enums\PropertyStatusEnum;
use Botble\RealEstate\Enums\PropertyTypeEnum;
use Botble\RealEstate\Models\Traits\UniqueId;
use Botble\RealEstate\QueryBuilders\PropertyBuilder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @method static \Botble\RealEstate\QueryBuilders\PropertyBuilder<static> query()
 */
class Property extends BaseModel
{
    use UniqueId;

    protected $table = 're_properties';

    protected $fillable = [
        'name',
        'type',
        'description',
        'content',
        'location',
        'images',
        'project_id',
        'number_bedroom',
        'number_bathroom',
        'number_floor',
        'square',
        'price',
        'status',
        'is_featured',
        'featured_priority',
        'currency_id',
        'city_id',
        'state_id',
        'country_id',
        'period',
        'author_id',
        'author_type',
        'expire_date',
        'auto_renew',
        'latitude',
        'longitude',
        'zip_code',
        'unique_id',
        'private_notes',
        'floor_plans',
        'reject_reason',
    ];

    protected $casts = [
        'status' => PropertyStatusEnum::class,
        'moderation_status' => ModerationStatusEnum::class,
        'type' => PropertyTypeEnum::class,
        'period' => PropertyPeriodEnum::class,
        'name' => SafeContent::class,
        'description' => SafeContent::class,
        'location' => SafeContent::class,
        'private_notes' => SafeContent::class,
        'expire_date' => 'datetime',
        'images' => 'json',
        'price' => 'float',
        'square' => 'float',
        'number_bedroom' => 'int',
        'number_bathroom' => 'int',
        'number_floor' => 'int',
        'featured_priority' => 'int',
        'floor_plans' => 'array',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Property $property): void {
            $property->categories()->detach();
            $property->customFields()->delete();
            $property->reviews()->delete();
            $property->features()->detach();
            $property->facilities()->detach();
            $property->metadata()->delete();
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id')->withDefault();
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 're_property_features', 'property_id', 'feature_id');
    }

    public function facilities(): BelongsToMany
    {
        return $this->morphToMany(Facility::class, 'reference', 're_facilities_distances')->withPivot('distance');
    }

    protected function image(): Attribute
    {
        return Attribute::get(fn () => Arr::first($this->images) ?? null);
    }

    protected function squareText(): Attribute
    {
        return Attribute::get(function () {
            $square = $this->square;

            $unit = setting('real_estate_square_unit', 'm²');

            $squareFormatted = fmod($square, 1) == 0 ? number_format($square) : number_format($square, 2);

            return apply_filters('real_estate_property_square_text', sprintf('%s %s', $squareFormatted, trans($unit)), $square);
        });
    }

    protected function address(): Attribute
    {
        return Attribute::get(fn () => $this->location);
    }

    protected function category(): Attribute
    {
        return Attribute::get(fn () => $this->categories->first() ?: new Category());
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function author(): MorphTo
    {
        return $this->morphTo()->withDefault();
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 're_property_categories');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id')->withDefault();
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id')->withDefault();
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id')->withDefault();
    }

    public function valuation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\Botble\RealEstate\Models\PropertyValuation::class);
    }

    public function history(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\Botble\RealEstate\Models\PropertyHistory::class)->orderBy('event_date', 'desc');
    }

    public function media(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\Botble\RealEstate\Models\PropertyMedia::class)->orderBy('order');
    }

    public function tours(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\Botble\RealEstate\Models\PropertyTour::class);
    }

    public function rentalApplications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\Botble\RealEstate\Models\RentalApplication::class);
    }

    public function shares(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\Botble\RealEstate\Models\PropertyShare::class);
    }

    protected function cityName(): Attribute
    {
        return Attribute::get(function () {
            if (! is_plugin_active('location')) {
                return $this->location;
            }

            return ($this->city->name ? $this->city->name . ', ' : null) . $this->state->name;
        });
    }

    protected function typeHtml(): Attribute
    {
        return Attribute::get(fn () => $this->type->label());
    }

    protected function statusHtml(): Attribute
    {
        return Attribute::get(fn () => $this->status->toHtml());
    }

    protected function categoryName(): Attribute
    {
        return Attribute::get(fn () => $this->category->name);
    }

    protected function imageThumb(): Attribute
    {
        return Attribute::get(fn () => $this->image ? RvMedia::getImageUrl($this->image, 'thumb', false, RvMedia::getDefaultImage()) : null);
    }

    protected function imageSmall(): Attribute
    {
        return Attribute::get(fn () => $this->image ? RvMedia::getImageUrl($this->image, 'small', false, RvMedia::getDefaultImage()) : null);
    }

    protected function priceHtml(): Attribute
    {
        return Attribute::get(function () {
            if (setting('real_estate_hide_price', false)) {
                return '';
            }

            if (! $this->price) {
                return trans('plugins/real-estate::real-estate.contact_for_price');
            }

            $price = $this->price_format;

            if ($this->type == PropertyTypeEnum::RENT) {
                $price .= ' / ' . Str::lower($this->period->shortLabel());
            }

            return $price;
        });
    }

    protected function priceFormat(): Attribute
    {
        return Attribute::get(function () {
            if (setting('real_estate_hide_price', false)) {
                return '';
            }

            if (! $this->price) {
                return trans('plugins/real-estate::real-estate.contact_for_price');
            }

            if ($this->price_formatted) {
                return $this->price_formatted;
            }

            $currency = $this->currency;

            if (! $currency || ! $currency->getKey()) {
                $currency = get_application_currency();
            }

            return $this->price_formatted = format_price($this->price, $currency);
        });
    }

    protected function mapIcon(): Attribute
    {
        return Attribute::get(fn () => $this->type_html . ': ' . $this->price_format);
    }

    public function customFields(): MorphMany
    {
        return $this->morphMany(CustomFieldValue::class, 'reference', 'reference_type', 'reference_id')->with('customField.options');
    }

    protected function customFieldsArray(): Attribute
    {
        return Attribute::get(fn () => CustomFieldValue::getCustomFieldValuesArray($this));
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function newEloquentBuilder($query): PropertyBuilder
    {
        return new PropertyBuilder($query);
    }

    protected function shortAddress(): Attribute
    {
        return Attribute::get(function () {
            if (! is_plugin_active('location')) {
                return $this->location;
            }

            return implode(', ', array_filter([$this->city->name, $this->state->name]));
        });
    }

    protected function formattedFloorPlans(): Attribute
    {
        return Attribute::get(function () {
            $floorPlan = $this->floor_plans;

            if (! is_array($floorPlan)) {
                $floorPlan = json_decode($floorPlan, true);
            }

            return collect($floorPlan)
                ->filter(fn ($floorPlan) => is_array($floorPlan))
                ->map(function ($floorPlan) {
                    $floorPlan = collect($floorPlan)->pluck('value', 'key')->toArray();
                    $bedrooms = (int) Arr::get($floorPlan, 'bedrooms', 0);
                    $bathrooms = (int) Arr::get($floorPlan, 'bathrooms', 0);

                    return [
                        'name' => Arr::get($floorPlan, 'name'),
                        'description' => Arr::get($floorPlan, 'description'),
                        'image' => Arr::get($floorPlan, 'image'),
                        'bedrooms' => $bedrooms === 1 ? trans('plugins/real-estate::property.1_bedroom') : trans('plugins/real-estate::property.bedrooms', ['count' => $bedrooms]),
                        'bathrooms' => $bathrooms === 1 ? trans('plugins/real-estate::property.1_bathroom') : trans('plugins/real-estate::property.bathrooms', ['count' => $bathrooms]),
                    ];
                });
        });
    }

    protected function isPendingModeration(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->exists) {
                return false;
            }

            return ! in_array($this->moderation_status, [ModerationStatusEnum::APPROVED, ModerationStatusEnum::REJECTED]);
        });
    }

    protected function displayedExpireDate(): Attribute
    {
        return Attribute::get(function () {
            if ($this->never_expired) {
                return Html::tag('span', trans('plugins/real-estate::property.never_expired_label'), ['class' => 'text-info'])->toHtml();
            }

            if (! $this->expire_date) {
                return '&mdash;';
            }

            if ($this->expire_date->isPast()) {
                return Html::tag('span', $this->expire_date->toDateString(), ['class' => 'text-danger'])->toHtml();
            }

            if (Carbon::now()->diffInDays($this->expire_date) < 3) {
                return Html::tag('span', $this->expire_date->toDateString(), ['class' => 'text-warning'])->toHtml();
            }

            return $this->expire_date->toDateString();
        });
    }
}
