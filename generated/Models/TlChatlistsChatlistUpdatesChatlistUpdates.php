<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatlistsChatlistUpdatesChatlistUpdatesChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatlistsChatlistUpdatesChatlistUpdatesMissing_peers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatlistsChatlistUpdatesChatlistUpdatesUsers;

/** Constructor model for chatlists.chatlistUpdates of chatlists.ChatlistUpdates (crc32 93bd878d). */
final class TlChatlistsChatlistUpdatesChatlistUpdates extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_chatlists_chatlist_updates_chatlist_updates';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function missingPeers(): HasMany
    {
        return $this->tlChild(TlChatlistsChatlistUpdatesChatlistUpdatesMissing_peers::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlChatlistsChatlistUpdatesChatlistUpdatesChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlChatlistsChatlistUpdatesChatlistUpdatesUsers::class);
    }
}
