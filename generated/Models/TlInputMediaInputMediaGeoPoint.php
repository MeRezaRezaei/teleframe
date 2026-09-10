<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputGeoPoint;

/** Constructor model for inputMediaGeoPoint of InputMedia (crc32 f9c44144). */
final class TlInputMediaInputMediaGeoPoint extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_media_input_media_geo_point';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function geoPoint(): BelongsTo
    {
        return $this->belongsTo(TlInputGeoPoint::class, 'geo_point');
    }
}
