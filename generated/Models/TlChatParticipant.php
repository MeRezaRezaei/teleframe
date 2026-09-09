<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatParticipantsChatParticipantsForbidden;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChatParticipant;

/** Anchor model for TL type ChatParticipant (spec §4.1). */
final class TlChatParticipant extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_chat_participant';

    protected $guarded = [];

    public function newParticipant(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateChatParticipant::class, 'new_participant');
    }
    public function prevParticipant(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateChatParticipant::class, 'prev_participant');
    }
    public function selfParticipant(): HasMany
    {
        return $this->hasMany(TlChatParticipantsChatParticipantsForbidden::class, 'self_participant');
    }
}
