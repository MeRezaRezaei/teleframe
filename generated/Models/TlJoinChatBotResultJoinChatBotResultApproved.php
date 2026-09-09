<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for joinChatBotResultApproved of JoinChatBotResult (crc32 ae152a69). */
final class TlJoinChatBotResultJoinChatBotResultApproved extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_join_chat_bot_result_join_chat_bot_result_approved';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
