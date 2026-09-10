<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputWebDocumentInputWebDocumentAttributes;

/** Constructor model for inputWebDocument of InputWebDocument (crc32 9bed434d). */
final class TlInputWebDocumentInputWebDocument extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_web_document_input_web_document';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'url' => 'string',
        'tl_size' => 'int',
        'mime_type' => 'string',
    ];

    public function attributes(): HasMany
    {
        return $this->tlChild(TlInputWebDocumentInputWebDocumentAttributes::class);
    }
}
