<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Mirror\Models\ResolvesEntityPeer;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Eloquent model for the shipped mirror table `tf_messages_service_action`.
 *
 * Columns, casts and relations are REFLECTED from the executed shipped DDL, not
 * transcribed and not inferred from names — see ModelGenerator.
 *
 * A relation appears here in one of two shapes, and the docblock on each says
 * which: one where the engine DECLARES a foreign key, and one HORIZONTAL — an
 * entity-to-entity edge derived from an inline `{base}_type` + `{base}_id` peer
 * pair or from a catalog-declared scalar entity column, which no constraint can
 * express. See HorizontalEntityRelations for why those are not FKs.
 *
 * Relations declared on this table: 5.
 */
final class TfMessagesServiceAction extends TfMirrorModel
{
    use ResolvesEntityPeer;

    use AccountScoped;

    protected $table = 'tf_messages_service_action';

    protected $guarded = [];

    /** No autoincrement anywhere on the mirror: keys are Telegram-native. */
    public $incrementing = false;

    /**
     * Eloquent gets ONE scalar key. The real key is the composite (account_id, peer_type, peer_id, id),
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
        'peer_type' => 'int',
        'peer_id' => 'int',
        'constructor' => 'string',
        'recurring_init' => 'boolean',
        'recurring_used' => 'boolean',
        'attach_menu' => 'boolean',
        'from_request' => 'boolean',
        'video' => 'boolean',
        'title_missing' => 'boolean',
        'same' => 'boolean',
        'for_both' => 'boolean',
        'via_giveaway' => 'boolean',
        'unclaimed' => 'boolean',
        'name_hidden' => 'boolean',
        'saved' => 'boolean',
        'converted' => 'boolean',
        'upgraded' => 'boolean',
        'refunded' => 'boolean',
        'can_upgrade' => 'boolean',
        'prepaid_upgrade' => 'boolean',
        'upgrade_separate' => 'boolean',
        'auction_acquired' => 'boolean',
        'transferred' => 'boolean',
        'assigned' => 'boolean',
        'from_offer' => 'boolean',
        'craft' => 'boolean',
        'closed' => 'boolean',
        'hidden' => 'boolean',
        'rejected' => 'boolean',
        'balance_too_low' => 'boolean',
        'payer_initiated' => 'boolean',
        'expired' => 'boolean',
        'missed' => 'boolean',
        'active' => 'boolean',
        'broadcast_messages_allowed' => 'boolean',
        'accepted' => 'boolean',
        'declined' => 'boolean',
        'prev_value' => 'boolean',
        'new_value' => 'boolean',
        'title' => 'string',
        'user_id' => 'int',
        'inviter_id' => 'int',
        'channel_id' => 'int',
        'chat_id' => 'int',
        'game_id' => 'int',
        'score' => 'int',
        'currency' => 'string',
        'total_amount' => 'int',
        'payload' => 'string',
        'shipping_option_id' => 'string',
        'subscription_until_date' => 'int',
        'invoice_slug' => 'string',
        'call_id' => 'int',
        'reason' => 'string',
        'duration' => 'int',
        'message' => 'string',
        'domain' => 'string',
        'from_id_type' => 'int',
        'from_id_id' => 'int',
        'to_id_type' => 'int',
        'to_id_id' => 'int',
        'distance' => 'int',
        'period' => 'int',
        'auto_setting_from' => 'int',
        'schedule_date' => 'int',
        'text' => 'string',
        'data' => 'string',
        'days' => 'int',
        'crypto_currency' => 'string',
        'crypto_amount' => 'int',
        'icon_color' => 'int',
        'icon_emoji_id' => 'int',
        'button_id' => 'int',
        'slug' => 'string',
        'stars' => 'int',
        'boost_peer_type' => 'int',
        'boost_peer_id' => 'int',
        'winners_count' => 'int',
        'unclaimed_count' => 'int',
        'boosts' => 'int',
        'transaction_id' => 'string',
        'convert_stars' => 'int',
        'upgrade_msg_id' => 'int',
        'upgrade_stars' => 'int',
        'prepaid_upgrade_hash' => 'string',
        'gift_msg_id' => 'int',
        'gift_num' => 'int',
        'can_export_at' => 'int',
        'transfer_stars' => 'int',
        'can_transfer_at' => 'int',
        'can_resell_at' => 'int',
        'drop_original_details_stars' => 'int',
        'can_craft_at' => 'int',
        'count' => 'int',
        'reject_comment' => 'string',
        'expires_at' => 'int',
        'new_creator_id' => 'int',
        'bot_id' => 'int',
        'months' => 'int',
    ];

    /** HORIZONTAL, not a foreign key. `tf_messages_service_action`.`boost_peer_type` is a `tinyint` discriminator and `tf_messages_service_action`.`boost_peer_id` the Telegram peer id beside it, and one FK cannot branch on a type int (2026_09_14_299999:25-31). Resolves against `tf_users`, `tf_chats` or `tf_channels` by the discriminator, pinned to this account. Null when the discriminator is 0 — Telegram did not send a peer — and RAISES on a discriminator outside the enum rather than returning an empty relation, which is what a wrong key produces. */
    public function boostPeer(): ?BelongsTo
    {
        return $this->entityPeer('boost_peer_type', 'boost_peer_id');
    }

    /** HORIZONTAL, not a foreign key. `tf_messages_service_action`.`from_id_type` is a `tinyint` discriminator and `tf_messages_service_action`.`from_id_id` the Telegram peer id beside it, and one FK cannot branch on a type int (2026_09_14_299999:25-31). Resolves against `tf_users` by the discriminator, pinned to this account. Null when the discriminator is 0 — Telegram did not send a peer — and RAISES on a discriminator outside the enum rather than returning an empty relation, which is what a wrong key produces. */
    public function fromId(): ?BelongsTo
    {
        return $this->entityPeer('from_id_type', 'from_id_id');
    }

    /** Declared foreign key: `tf_messages_service_action`(account_id, peer_type, peer_id, id) -> `tf_messages_service`(account_id, peer_type, peer_id, id). Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesService(): BelongsTo
    {
        return $this->belongsTo(TfMessagesService::class, 'id', 'id');
    }

    /** HORIZONTAL, not a foreign key. `tf_messages_service_action`.`peer_type` is a `tinyint` discriminator and `tf_messages_service_action`.`peer_id` the Telegram peer id beside it, and one FK cannot branch on a type int (2026_09_14_299999:25-31). Resolves against `tf_users`, `tf_chats` or `tf_channels` by the discriminator, pinned to this account. Null when the discriminator is 0 — Telegram did not send a peer — and RAISES on a discriminator outside the enum rather than returning an empty relation, which is what a wrong key produces. */
    public function peer(): ?BelongsTo
    {
        return $this->entityPeer('peer_type', 'peer_id');
    }

    /** HORIZONTAL, not a foreign key. `tf_messages_service_action`.`to_id_type` is a `tinyint` discriminator and `tf_messages_service_action`.`to_id_id` the Telegram peer id beside it, and one FK cannot branch on a type int (2026_09_14_299999:25-31). Resolves against `tf_users` by the discriminator, pinned to this account. Null when the discriminator is 0 — Telegram did not send a peer — and RAISES on a discriminator outside the enum rather than returning an empty relation, which is what a wrong key produces. */
    public function toId(): ?BelongsTo
    {
        return $this->entityPeer('to_id_type', 'to_id_id');
    }
}
