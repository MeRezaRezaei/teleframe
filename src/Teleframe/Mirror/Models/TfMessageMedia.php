<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:1 child of tf_messages (message.media payload, MessageMedia union,
 * 19 ctors), constructor-discriminated flattened union.
 *
 * ## WHY THIS TABLE CARRIES TWO PAIRS OF PEER COLUMNS
 *
 * `peer_type`/`peer_id` (both `->default(0)`) are a peer INSIDE the media
 * payload — several `MessageMedia` constructors ship one
 * (`messageMediaWebPage.peer`, `messageMediaStory.peer`, …). They belong to the
 * payload, NOT to the enclosing message, and they are 0 for the ordinary
 * messageMediaPhoto / messageMediaDocument rows.
 *
 * `message_peer_type`/`message_peer_id` are the ENCLOSING MESSAGE's scope, and
 * are what the FK declares (`2026_09_14_299999_create_tf_foreign_keys.php:81-85`)
 * against the parent's unprefixed `peer_type`/`peer_id`. Every relation on this
 * model that crosses into the message scope must use the PREFIXED pair.
 *
 * @property int $message_peer_type the enclosing message's peer discriminator (1=user, 2=chat, 3=channel)
 * @property int $message_peer_id the enclosing message's peer id (signed)
 * @property int $peer_type a peer INSIDE the media payload; 0 for most rows
 * @property int $peer_id that payload peer's id; 0 for most rows
 */
final class TfMessageMedia extends TfChildModel
{
    use PinsDeclaredForeignKeyLegs;

    use AccountScoped;
    use ResolvesMessagePeerScope;

    protected $table = 'tf_messages_media';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'message_peer_type' => 'integer',
        'message_peer_id' => 'integer',
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

    /**
     * The ENCLOSING MESSAGE, joined on the message's FULL peer-scoped key.
     *
     * NOT `('id','id')` any more. Telegram numbers messages PER CHAT — 136 of
     * one real account's dialogs hold a message with id=1 — so `id` is unique
     * only WITHIN (account_id, peer_type, peer_id). A belongsTo on `id` alone
     * resolved to whichever dialog happened to be written last, which is the
     * same cross-dialog collapse the primary key was widened to end, one level
     * up in the ORM. `account_id` is still supplied by the AccountScope global
     * scope, exactly as before.
     *
     * The peer legs constrain the PARENT's `peer_type`/`peer_id` — a belongsTo
     * filters `tf_messages` — but take their VALUES from this table's PREFIXED
     * `message_peer_*` columns, because this table's own `peer_type`/`peer_id`
     * are the media PAYLOAD's peer (see the class docblock and
     * `2026_09_14_200012:28-36`). Pinning the payload peer here — as the code
     * did until 2026-10-03 — compared 0 (every ordinary media row) against the
     * message's peer, so the relation resolved to NOTHING rather than to the
     * wrong message. `RelationIntegrityTest::
     * test_the_media_relation_resolves_to_its_seeded_message` proves it resolves
     * now, against a real row.
     */
    public function message(): BelongsTo
    {
        return $this->pinMessagePeerScopeBelongsTo($this->belongsTo(TfMessage::class, 'id', 'id'));
    }
}
