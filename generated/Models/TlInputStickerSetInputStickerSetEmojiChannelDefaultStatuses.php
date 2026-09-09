<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for inputStickerSetEmojiChannelDefaultStatuses of InputStickerSet (crc32 49748553). */
final class TlInputStickerSetInputStickerSetEmojiChannelDefaultStatuses extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_sticker_set_input_sticker_set_emoji__d93dca74142c';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
