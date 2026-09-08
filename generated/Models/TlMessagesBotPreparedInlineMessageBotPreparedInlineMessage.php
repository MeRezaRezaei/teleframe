<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for messages.botPreparedInlineMessage of messages.BotPreparedInlineMessage (crc32 8ecf0511). */
final class TlMessagesBotPreparedInlineMessageBotPreparedInlineMessage extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_messages_bot_prepared_inline_message_bot_p_d161024c1fed';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'tl_id' => 'string',
        'expire_date' => 'int',
    ];
}
