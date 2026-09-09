<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebDocumentWebDocumentAttributes;

/** Constructor model for webDocument of WebDocument (crc32 1c570ed1). */
final class TlWebDocumentWebDocument extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_web_document_web_document';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'url' => 'string',
        'access_hash' => 'int',
        'tl_size' => 'int',
        'mime_type' => 'string',
    ];

    public function attributes(): HasMany
    {
        return $this->tlChild(TlWebDocumentWebDocumentAttributes::class);
    }
}
