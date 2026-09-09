<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateJoinChatWebViewDecision;

/** Anchor model for TL type JoinChatBotResult (spec §4.1). */
final class TlJoinChatBotResult extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_join_chat_bot_result';

    protected $guarded = [];

    public function result(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateJoinChatWebViewDecision::class, 'result');
    }
}
