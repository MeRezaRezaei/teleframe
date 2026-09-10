<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatlistsChatlistInviteChatlistInviteAlreadyAlready_peers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatlistsChatlistInviteChatlistInviteAlreadyChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatlistsChatlistInviteChatlistInviteAlreadyMissing_peers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatlistsChatlistInviteChatlistInviteAlreadyUsers;

/** Constructor model for chatlists.chatlistInviteAlready of chatlists.ChatlistInvite (crc32 fa87f659). */
final class TlChatlistsChatlistInviteChatlistInviteAlready extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_chatlists_chatlist_invite_chatlist_invite_already';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'filter_id' => 'int',
    ];

    public function missingPeers(): HasMany
    {
        return $this->tlChild(TlChatlistsChatlistInviteChatlistInviteAlreadyMissing_peers::class);
    }
    public function alreadyPeers(): HasMany
    {
        return $this->tlChild(TlChatlistsChatlistInviteChatlistInviteAlreadyAlready_peers::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlChatlistsChatlistInviteChatlistInviteAlreadyChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlChatlistsChatlistInviteChatlistInviteAlreadyUsers::class);
    }
}
