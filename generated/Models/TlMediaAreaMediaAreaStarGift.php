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

/** Constructor model for mediaAreaStarGift of MediaArea (crc32 5787686d). */
final class TlMediaAreaMediaAreaStarGift extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_media_area_media_area_star_gift';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'slug' => 'string',
    ];

    public function coordinates(): BelongsTo
    {
        return $this->belongsTo(TlMediaAreaCoordinates::class, 'coordinates');
    }
}
