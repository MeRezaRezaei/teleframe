<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for messages.botCallbackAnswer of messages.BotCallbackAnswer (crc32 36585ea4). */
final class TlMessagesBotCallbackAnswerBotCallbackAnswer extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_messages_bot_callback_answer_bot_callback_answer';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'alert' => 'bool',
        'has_url' => 'bool',
        'native_ui' => 'bool',
        'message' => 'string',
        'url' => 'string',
        'cache_time' => 'int',
    ];
}
