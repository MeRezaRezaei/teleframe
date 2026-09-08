<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for messages.chatInviteJoinResultOk of messages.ChatInviteJoinResult (crc32 445663a7). */
final class TlMessagesChatInviteJoinResultChatInviteJoinResultOk extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_messages_chat_invite_join_result_chat_invi_71ed5b26df07';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'updates' => 'string',
    ];
}
