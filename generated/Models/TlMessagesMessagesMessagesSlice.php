<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesMessagesMessagesSliceMessages;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesMessagesMessagesSliceTopics;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesMessagesMessagesSliceChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesMessagesMessagesSliceUsers;

/** Constructor model for messages.messagesSlice of messages.Messages (crc32 5f206716). */
final class TlMessagesMessagesMessagesSlice extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_messages_messages_messages_slice';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'inexact' => 'bool',
        'count' => 'int',
        'next_rate' => 'int',
        'offset_id_offset' => 'int',
        'search_flood' => 'string',
    ];

    public function messages(): HasMany
    {
        return $this->tlChild(TlMessagesMessagesMessagesSliceMessages::class);
    }
    public function topics(): HasMany
    {
        return $this->tlChild(TlMessagesMessagesMessagesSliceTopics::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlMessagesMessagesMessagesSliceChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlMessagesMessagesMessagesSliceUsers::class);
    }
}
