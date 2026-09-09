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

/** Constructor model for inputWebFileGeoPointLocation of InputWebFileLocation (crc32 9f2221c9). */
final class TlInputWebFileLocationInputWebFileGeoPointLocation extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_web_file_location_input_web_file_geo_aad57bf4e8d0';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'access_hash' => 'int',
        'w' => 'int',
        'h' => 'int',
        'zoom' => 'int',
        'scale' => 'int',
    ];

    public function geoPoint(): BelongsTo
    {
        return $this->belongsTo(TlInputGeoPoint::class, 'geo_point');
    }
}
