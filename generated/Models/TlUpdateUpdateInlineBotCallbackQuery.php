<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineMessageID;

/** Constructor model for updateInlineBotCallbackQuery of Update (crc32 691e9052). */
final class TlUpdateUpdateInlineBotCallbackQuery extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_update_update_inline_bot_callback_query';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'query_id' => 'int',
        'user_id' => 'int',
        'chat_instance' => 'int',
        'data' => 'string',
        'game_short_name' => 'string',
    ];

    public function msgId(): BelongsTo
    {
        return $this->belongsTo(TlInputBotInlineMessageID::class, 'msg_id');
    }
}
