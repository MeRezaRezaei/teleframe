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

/** Constructor model for documentAttributeCustomEmoji of DocumentAttribute (crc32 fd149899). */
final class TlDocumentAttributeDocumentAttributeCustomEmoji extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_document_attribute_document_attribute_custom_emoji';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'free' => 'bool',
        'text_color' => 'bool',
        'alt' => 'string',
    ];

    public function stickerset(): BelongsTo
    {
        return $this->belongsTo(TlInputStickerSet::class, 'stickerset');
    }
}
