<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputBotInlineMessageMediaGeo of InputBotInlineMessage (crc32 96929a85). */
final class TlInputBotInlineMessageInputBotInlineMessageMediaGeo extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_bot_inline_message_input_bot_inline__a1361e727854';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'geo_point' => 'string',
        'heading' => 'int',
        'period' => 'int',
        'proximity_notification_radius' => 'int',
        'reply_markup' => 'string',
    ];
}
