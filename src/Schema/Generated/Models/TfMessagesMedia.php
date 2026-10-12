<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Eloquent model for the shipped mirror table `tf_messages_media`.
 *
 * Columns, casts and relations are REFLECTED from the executed shipped DDL, not
 * transcribed and not inferred from names — see ModelGenerator. A relation
 * appears here only where the engine DECLARES a foreign key.
 *
 * Relations declared on this table: 1.
 */
final class TfMessagesMedia extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_messages_media';

    protected $guarded = [];

    /** No autoincrement anywhere on the mirror: keys are Telegram-native. */
    public $incrementing = false;

    /**
     * Eloquent gets ONE scalar key. The real key is the composite (account_id, message_peer_type, message_peer_id, id),
     * which the database enforces; `id` is total only WITHIN an account, and
     * AccountScope guarantees that. So `find()` is account-scoped by construction:
     * reach for a relation or an explicit where, never a bare find().
     */
    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * Derived from the type the ENGINE reports for each column — never from a
     * docblock, and never from SQLite, whose affinity collapses integer(),
     * bigInteger() and tinyInteger() alike and drops width and signedness.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'account_id' => 'int',
        'id' => 'int',
        'message_peer_type' => 'int',
        'message_peer_id' => 'int',
        'constructor' => 'string',
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
        'ttl_seconds' => 'int',
        'video_timestamp' => 'int',
        'phone_number' => 'string',
        'first_name' => 'string',
        'last_name' => 'string',
        'vcard' => 'string',
        'user_id' => 'int',
        'title' => 'string',
        'address' => 'string',
        'provider' => 'string',
        'venue_id' => 'string',
        'venue_type' => 'string',
        'description' => 'string',
        'receipt_msg_id' => 'int',
        'currency' => 'string',
        'total_amount' => 'int',
        'start_param' => 'string',
        'heading' => 'int',
        'period' => 'int',
        'proximity_notification_radius' => 'int',
        'value' => 'int',
        'emoticon' => 'string',
        'peer_type' => 'int',
        'peer_id' => 'int',
        'channel_id' => 'int',
        'additional_peers_count' => 'int',
        'launch_msg_id' => 'int',
        'winners_count' => 'int',
        'unclaimed_count' => 'int',
        'prize_description' => 'string',
        'quantity' => 'int',
        'months' => 'int',
        'stars' => 'int',
        'until_date' => 'int',
        'stars_amount' => 'int',
    ];

    /** Declared foreign key: `tf_messages_media`(account_id, message_peer_type, message_peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id). Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messages(): BelongsTo
    {
        return $this->belongsTo(TlMessage::class, 'id', 'id');
    }
}
