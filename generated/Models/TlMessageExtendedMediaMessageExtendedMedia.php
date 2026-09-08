<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for messageExtendedMedia of MessageExtendedMedia (crc32 ee479c64). */
final class TlMessageExtendedMediaMessageExtendedMedia extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_message_extended_media_message_extended_media';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'media' => 'string',
    ];
}
