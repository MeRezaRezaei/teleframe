<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for mediaAreaCoordinates of MediaAreaCoordinates (crc32 cfc9e002). */
final class TlMediaAreaCoordinatesMediaAreaCoordinates extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_media_area_coordinates_media_area_coordinates';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'x' => 'float',
        'y' => 'float',
        'w' => 'float',
        'h' => 'float',
        'rotation' => 'float',
        'radius' => 'float',
    ];
}
