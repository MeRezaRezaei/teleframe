<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdates;

/** Constructor model for messages.chatInviteJoinResultOk of messages.ChatInviteJoinResult (crc32 445663a7). */
final class TlMessagesChatInviteJoinResultChatInviteJoinResultOk extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_chat_invite_join_result_chat_invi_71ed5b26df07';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function updates(): BelongsTo
    {
        return $this->belongsTo(TlUpdates::class, 'updates');
    }
}
