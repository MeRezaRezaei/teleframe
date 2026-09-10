<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionChangeAvailableReactions;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFullChannelFull;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFullChatFull;

/** Anchor model for TL type ChatReactions (spec §4.1). */
final class TlChatReactions extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_chat_reactions_chat_reactions_all';

    protected $guarded = [];

    public function availableReactions(): HasMany
    {
        return $this->hasMany(TlChatFullChatFull::class, 'available_reactions');
    }
    public function availableReactionsChannelFull(): HasMany
    {
        return $this->hasMany(TlChatFullChannelFull::class, 'available_reactions');
    }
    public function newValue(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionChangeAvailableReactions::class, 'new_value');
    }
    public function prevValue(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionChangeAvailableReactions::class, 'prev_value');
    }
}
