<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for messageActionGeoProximityReached of MessageAction (crc32 98e0d697). */
final class TlMessageActionMessageActionGeoProximityReached extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_message_action_message_action_geo_proximity_reached';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'from_id' => 'string',
        'to_id' => 'string',
        'distance' => 'int',
    ];
}
