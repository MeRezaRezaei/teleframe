<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGeoPoint;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInlineQueryPeerType;

/** Constructor model for updateBotInlineQuery of Update (crc32 496f379c). */
final class TlUpdateUpdateBotInlineQuery extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_update_update_bot_inline_query';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'query_id' => 'int',
        'user_id' => 'int',
        'query' => 'string',
        'tl_offset' => 'string',
    ];

    public function geo(): BelongsTo
    {
        return $this->belongsTo(TlGeoPoint::class, 'geo');
    }
    public function peerType(): BelongsTo
    {
        return $this->belongsTo(TlInlineQueryPeerType::class, 'peer_type');
    }
}
