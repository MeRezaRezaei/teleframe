<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDocumentDocumentAttributes;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDocumentDocumentThumbs;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDocumentDocumentVideo_thumbs;

/** Constructor model for document of Document (crc32 8fd4c4d8). */
final class TlDocumentDocument extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_document_document';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'tl_id' => 'int',
        'access_hash' => 'int',
        'file_reference' => 'string',
        'date' => 'int',
        'mime_type' => 'string',
        'tl_size' => 'int',
        'dc_id' => 'int',
    ];

    public function thumbs(): HasMany
    {
        return $this->tlChild(TlDocumentDocumentThumbs::class);
    }
    public function videoThumbs(): HasMany
    {
        return $this->tlChild(TlDocumentDocumentVideo_thumbs::class);
    }
    public function attributes(): HasMany
    {
        return $this->tlChild(TlDocumentDocumentAttributes::class);
    }
}
