<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFull;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesChatFullChatFullChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesChatFullChatFullUsers;

/** Constructor model for messages.chatFull of messages.ChatFull (crc32 e5d7d19c). */
final class TlMessagesChatFullChatFull extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_chat_full_chat_full';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function chats(): HasMany
    {
        return $this->tlChild(TlMessagesChatFullChatFullChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlMessagesChatFullChatFullUsers::class);
    }

    public function fullChat(): BelongsTo
    {
        return $this->belongsTo(TlChatFull::class, 'full_chat');
    }
}
