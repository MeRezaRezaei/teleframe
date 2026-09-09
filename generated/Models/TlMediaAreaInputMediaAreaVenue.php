<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMediaAreaCoordinates;

/** Constructor model for inputMediaAreaVenue of MediaArea (crc32 b282217f). */
final class TlMediaAreaInputMediaAreaVenue extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_media_area_input_media_area_venue';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'query_id' => 'int',
        'result_id' => 'string',
    ];

    public function coordinates(): BelongsTo
    {
        return $this->belongsTo(TlMediaAreaCoordinates::class, 'coordinates');
    }
}
