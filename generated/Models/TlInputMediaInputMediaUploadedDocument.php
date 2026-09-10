<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputFile;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaUploadedDocumentAttributes;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaUploadedDocumentStickers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputPhoto;

/** Constructor model for inputMediaUploadedDocument of InputMedia (crc32 037c9330). */
final class TlInputMediaInputMediaUploadedDocument extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_media_input_media_uploaded_document';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'nosound_video' => 'bool',
        'force_file' => 'bool',
        'spoiler' => 'bool',
        'mime_type' => 'string',
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

    public function file(): BelongsTo
    {
        return $this->belongsTo(TlInputFile::class, 'file');
    }
    public function thumb(): BelongsTo
    {
        return $this->belongsTo(TlInputFile::class, 'thumb');
    }
    public function videoCover(): BelongsTo
    {
        return $this->belongsTo(TlInputPhoto::class, 'video_cover');
    }
}
