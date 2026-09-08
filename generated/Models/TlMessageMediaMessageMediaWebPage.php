<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for messageMediaWebPage of MessageMedia (crc32 ddf10c3b). */
final class TlMessageMediaMessageMediaWebPage extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_message_media_message_media_web_page';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'force_large_media' => 'bool',
        'force_small_media' => 'bool',
        'manual' => 'bool',
        'safe' => 'bool',
        'webpage' => 'string',
    ];
}
