<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Mirror\Models\ResolvesEntityPeer;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Eloquent model for the shipped mirror table `tf_messages_fwd_from`.
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
final class TfMessagesFwdFrom extends TfMirrorModel
{
    use ResolvesEntityPeer;

    use AccountScoped;

    protected $table = 'tf_messages_fwd_from';

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
        'imported' => 'boolean',
        'saved_out' => 'boolean',
        'from_id_type' => 'int',
        'from_id_id' => 'int',
        'from_name' => 'string',
        'date' => 'int',
        'channel_post' => 'int',
        'post_author' => 'string',
        'saved_from_peer_type' => 'int',
        'saved_from_peer_id' => 'int',
        'saved_from_msg_id' => 'int',
        'saved_from_id_type' => 'int',
        'saved_from_id_id' => 'int',
        'saved_from_name' => 'string',
        'saved_date' => 'int',
        'psa_type' => 'string',
    ];

    /** HORIZONTAL, not a foreign key. `tf_messages_fwd_from`.`from_id_type` is a `tinyint` discriminator and `tf_messages_fwd_from`.`from_id_id` the Telegram peer id beside it, and one FK cannot branch on a type int (2026_09_14_299999:25-31). Resolves against `tf_users` by the discriminator, pinned to this account. Null when the discriminator is 0 — Telegram did not send a peer — and RAISES on a discriminator outside the enum rather than returning an empty relation, which is what a wrong key produces. */
    public function fromId(): ?BelongsTo
    {
        return $this->entityPeer('from_id_type', 'from_id_id');
    }

    /** Declared foreign key: `tf_messages_fwd_from`(account_id, peer_type, peer_id, id) -> `tf_messages`(account_id, peer_type, peer_id, id). Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function messages(): BelongsTo
    {
        return $this->belongsTo(TlMessage::class, 'id', 'id');
    }

    /** HORIZONTAL, not a foreign key. `tf_messages_fwd_from`.`peer_type` is a `tinyint` discriminator and `tf_messages_fwd_from`.`peer_id` the Telegram peer id beside it, and one FK cannot branch on a type int (2026_09_14_299999:25-31). Resolves against `tf_users`, `tf_chats` or `tf_channels` by the discriminator, pinned to this account. Null when the discriminator is 0 — Telegram did not send a peer — and RAISES on a discriminator outside the enum rather than returning an empty relation, which is what a wrong key produces. */
    public function peer(): ?BelongsTo
    {
        return $this->entityPeer('peer_type', 'peer_id');
    }

    /** HORIZONTAL, not a foreign key. `tf_messages_fwd_from`.`saved_from_id_type` is a `tinyint` discriminator and `tf_messages_fwd_from`.`saved_from_id_id` the Telegram peer id beside it, and one FK cannot branch on a type int (2026_09_14_299999:25-31). Resolves against `tf_users` by the discriminator, pinned to this account. Null when the discriminator is 0 — Telegram did not send a peer — and RAISES on a discriminator outside the enum rather than returning an empty relation, which is what a wrong key produces. */
    public function savedFromId(): ?BelongsTo
    {
        return $this->entityPeer('saved_from_id_type', 'saved_from_id_id');
    }

    /** HORIZONTAL, not a foreign key. `tf_messages_fwd_from`.`saved_from_peer_type` is a `tinyint` discriminator and `tf_messages_fwd_from`.`saved_from_peer_id` the Telegram peer id beside it, and one FK cannot branch on a type int (2026_09_14_299999:25-31). Resolves against `tf_users`, `tf_chats` or `tf_channels` by the discriminator, pinned to this account. Null when the discriminator is 0 — Telegram did not send a peer — and RAISES on a discriminator outside the enum rather than returning an empty relation, which is what a wrong key produces. */
    public function savedFromPeer(): ?BelongsTo
    {
        return $this->entityPeer('saved_from_peer_type', 'saved_from_peer_id');
    }
}
