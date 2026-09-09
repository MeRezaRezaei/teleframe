<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMediaAreaMediaAreaGeoPoint;

/** Anchor model for TL type GeoPointAddress (spec §4.1). */
final class TlGeoPointAddress extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_geo_point_address';

    protected $guarded = [];

    public function address(): HasMany
    {
        return $this->hasMany(TlMediaAreaMediaAreaGeoPoint::class, 'address');
    }
}
