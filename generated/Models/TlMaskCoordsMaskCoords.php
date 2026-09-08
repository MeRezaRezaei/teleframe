<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for maskCoords of MaskCoords (crc32 aed6dbb2). */
final class TlMaskCoordsMaskCoords extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_mask_coords_mask_coords';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'n' => 'int',
        'x' => 'float',
        'y' => 'float',
        'zoom' => 'float',
    ];
}
