<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for documentAttributeHasStickers of DocumentAttribute (crc32 9801d2f7). */
final class TlDocumentAttributeDocumentAttributeHasStickers extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_document_attribute_document_attribute_has_stickers';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
