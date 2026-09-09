<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDocument;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaDocumentAlt_documents;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoto;

/** Constructor model for messageMediaDocument of MessageMedia (crc32 52d8ccd9). */
final class TlMessageMediaMessageMediaDocument extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

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
        'video_timestamp' => 'int',
        'ttl_seconds' => 'int',
    ];

    public function altDocuments(): HasMany
    {
        return $this->tlChild(TlMessageMediaMessageMediaDocumentAlt_documents::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(TlDocument::class, 'document');
    }
    public function videoCover(): BelongsTo
    {
        return $this->belongsTo(TlPhoto::class, 'video_cover');
    }
}
