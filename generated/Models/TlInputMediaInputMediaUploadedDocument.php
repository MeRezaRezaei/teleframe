<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaUploadedDocumentAttributes;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaUploadedDocumentStickers;

/** Constructor model for inputMediaUploadedDocument of InputMedia (crc32 037c9330). */
final class TlInputMediaInputMediaUploadedDocument extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_media_input_media_uploaded_document';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'nosound_video' => 'bool',
        'force_file' => 'bool',
        'spoiler' => 'bool',
        'file' => 'string',
        'thumb' => 'string',
        'mime_type' => 'string',
        'video_cover' => 'string',
        'video_timestamp' => 'int',
        'ttl_seconds' => 'int',
    ];

    public function attributes(): HasMany
    {
        return $this->tlChild(TlInputMediaInputMediaUploadedDocumentAttributes::class);
    }
    public function stickers(): HasMany
    {
        return $this->tlChild(TlInputMediaInputMediaUploadedDocumentStickers::class);
    }
}
