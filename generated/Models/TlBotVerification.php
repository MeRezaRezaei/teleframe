<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFullChannelFull;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatInviteChatInvite;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserFullUserFull;

/** Anchor model for TL type BotVerification (spec §4.1). */
final class TlBotVerification extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_bot_verification_bot_verification';

    protected $guarded = [];

    public function botVerification(): HasMany
    {
        return $this->hasMany(TlChatFullChannelFull::class, 'bot_verification');
    }
    public function botVerificationChatInvite(): HasMany
    {
        return $this->hasMany(TlChatInviteChatInvite::class, 'bot_verification');
    }
    public function botVerificationUserFull(): HasMany
    {
        return $this->hasMany(TlUserFullUserFull::class, 'bot_verification');
    }
}
