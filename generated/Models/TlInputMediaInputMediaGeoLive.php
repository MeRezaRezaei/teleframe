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

/** Constructor model for inputMediaGeoLive of InputMedia (crc32 971fa843). */
final class TlInputMediaInputMediaGeoLive extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_media_input_media_geo_live';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'stopped' => 'bool',
        'heading' => 'int',
        'period' => 'int',
        'proximity_notification_radius' => 'int',
    ];

    public function geoPoint(): BelongsTo
    {
        return $this->belongsTo(TlInputGeoPoint::class, 'geo_point');
    }
}
