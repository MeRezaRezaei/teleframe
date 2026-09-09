<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotInlineQuery;

/** Anchor model for TL type InlineQueryPeerType (spec §4.1). */
final class TlInlineQueryPeerType extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_inline_query_peer_type';

    protected $guarded = [];

    public function peerType(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateBotInlineQuery::class, 'peer_type');
    }
}
