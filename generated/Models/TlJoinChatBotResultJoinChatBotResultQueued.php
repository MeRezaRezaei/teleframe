<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for joinChatBotResultQueued of JoinChatBotResult (crc32 98a3a840). */
final class TlJoinChatBotResultJoinChatBotResultQueued extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_join_chat_bot_result_join_chat_bot_result_queued';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
