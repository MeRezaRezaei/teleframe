<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for inlineQueryPeerTypeBotPM of InlineQueryPeerType (crc32 0e3b2d0c). */
final class TlInlineQueryPeerTypeInlineQueryPeerTypeBotPM extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_inline_query_peer_type_inline_query_peer_type_bot_p_m';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
