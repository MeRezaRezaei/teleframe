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
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputDocument;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputFile;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaUploadedPhotoStickers;

/** Constructor model for inputMediaUploadedPhoto of InputMedia (crc32 7d8375da). */
final class TlInputMediaInputMediaUploadedPhoto extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_media_input_media_uploaded_photo';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'spoiler' => 'bool',
        'live_photo' => 'bool',
        'ttl_seconds' => 'int',
    ];

    public function stickers(): HasMany
    {
        return $this->tlChild(TlInputMediaInputMediaUploadedPhotoStickers::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(TlInputFile::class, 'file');
    }
    public function video(): BelongsTo
    {
        return $this->belongsTo(TlInputDocument::class, 'video');
    }
}
