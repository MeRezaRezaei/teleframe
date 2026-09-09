<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFullChatFull;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChatParticipants;

/** Anchor model for TL type ChatParticipants (spec §4.1). */
final class TlChatParticipants extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_chat_participants';

    protected $guarded = [];

    public function participants(): HasMany
    {
        return $this->hasMany(TlChatFullChatFull::class, 'participants');
    }
    public function participantsUpdateChatParticipants(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateChatParticipants::class, 'participants');
    }
}
