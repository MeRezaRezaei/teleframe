<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:1 child of tf_messages (message.media payload, MessageMedia union,
 * 19 ctors), constructor-discriminated flattened union.
 */
final class TfMessageMedia extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_messages_media';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'spoiler' => 'boolean',
        'live_photo' => 'boolean',
        'nopremium' => 'boolean',
        'video' => 'boolean',
        'round' => 'boolean',
        'voice' => 'boolean',
        'force_large_media' => 'boolean',
        'force_small_media' => 'boolean',
        'manual' => 'boolean',
        'safe' => 'boolean',
        'shipping_address_requested' => 'boolean',
        'test' => 'boolean',
        'via_mention' => 'boolean',
        'only_new_subscribers' => 'boolean',
        'winners_are_visible' => 'boolean',
        'refunded' => 'boolean',
        'rtmp_stream' => 'boolean',
        'ttl_seconds' => 'integer',
        'video_timestamp' => 'integer',
        'user_id' => 'integer',
        'receipt_msg_id' => 'integer',
        'total_amount' => 'integer',
        'heading' => 'integer',
        'period' => 'integer',
        'proximity_notification_radius' => 'integer',
        'value' => 'integer',
        'peer_type' => 'integer',
        'peer_id' => 'integer',
        'channel_id' => 'integer',
        'additional_peers_count' => 'integer',
        'launch_msg_id' => 'integer',
        'winners_count' => 'integer',
        'unclaimed_count' => 'integer',
        'quantity' => 'integer',
        'months' => 'integer',
        'stars' => 'integer',
        'until_date' => 'integer',
        'stars_amount' => 'integer',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(TfMessage::class, 'id', 'id');
    }
}
