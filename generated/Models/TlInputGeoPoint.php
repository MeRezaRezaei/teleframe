<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineMessageInputBotInlineMessageMediaGeo;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineMessageInputBotInlineMessageMediaVenue;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaGeoLive;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaGeoPoint;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaVenue;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputWebFileLocationInputWebFileGeoPointLocation;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockInputPageBlockMap;

/** Anchor model for TL type InputGeoPoint (spec §4.1). */
final class TlInputGeoPoint extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_geo_point_input_geo_point';

    protected $guarded = [];

    public function geo(): HasMany
    {
        return $this->hasMany(TlPageBlockInputPageBlockMap::class, 'geo');
    }
    public function geoPoint(): HasMany
    {
        return $this->hasMany(TlInputMediaInputMediaGeoPoint::class, 'geo_point');
    }
    public function geoPointInputBotInlineMessageMediaGeo(): HasMany
    {
        return $this->hasMany(TlInputBotInlineMessageInputBotInlineMessageMediaGeo::class, 'geo_point');
    }
    public function geoPointInputBotInlineMessageMediaVenue(): HasMany
    {
        return $this->hasMany(TlInputBotInlineMessageInputBotInlineMessageMediaVenue::class, 'geo_point');
    }
    public function geoPointInputMediaGeoLive(): HasMany
    {
        return $this->hasMany(TlInputMediaInputMediaGeoLive::class, 'geo_point');
    }
    public function geoPointInputMediaVenue(): HasMany
    {
        return $this->hasMany(TlInputMediaInputMediaVenue::class, 'geo_point');
    }
    public function geoPointInputWebFileGeoPointLocation(): HasMany
    {
        return $this->hasMany(TlInputWebFileLocationInputWebFileGeoPointLocation::class, 'geo_point');
    }
}
