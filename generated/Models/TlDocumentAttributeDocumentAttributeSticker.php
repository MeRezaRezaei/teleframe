<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for documentAttributeSticker of DocumentAttribute (crc32 6319d612). */
final class TlDocumentAttributeDocumentAttributeSticker extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_document_attribute_document_attribute_sticker';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'mask' => 'bool',
        'alt' => 'string',
        'stickerset' => 'string',
        'mask_coords' => 'string',
    ];
}
