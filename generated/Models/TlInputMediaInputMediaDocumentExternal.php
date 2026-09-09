<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputPhoto;

/** Constructor model for inputMediaDocumentExternal of InputMedia (crc32 779600f9). */
final class TlInputMediaInputMediaDocumentExternal extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_media_input_media_document_external';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'spoiler' => 'bool',
        'url' => 'string',
        'ttl_seconds' => 'int',
        'video_timestamp' => 'int',
    ];

    public function videoCover(): BelongsTo
    {
        return $this->belongsTo(TlInputPhoto::class, 'video_cover');
    }
}
