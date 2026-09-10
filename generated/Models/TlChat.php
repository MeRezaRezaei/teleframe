<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatInviteChatInviteAlready;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatInviteChatInvitePeek;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPageBlockPageBlockChannel;

/** Anchor model for TL type Chat (spec §4.1). */
final class TlChat extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_chat_channel';

    protected $guarded = [];

    public function channel(): HasMany
    {
        return $this->hasMany(TlPageBlockPageBlockChannel::class, 'channel');
    }
    public function chat(): HasMany
    {
        return $this->hasMany(TlChatInviteChatInviteAlready::class, 'chat');
    }
    public function chatChatInvitePeek(): HasMany
    {
        return $this->hasMany(TlChatInviteChatInvitePeek::class, 'chat');
    }
}
