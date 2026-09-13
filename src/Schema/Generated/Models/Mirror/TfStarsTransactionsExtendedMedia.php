<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfStarsTransactionsExtendedMedia extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_stars_transactions_extended_media';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'spoiler' => 'boolean',
        'live_photo' => 'boolean',
        'ttl_seconds' => 'integer',
        'user_id' => 'integer',
        'nopremium' => 'boolean',
        'video' => 'boolean',
        'round' => 'boolean',
        'voice' => 'boolean',
        'video_timestamp' => 'integer',
        'force_large_media' => 'boolean',
        'force_small_media' => 'boolean',
        'manual' => 'boolean',
        'safe' => 'boolean',
        'shipping_address_requested' => 'boolean',
        'test' => 'boolean',
        'receipt_msg_id' => 'integer',
        'total_amount' => 'integer',
        'heading' => 'integer',
        'period' => 'integer',
        'proximity_notification_radius' => 'integer',
        'value' => 'integer',
        'via_mention' => 'boolean',
        'peer_id' => 'integer',
        'only_new_subscribers' => 'boolean',
        'winners_are_visible' => 'boolean',
        'quantity' => 'integer',
        'months' => 'integer',
        'stars' => 'integer',
        'until_date' => 'integer',
        'refunded' => 'boolean',
        'channel_id' => 'integer',
        'additional_peers_count' => 'integer',
        'launch_msg_id' => 'integer',
        'winners_count' => 'integer',
        'unclaimed_count' => 'integer',
        'stars_amount' => 'integer',
        'rtmp_stream' => 'boolean',
    ];
}
