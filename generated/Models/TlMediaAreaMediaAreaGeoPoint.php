<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGeoPoint;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGeoPointAddress;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMediaAreaCoordinates;

/** Constructor model for mediaAreaGeoPoint of MediaArea (crc32 cad5452d). */
final class TlMediaAreaMediaAreaGeoPoint extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_media_area_media_area_geo_point';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
    ];

    public function coordinates(): BelongsTo
    {
        return $this->belongsTo(TlMediaAreaCoordinates::class, 'coordinates');
    }
    public function geo(): BelongsTo
    {
        return $this->belongsTo(TlGeoPoint::class, 'geo');
    }
    public function address(): BelongsTo
    {
        return $this->belongsTo(TlGeoPointAddress::class, 'address');
    }
}
