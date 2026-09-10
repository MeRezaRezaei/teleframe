<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatlistsChatlistInviteChatlistInviteChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatlistsChatlistInviteChatlistInvitePeers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatlistsChatlistInviteChatlistInviteUsers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlTextWithEntities;

/** Constructor model for chatlists.chatlistInvite of chatlists.ChatlistInvite (crc32 f10ece2f). */
final class TlChatlistsChatlistInviteChatlistInvite extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_chatlists_chatlist_invite_chatlist_invite';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'title_noanimate' => 'bool',
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

    public function title(): BelongsTo
    {
        return $this->belongsTo(TlTextWithEntities::class, 'title');
    }
}
