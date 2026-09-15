<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:N extended_media child — Vector<MessageMedia>, one row per vector slot.
 * Same flat union as the messages domain tf_messages_media.
 */
final class TfStarsTransactionExtendedMedia extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_stars_transactions_extended_media';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'position' => 'int',
        'ttl_seconds' => 'int',
        'video_timestamp' => 'int',
        'user_id' => 'int',
        'receipt_msg_id' => 'int',
        'total_amount' => 'int',
        'heading' => 'int',
        'period' => 'int',
        'proximity_notification_radius' => 'int',
        'value' => 'int',
        'peer_type' => 'int',
        'peer_id' => 'int',
        'channel_id' => 'int',
        'additional_peers_count' => 'int',
        'launch_msg_id' => 'int',
        'winners_count' => 'int',
        'unclaimed_count' => 'int',
        'quantity' => 'int',
        'months' => 'int',
        'stars' => 'int',
        'until_date' => 'int',
        'stars_amount' => 'int',
        'spoiler' => 'bool',
        'live_photo' => 'bool',
        'nopremium' => 'bool',
        'video' => 'bool',
        'round' => 'bool',
        'voice' => 'bool',
        'force_large_media' => 'bool',
        'force_small_media' => 'bool',
        'manual' => 'bool',
        'safe' => 'bool',
        'shipping_address_requested' => 'bool',
        'test' => 'bool',
        'via_mention' => 'bool',
        'only_new_subscribers' => 'bool',
        'winners_are_visible' => 'bool',
        'refunded' => 'bool',
        'rtmp_stream' => 'bool',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(TfStarsTransaction::class, 'id', 'id');
    }
}
