<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for inputStickerSetEmojiDefaultStatuses of InputStickerSet (crc32 29d0f5ee). */
final class TlInputStickerSetInputStickerSetEmojiDefaultStatuses extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_sticker_set_input_sticker_set_emoji__f673730c96f9';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
