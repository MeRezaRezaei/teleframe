<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for inlineQueryPeerTypeSameBotPM of InlineQueryPeerType (crc32 3081ed9d). */
final class TlInlineQueryPeerTypeInlineQueryPeerTypeSameBotPM extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_inline_query_peer_type_inline_query_peer_t_a7c6c467b6d2';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
