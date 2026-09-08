<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for botPreviewMedia of BotPreviewMedia (crc32 23e91ba3). */
final class TlBotPreviewMediaBotPreviewMedia extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_bot_preview_media_bot_preview_media';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'date' => 'int',
        'media' => 'string',
    ];
}
