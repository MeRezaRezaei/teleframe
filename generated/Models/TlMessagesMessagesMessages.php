<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesMessagesMessagesChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesMessagesMessagesMessages;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesMessagesMessagesTopics;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesMessagesMessagesUsers;

/** Constructor model for messages.messages of messages.Messages (crc32 1d73e7ea). */
final class TlMessagesMessagesMessages extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_messages_messages';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function messages(): HasMany
    {
        return $this->tlChild(TlMessagesMessagesMessagesMessages::class);
    }
    public function topics(): HasMany
    {
        return $this->tlChild(TlMessagesMessagesMessagesTopics::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlMessagesMessagesMessagesChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlMessagesMessagesMessagesUsers::class);
    }
}
