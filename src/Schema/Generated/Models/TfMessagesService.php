<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Mirror\Models\ResolvesEntityPeer;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Eloquent model for the shipped mirror table `tf_messages_service`.
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
 * Relations declared on this table: 7.
 */
final class TfMessagesService extends TfMirrorModel
{
    use ResolvesEntityPeer;

    use AccountScoped;

    protected $table = 'tf_messages_service';

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
        'date' => 'int',
        'out' => 'boolean',
        'mentioned' => 'boolean',
        'media_unread' => 'boolean',
        'reactions_are_possible' => 'boolean',
        'silent' => 'boolean',
        'post' => 'boolean',
        'legacy' => 'boolean',
    ];

    /** Declared foreign key (1:N): `tf_messages_service_action`(account_id, peer_type, peer_id, id) -> `tf_messages_service`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesServiceAction(): HasMany
    {
        return $this->hasMany(TfMessagesServiceAction::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_service_from_id`(account_id, peer_type, peer_id, id) -> `tf_messages_service`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesServiceFromId(): HasMany
    {
        return $this->hasMany(TfMessagesServiceFromId::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_service_reactions`(account_id, peer_type, peer_id, id) -> `tf_messages_service`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesServiceReactions(): HasMany
    {
        return $this->hasMany(TfMessagesServiceReactions::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_service_reply_to`(account_id, peer_type, peer_id, id) -> `tf_messages_service`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesServiceReplyTo(): HasMany
    {
        return $this->hasMany(TfMessagesServiceReplyTo::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_service_saved_peer_id`(account_id, peer_type, peer_id, id) -> `tf_messages_service`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesServiceSavedPeerId(): HasMany
    {
        return $this->hasMany(TfMessagesServiceSavedPeerId::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_messages_service_ttl_period`(account_id, peer_type, peer_id, id) -> `tf_messages_service`(account_id, peer_type, peer_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messagesServiceTtlPeriod(): HasMany
    {
        return $this->hasMany(TfMessagesServiceTtlPeriod::class, 'id', 'id');
    }

    /** HORIZONTAL, not a foreign key. `tf_messages_service`.`peer_type` is a `tinyint` discriminator and `tf_messages_service`.`peer_id` the Telegram peer id beside it, and one FK cannot branch on a type int (2026_09_14_299999:25-31). Resolves against `tf_users`, `tf_chats` or `tf_channels` by the discriminator, pinned to this account. Null when the discriminator is 0 — Telegram did not send a peer — and RAISES on a discriminator outside the enum rather than returning an empty relation, which is what a wrong key produces. */
    public function peer(): ?BelongsTo
    {
        return $this->entityPeer('peer_type', 'peer_id');
    }
}
