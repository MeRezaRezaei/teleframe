<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for documentAttributeCustomEmoji of DocumentAttribute (crc32 fd149899). */
final class TlDocumentAttributeDocumentAttributeCustomEmoji extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_document_attribute_document_attribute_custom_emoji';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'free' => 'bool',
        'text_color' => 'bool',
        'alt' => 'string',
        'stickerset' => 'string',
    ];
}
