<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesChatsChatsChats;

/** Constructor model for messages.chats of messages.Chats (crc32 64ff9fd5). */
final class TlMessagesChatsChats extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_chats_chats';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function chats(): HasMany
    {
        return $this->tlChild(TlMessagesChatsChatsChats::class);
    }
}
