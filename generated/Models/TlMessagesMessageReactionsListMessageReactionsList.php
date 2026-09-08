<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesMessageReactionsListMessageReactionsListReactions;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesMessageReactionsListMessageReactionsListChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesMessageReactionsListMessageReactionsListUsers;

/** Constructor model for messages.messageReactionsList of messages.MessageReactionsList (crc32 31bd492d). */
final class TlMessagesMessageReactionsListMessageReactionsList extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_messages_message_reactions_list_message_reactions_list';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'count' => 'int',
        'next_offset' => 'string',
    ];

    public function reactions(): HasMany
    {
        return $this->tlChild(TlMessagesMessageReactionsListMessageReactionsListReactions::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlMessagesMessageReactionsListMessageReactionsListChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlMessagesMessageReactionsListMessageReactionsListUsers::class);
    }
}
