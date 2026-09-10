<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMedia;

/** Constructor model for messageExtendedMedia of MessageExtendedMedia (crc32 ee479c64). */
final class TlMessageExtendedMediaMessageExtendedMedia extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_extended_media_message_extended_media';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function media(): BelongsTo
    {
        return $this->belongsTo(TlMessageMedia::class, 'media');
    }
}
