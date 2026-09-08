<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for joinChatBotResultWebView of JoinChatBotResult (crc32 d6e3b813). */
final class TlJoinChatBotResultJoinChatBotResultWebView extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_join_chat_bot_result_join_chat_bot_result_web_view';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'url' => 'string',
    ];
}
