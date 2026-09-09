<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputGeoPoint;

/** Constructor model for inputMediaVenue of InputMedia (crc32 c13d1c11). */
final class TlInputMediaInputMediaVenue extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_media_input_media_venue';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'title' => 'string',
        'address' => 'string',
        'provider' => 'string',
        'venue_id' => 'string',
        'venue_type' => 'string',
    ];

    public function geoPoint(): BelongsTo
    {
        return $this->belongsTo(TlInputGeoPoint::class, 'geo_point');
    }
}
