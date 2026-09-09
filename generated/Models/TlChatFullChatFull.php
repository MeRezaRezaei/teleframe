<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFullChatFullBot_info;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFullChatFullRecent_requesters;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatParticipants;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatReactions;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlExportedChatInvite;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputGroupCall;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPeerNotifySettings;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoto;

/** Constructor model for chatFull of ChatFull (crc32 2633421b). */
final class TlChatFullChatFull extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_chat_full_chat_full';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'can_set_username' => 'bool',
        'has_scheduled' => 'bool',
        'translations_disabled' => 'bool',
        'tl_id' => 'int',
        'about' => 'string',
        'pinned_msg_id' => 'int',
        'folder_id' => 'int',
        'ttl_period' => 'int',
        'theme_emoticon' => 'string',
        'requests_pending' => 'int',
        'reactions_limit' => 'int',
    ];

    public function botInfo(): HasMany
    {
        return $this->tlChild(TlChatFullChatFullBot_info::class);
    }
    public function recentRequesters(): HasMany
    {
        return $this->tlChild(TlChatFullChatFullRecent_requesters::class);
    }

    public function participants(): BelongsTo
    {
        return $this->belongsTo(TlChatParticipants::class, 'participants');
    }
    public function chatPhoto(): BelongsTo
    {
        return $this->belongsTo(TlPhoto::class, 'chat_photo');
    }
    public function notifySettings(): BelongsTo
    {
        return $this->belongsTo(TlPeerNotifySettings::class, 'notify_settings');
    }
    public function exportedInvite(): BelongsTo
    {
        return $this->belongsTo(TlExportedChatInvite::class, 'exported_invite');
    }
    public function call(): BelongsTo
    {
        return $this->belongsTo(TlInputGroupCall::class, 'call');
    }
    public function availableReactions(): BelongsTo
    {
        return $this->belongsTo(TlChatReactions::class, 'available_reactions');
    }
}
