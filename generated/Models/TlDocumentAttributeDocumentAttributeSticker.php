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
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMaskCoords;

/** Constructor model for documentAttributeSticker of DocumentAttribute (crc32 6319d612). */
final class TlDocumentAttributeDocumentAttributeSticker extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_document_attribute_document_attribute_sticker';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'mask' => 'bool',
        'alt' => 'string',
    ];

    public function stickerset(): BelongsTo
    {
        return $this->belongsTo(TlInputStickerSet::class, 'stickerset');
    }
    public function maskCoords(): BelongsTo
    {
        return $this->belongsTo(TlMaskCoords::class, 'mask_coords');
    }
}
