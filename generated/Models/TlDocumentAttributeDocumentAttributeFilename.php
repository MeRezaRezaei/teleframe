<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for documentAttributeFilename of DocumentAttribute (crc32 15590068). */
final class TlDocumentAttributeDocumentAttributeFilename extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_document_attribute_document_attribute_filename';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'file_name' => 'string',
    ];
}
