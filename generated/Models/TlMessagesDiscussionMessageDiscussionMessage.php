<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesDiscussionMessageDiscussionMessageChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesDiscussionMessageDiscussionMessageMessages;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesDiscussionMessageDiscussionMessageUsers;

/** Constructor model for messages.discussionMessage of messages.DiscussionMessage (crc32 a6341782). */
final class TlMessagesDiscussionMessageDiscussionMessage extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_discussion_message_discussion_message';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'max_id' => 'int',
        'read_inbox_max_id' => 'int',
        'read_outbox_max_id' => 'int',
        'unread_count' => 'int',
    ];

    public function messages(): HasMany
    {
        return $this->tlChild(TlMessagesDiscussionMessageDiscussionMessageMessages::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlMessagesDiscussionMessageDiscussionMessageChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlMessagesDiscussionMessageDiscussionMessageUsers::class);
    }
}
