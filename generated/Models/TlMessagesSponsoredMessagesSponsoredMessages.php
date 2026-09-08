<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesSponsoredMessagesSponsoredMessagesMessages;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesSponsoredMessagesSponsoredMessagesChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesSponsoredMessagesSponsoredMessagesUsers;

/** Constructor model for messages.sponsoredMessages of messages.SponsoredMessages (crc32 ffda656d). */
final class TlMessagesSponsoredMessagesSponsoredMessages extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_messages_sponsored_messages_sponsored_messages';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'posts_between' => 'int',
        'start_delay' => 'int',
        'between_delay' => 'int',
    ];

    public function messages(): HasMany
    {
        return $this->tlChild(TlMessagesSponsoredMessagesSponsoredMessagesMessages::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlMessagesSponsoredMessagesSponsoredMessagesChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlMessagesSponsoredMessagesSponsoredMessagesUsers::class);
    }
}
