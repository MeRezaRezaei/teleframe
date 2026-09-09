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

/** Constructor model for messageMediaVenue of MessageMedia (crc32 2ec0533f). */
final class TlMessageMediaMessageMediaVenue extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_media_message_media_venue';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'title' => 'string',
        'address' => 'string',
        'provider' => 'string',
        'venue_id' => 'string',
        'venue_type' => 'string',
    ];

    public function geo(): BelongsTo
    {
        return $this->belongsTo(TlGeoPoint::class, 'geo');
    }
}
