<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMediaAreaInputMediaAreaChannelPost;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMediaAreaInputMediaAreaVenue;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMediaAreaMediaAreaChannelPost;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMediaAreaMediaAreaGeoPoint;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMediaAreaMediaAreaStarGift;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMediaAreaMediaAreaSuggestedReaction;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMediaAreaMediaAreaUrl;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMediaAreaMediaAreaVenue;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMediaAreaMediaAreaWeather;

/** Anchor model for TL type MediaAreaCoordinates (spec §4.1). */
final class TlMediaAreaCoordinates extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_media_area_coordinates_media_area_coordinates';

    protected $guarded = [];

    public function coordinates(): HasMany
    {
        return $this->hasMany(TlMediaAreaMediaAreaVenue::class, 'coordinates');
    }
    public function coordinatesInputMediaAreaChannelPost(): HasMany
    {
        return $this->hasMany(TlMediaAreaInputMediaAreaChannelPost::class, 'coordinates');
    }
    public function coordinatesInputMediaAreaVenue(): HasMany
    {
        return $this->hasMany(TlMediaAreaInputMediaAreaVenue::class, 'coordinates');
    }
    public function coordinatesMediaAreaChannelPost(): HasMany
    {
        return $this->hasMany(TlMediaAreaMediaAreaChannelPost::class, 'coordinates');
    }
    public function coordinatesMediaAreaGeoPoint(): HasMany
    {
        return $this->hasMany(TlMediaAreaMediaAreaGeoPoint::class, 'coordinates');
    }
    public function coordinatesMediaAreaStarGift(): HasMany
    {
        return $this->hasMany(TlMediaAreaMediaAreaStarGift::class, 'coordinates');
    }
    public function coordinatesMediaAreaSuggestedReaction(): HasMany
    {
        return $this->hasMany(TlMediaAreaMediaAreaSuggestedReaction::class, 'coordinates');
    }
    public function coordinatesMediaAreaUrl(): HasMany
    {
        return $this->hasMany(TlMediaAreaMediaAreaUrl::class, 'coordinates');
    }
    public function coordinatesMediaAreaWeather(): HasMany
    {
        return $this->hasMany(TlMediaAreaMediaAreaWeather::class, 'coordinates');
    }
}
