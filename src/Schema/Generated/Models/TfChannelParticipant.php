<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Telegram channel participant — tf_channel_participants.
 *
 * PK: (channel_id, user_id, account_id).
 * Represents membership/role of a user in a channel/supergroup.
 */
class TfChannelParticipant extends TfModel
{
    protected $table = 'tf_channel_participants';

    /** Composite PK — Eloquent uses channel_id as logical key. */
    protected $primaryKey = 'channel_id';

    /** @var list<string> */
    protected $fillable = [
        'channel_id', 'user_id', 'account_id', 'date', 'rank',
        'inviter_id', 'promoted_by', 'kicked_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'tl_data' => 'array',
        'is_via_request' => 'boolean',
    ];

    // -- Relations --

    /** The user who is a participant. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(TfUser::class, 'user_id', 'id');
    }

    /** The channel this participation belongs to. */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(TfChannel::class, 'channel_id', 'id');
    }
}
