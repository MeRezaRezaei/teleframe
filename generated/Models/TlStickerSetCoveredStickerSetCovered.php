<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for stickerSetCovered of StickerSetCovered (crc32 6410a5d2). */
final class TlStickerSetCoveredStickerSetCovered extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_sticker_set_covered_sticker_set_covered';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'set' => 'string',
        'cover' => 'string',
    ];
}
