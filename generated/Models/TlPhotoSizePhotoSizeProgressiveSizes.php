<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param sizes (table tl_photo_size_photo_size_progressive__sizes). */
final class TlPhotoSizePhotoSizeProgressiveSizes extends TlAnchorModel
{
    protected $table = 'tl_photo_size_photo_size_progressive__sizes';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'int',
    ];
}
