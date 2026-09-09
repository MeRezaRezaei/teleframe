<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for inlineQueryPeerTypeChat of InlineQueryPeerType (crc32 d766c50a). */
final class TlInlineQueryPeerTypeInlineQueryPeerTypeChat extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_inline_query_peer_type_inline_query_peer_type_chat';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
