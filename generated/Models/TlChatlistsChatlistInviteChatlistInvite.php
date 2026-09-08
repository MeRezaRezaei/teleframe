<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatlistsChatlistInviteChatlistInvitePeers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatlistsChatlistInviteChatlistInviteChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatlistsChatlistInviteChatlistInviteUsers;

/** Constructor model for chatlists.chatlistInvite of chatlists.ChatlistInvite (crc32 f10ece2f). */
final class TlChatlistsChatlistInviteChatlistInvite extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_chatlists_chatlist_invite_chatlist_invite';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'title_noanimate' => 'bool',
        'title' => 'string',
        'emoticon' => 'string',
    ];

    public function peers(): HasMany
    {
        return $this->tlChild(TlChatlistsChatlistInviteChatlistInvitePeers::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlChatlistsChatlistInviteChatlistInviteChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlChatlistsChatlistInviteChatlistInviteUsers::class);
    }
}
