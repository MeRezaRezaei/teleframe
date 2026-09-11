<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Telegram channel/supergroup — tf_channels.
 *
 * PK: (id, account_id). id is the globally unique Telegram channel ID.
 * Telegram channel IDs are negative 64-bit integers (bitwise NOT of the
 * actual value, so they appear as large negative numbers in signed 64-bit).
 */
class TfChannel extends TfModel
{
    protected $table = 'tf_channels';

    protected $primaryKey = 'id';

    /** @var list<string> */
    protected $fillable = [
        'id', 'account_id', 'access_hash', 'title', 'username',
        'date', 'participants_count', 'restriction_reason',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'tl_data' => 'array',
        'is_broadcast' => 'boolean',
        'is_megagroup' => 'boolean',
        'is_verified' => 'boolean',
        'is_restricted' => 'boolean',
        'is_left' => 'boolean',
        'is_forum' => 'boolean',
        'is_creator' => 'boolean',
        'is_scam' => 'boolean',
        'is_fake' => 'boolean',
        'is_noforwards' => 'boolean',
        'is_gigagroup' => 'boolean',
        'is_slowmode_enabled' => 'boolean',
        'is_signatures' => 'boolean',
    ];

    // -- Relations --

    /** Messages in this channel/supergroup. */
    public function messages(): HasMany
    {
        return $this->hasMany(TfMessage::class, 'peer_id', 'id')
            ->where('peer_type', 'peerChannel');
    }

    /** Participants of this channel. */
    public function participants(): HasMany
    {
        return $this->hasMany(TfChannelParticipant::class, 'channel_id', 'id');
    }
}
