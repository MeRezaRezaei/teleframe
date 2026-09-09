<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesPreparedInlineMessagePreparedInlineMessage;

/** Anchor model for TL type BotInlineResult (spec §4.1). */
final class TlBotInlineResult extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_bot_inline_result';

    protected $guarded = [];

    public function result(): HasMany
    {
        return $this->hasMany(TlMessagesPreparedInlineMessagePreparedInlineMessage::class, 'result');
    }
}
