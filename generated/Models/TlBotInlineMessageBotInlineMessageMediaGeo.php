<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for botInlineMessageMediaGeo of BotInlineMessage (crc32 051846fd). */
final class TlBotInlineMessageBotInlineMessageMediaGeo extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_bot_inline_message_bot_inline_message_media_geo';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'geo' => 'string',
        'heading' => 'int',
        'period' => 'int',
        'proximity_notification_radius' => 'int',
        'reply_markup' => 'string',
    ];
}
