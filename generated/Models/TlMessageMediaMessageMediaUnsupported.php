<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for messageMediaUnsupported of MessageMedia (crc32 9f84f49e). */
final class TlMessageMediaMessageMediaUnsupported extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_message_media_message_media_unsupported';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
