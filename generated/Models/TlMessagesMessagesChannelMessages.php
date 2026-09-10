<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesMessagesChannelMessagesChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesMessagesChannelMessagesMessages;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesMessagesChannelMessagesTopics;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesMessagesChannelMessagesUsers;

/** Constructor model for messages.channelMessages of messages.Messages (crc32 c776ba4e). */
final class TlMessagesMessagesChannelMessages extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_messages_channel_messages';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'inexact' => 'bool',
        'pts' => 'int',
        'count' => 'int',
        'offset_id_offset' => 'int',
    ];

    public function messages(): HasMany
    {
        return $this->tlChild(TlMessagesMessagesChannelMessagesMessages::class);
    }
    public function topics(): HasMany
    {
        return $this->tlChild(TlMessagesMessagesChannelMessagesTopics::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlMessagesMessagesChannelMessagesChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlMessagesMessagesChannelMessagesUsers::class);
    }
}
