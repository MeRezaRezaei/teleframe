<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebDocumentWebDocumentNoProxyAttributes;

/** Constructor model for webDocumentNoProxy of WebDocument (crc32 f9c8bcc6). */
final class TlWebDocumentWebDocumentNoProxy extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_web_document_web_document_no_proxy';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'url' => 'string',
        'tl_size' => 'int',
        'mime_type' => 'string',
    ];

    public function attributes(): HasMany
    {
        return $this->tlChild(TlWebDocumentWebDocumentNoProxyAttributes::class);
    }
}
