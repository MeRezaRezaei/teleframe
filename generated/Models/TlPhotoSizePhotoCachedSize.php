<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for photoCachedSize of PhotoSize (crc32 021e1ad6). */
final class TlPhotoSizePhotoCachedSize extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_photo_size_photo_cached_size';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'tl_type' => 'string',
        'w' => 'int',
        'h' => 'int',
        'bytes' => 'string',
    ];
}
