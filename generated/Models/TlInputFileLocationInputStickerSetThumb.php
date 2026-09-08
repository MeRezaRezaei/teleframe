<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputStickerSetThumb of InputFileLocation (crc32 9d84f3db). */
final class TlInputFileLocationInputStickerSetThumb extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_file_location_input_sticker_set_thumb';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'stickerset' => 'string',
        'thumb_version' => 'int',
    ];
}
