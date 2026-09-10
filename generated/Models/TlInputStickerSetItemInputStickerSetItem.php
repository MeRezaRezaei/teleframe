<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputDocument;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMaskCoords;

/** Constructor model for inputStickerSetItem of InputStickerSetItem (crc32 32da9e9c). */
final class TlInputStickerSetItemInputStickerSetItem extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_sticker_set_item_input_sticker_set_item';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'emoji' => 'string',
        'keywords' => 'string',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(TlInputDocument::class, 'document');
    }
    public function maskCoords(): BelongsTo
    {
        return $this->belongsTo(TlMaskCoords::class, 'mask_coords');
    }
}
