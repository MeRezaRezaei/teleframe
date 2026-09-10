<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineMessageBotInlineMessageMediaGeo;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineMessageBotInlineMessageMediaVenue;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBusinessLocationBusinessLocation;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelLocationChannelLocation;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMediaAreaMediaAreaGeoPoint;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMediaAreaMediaAreaVenue;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaGeo;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaGeoLive;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaVenue;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockMap;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotInlineQuery;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotInlineSend;

/** Anchor model for TL type GeoPoint (spec §4.1). */
final class TlGeoPoint extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_geo_point_geo_point';

    protected $guarded = [];

    public function geo(): HasMany
    {
        return $this->hasMany(TlMessageMediaMessageMediaGeo::class, 'geo');
    }
    public function geoBotInlineMessageMediaGeo(): HasMany
    {
        return $this->hasMany(TlBotInlineMessageBotInlineMessageMediaGeo::class, 'geo');
    }
    public function geoBotInlineMessageMediaVenue(): HasMany
    {
        return $this->hasMany(TlBotInlineMessageBotInlineMessageMediaVenue::class, 'geo');
    }
    public function geoMediaAreaGeoPoint(): HasMany
    {
        return $this->hasMany(TlMediaAreaMediaAreaGeoPoint::class, 'geo');
    }
    public function geoMediaAreaVenue(): HasMany
    {
        return $this->hasMany(TlMediaAreaMediaAreaVenue::class, 'geo');
    }
    public function geoMessageMediaGeoLive(): HasMany
    {
        return $this->hasMany(TlMessageMediaMessageMediaGeoLive::class, 'geo');
    }
    public function geoMessageMediaVenue(): HasMany
    {
        return $this->hasMany(TlMessageMediaMessageMediaVenue::class, 'geo');
    }
    public function geoPageBlockMap(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockMap::class, 'geo');
    }
    public function geoPoint(): HasMany
    {
        return $this->hasMany(TlChannelLocationChannelLocation::class, 'geo_point');
    }
    public function geoPointBusinessLocation(): HasMany
    {
        return $this->hasMany(TlBusinessLocationBusinessLocation::class, 'geo_point');
    }
    public function geoUpdateBotInlineQuery(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateBotInlineQuery::class, 'geo');
    }
    public function geoUpdateBotInlineSend(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateBotInlineSend::class, 'geo');
    }
}
