<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for inputStickerSetEmojiGenericAnimations of InputStickerSet (crc32 04c4d4ce). */
final class TlInputStickerSetInputStickerSetEmojiGenericAnimations extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_sticker_set_input_sticker_set_emoji__d4587551ad4b';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
