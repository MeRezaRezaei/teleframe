<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;

/**
 * Telegram message — tf_messages.
 *
 * PK: auto-increment id (surrogate). Unique constraint on
 * (peer_id, message_id, account_id).
 *
 * Telegram message IDs are per-peer 32-bit integers, NOT globally unique.
 * The same message_id can exist across different peers.
 */
class TfMessage extends TfModel
{
    use PeerResolution;

    protected $table = 'tf_messages';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    /** @var list<string> */
    protected $fillable = [
        'message_id', 'peer_id', 'peer_type', 'from_id', 'from_type',
        'date', 'constructor_id', 'account_id', 'edit_date',
        'grouped_id', 'ttl_period',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'tl_data' => 'array',
        'is_outgoing' => 'boolean',
        'is_mentioned' => 'boolean',
        'is_media_unread' => 'boolean',
        'is_silent' => 'boolean',
        'is_post' => 'boolean',
        'is_from_scheduled' => 'boolean',
        'is_legacy' => 'boolean',
        'is_edit_hide' => 'boolean',
        'is_pinned' => 'boolean',
        'is_noforwards' => 'boolean',
    ];

    // -- Relations --

    /** The user who sent this message. */
    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(TfUser::class, 'from_id', 'id');
    }

    /** The channel this message belongs to (when peer_type is peerChannel). */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(TfChannel::class, 'peer_id', 'id');
    }

    /** The chat this message belongs to (when peer_type is peerChat). */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(TfChat::class, 'peer_id', 'id');
    }
}
