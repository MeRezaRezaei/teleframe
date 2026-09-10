<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChat;

/** Constructor model for chatInviteAlready of ChatInvite (crc32 5a686d7c). */
final class TlChatInviteChatInviteAlready extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_chat_invite_chat_invite_already';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(TlChat::class, 'chat');
    }
}
