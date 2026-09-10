<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhotoSize;

/** Constructor model for messageExtendedMediaPreview of MessageExtendedMedia (crc32 ad628cc8). */
final class TlMessageExtendedMediaMessageExtendedMediaPreview extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_extended_media_message_extended_media_preview';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'w' => 'int',
        'h' => 'int',
        'video_duration' => 'int',
    ];

    public function thumb(): BelongsTo
    {
        return $this->belongsTo(TlPhotoSize::class, 'thumb');
    }
}
