<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputStickerSetEmojiDefaultTopicIcons of InputStickerSet (crc32 44c1f8e9). */
final class TlInputStickerSetInputStickerSetEmojiDefaultTopicIcons extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_sticker_set_input_sticker_set_emoji__e38d997c577e';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
