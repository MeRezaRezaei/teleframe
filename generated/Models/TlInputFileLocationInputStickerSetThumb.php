<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputStickerSet;

/** Constructor model for inputStickerSetThumb of InputFileLocation (crc32 9d84f3db). */
final class TlInputFileLocationInputStickerSetThumb extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_file_location_input_sticker_set_thumb';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'thumb_version' => 'int',
    ];

    public function stickerset(): BelongsTo
    {
        return $this->belongsTo(TlInputStickerSet::class, 'stickerset');
    }
}
