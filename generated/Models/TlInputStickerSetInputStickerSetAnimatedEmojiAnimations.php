<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputStickerSetAnimatedEmojiAnimations of InputStickerSet (crc32 0cde3739). */
final class TlInputStickerSetInputStickerSetAnimatedEmojiAnimations extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_sticker_set_input_sticker_set_animat_7ff1565b3f75';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
