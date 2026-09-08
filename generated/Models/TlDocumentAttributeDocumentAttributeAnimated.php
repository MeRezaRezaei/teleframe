<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for documentAttributeAnimated of DocumentAttribute (crc32 11b58939). */
final class TlDocumentAttributeDocumentAttributeAnimated extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_document_attribute_document_attribute_animated';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
