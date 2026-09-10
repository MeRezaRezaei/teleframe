<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesChatFullChatFull;

/** Anchor model for TL type ChatFull (spec §4.1). */
final class TlChatFull extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_chat_full_channel_full';

    protected $guarded = [];

    public function fullChat(): HasMany
    {
        return $this->hasMany(TlMessagesChatFullChatFull::class, 'full_chat');
    }
}
