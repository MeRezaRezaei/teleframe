<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inlineQueryPeerTypeBroadcast of InlineQueryPeerType (crc32 6334ee9a). */
final class TlInlineQueryPeerTypeInlineQueryPeerTypeBroadcast extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_inline_query_peer_type_inline_query_peer_type_broadcast';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
