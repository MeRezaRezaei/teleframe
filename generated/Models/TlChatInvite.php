<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRecentMeUrlRecentMeUrlChatInvite;

/** Anchor model for TL type ChatInvite (spec §4.1). */
final class TlChatInvite extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_chat_invite_chat_invite';

    protected $guarded = [];

    public function chatInvite(): HasMany
    {
        return $this->hasMany(TlRecentMeUrlRecentMeUrlChatInvite::class, 'chat_invite');
    }
}
