<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesForumTopicsForumTopicsTopics;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesForumTopicsForumTopicsMessages;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesForumTopicsForumTopicsChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesForumTopicsForumTopicsUsers;

/** Constructor model for messages.forumTopics of messages.ForumTopics (crc32 367617d3). */
final class TlMessagesForumTopicsForumTopics extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_messages_forum_topics_forum_topics';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'order_by_create_date' => 'bool',
        'count' => 'int',
        'pts' => 'int',
    ];

    public function topics(): HasMany
    {
        return $this->tlChild(TlMessagesForumTopicsForumTopicsTopics::class);
    }
    public function messages(): HasMany
    {
        return $this->tlChild(TlMessagesForumTopicsForumTopicsMessages::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlMessagesForumTopicsForumTopicsChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlMessagesForumTopicsForumTopicsUsers::class);
    }
}
