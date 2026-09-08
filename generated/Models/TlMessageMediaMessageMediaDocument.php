<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaDocumentAlt_documents;

/** Constructor model for messageMediaDocument of MessageMedia (crc32 52d8ccd9). */
final class TlMessageMediaMessageMediaDocument extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_message_media_message_media_document';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'nopremium' => 'bool',
        'spoiler' => 'bool',
        'video' => 'bool',
        'round' => 'bool',
        'voice' => 'bool',
        'document' => 'string',
        'video_cover' => 'string',
        'video_timestamp' => 'int',
        'ttl_seconds' => 'int',
    ];

    public function altDocuments(): HasMany
    {
        return $this->tlChild(TlMessageMediaMessageMediaDocumentAlt_documents::class);
    }
}
