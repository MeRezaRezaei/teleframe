<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for botApp of BotApp (crc32 95fcd1d6). */
final class TlBotAppBotApp extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_bot_app_bot_app';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'tl_id' => 'int',
        'access_hash' => 'int',
        'short_name' => 'string',
        'title' => 'string',
        'description' => 'string',
        'photo' => 'string',
        'document' => 'string',
        'hash' => 'int',
    ];
}
