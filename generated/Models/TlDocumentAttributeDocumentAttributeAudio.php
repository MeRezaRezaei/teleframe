<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for documentAttributeAudio of DocumentAttribute (crc32 9852f9c6). */
final class TlDocumentAttributeDocumentAttributeAudio extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_document_attribute_document_attribute_audio';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'voice' => 'bool',
        'duration' => 'int',
        'title' => 'string',
        'performer' => 'string',
        'waveform' => 'string',
    ];
}
