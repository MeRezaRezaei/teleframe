<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for messageExtendedMediaPreview of MessageExtendedMedia (crc32 ad628cc8). */
final class TlMessageExtendedMediaMessageExtendedMediaPreview extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_message_extended_media_message_extended_media_preview';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'w' => 'int',
        'h' => 'int',
        'thumb' => 'string',
        'video_duration' => 'int',
    ];
}
