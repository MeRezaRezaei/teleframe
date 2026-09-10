<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGeoPoint;

/** Constructor model for channelLocation of ChannelLocation (crc32 209b82db). */
final class TlChannelLocationChannelLocation extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_channel_location_channel_location';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'address' => 'string',
    ];

    public function geoPoint(): BelongsTo
    {
        return $this->belongsTo(TlGeoPoint::class, 'geo_point');
    }
}
