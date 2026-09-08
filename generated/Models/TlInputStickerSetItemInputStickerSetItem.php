<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputStickerSetItem of InputStickerSetItem (crc32 32da9e9c). */
final class TlInputStickerSetItemInputStickerSetItem extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_sticker_set_item_input_sticker_set_item';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'document' => 'string',
        'emoji' => 'string',
        'mask_coords' => 'string',
        'keywords' => 'string',
    ];
}
