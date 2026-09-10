<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for joinChatBotResultDeclined of JoinChatBotResult (crc32 0efa0194). */
final class TlJoinChatBotResultJoinChatBotResultDeclined extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_join_chat_bot_result_join_chat_bot_result_declined';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
