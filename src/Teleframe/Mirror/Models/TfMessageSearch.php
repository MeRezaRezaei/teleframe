<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * tf_message_search — the DERIVED, REBUILDABLE inverted index over message
 * text. Not a Telegram fact: it is rebuilt from tf_messages whenever the
 * content changes, and its read side joins candidate ids back onto
 * tf_messages by (account_id, peer_type, peer_id, id) — the message's own
 * peer-scoped key, because Telegram numbers message ids PER CHAT, so
 * (account_id, id) alone names a different dialog's message.
 *
 * Written by MessageSearchIndexer::index() (upserts
 * (account_id, token, peer_type, peer_id, id)) and pruned by its forget()
 * (deletes by the peer-scoped message key); read by MessageSearchQuery.
 * Deliberately no FK to tf_messages — a FK here would make every message
 * delete pay for a cascade across the largest table in the schema, so
 * referential integrity is the app layer's job (SafeDelete).
 *
 * KEY: the DDL primary key is COMPOSITE (account_id, token, peer_type, peer_id,
 * id), named tf_message_search_pk (2026_09_29_000300), with a SECONDARY
 * (account_id, peer_type, peer_id, id) index — tf_message_search_message_idx —
 * carrying the whole peer-scoped message key, because that is what `index()`'s
 * and `forget()`'s delete address and it is NOT a prefix of the primary key.
 * Eloquent has one key slot, so `account_id` is the surrogate — the same
 * convention the existing composite-PK mirror roots already use (TfDialog,
 * TfUpdate, TfQuickReply, TfSavedDialog). It is a poor discriminator on its
 * own: many tokens per account. Composite-key behaviour is inherently
 * PARTIAL — getKey() and find() are ambiguous, so address a row by its natural
 * (account_id, token, peer_type, peer_id, id), or query by the indexed
 * peer-scoped pair.
 *
 * The peer legs sit AFTER `token` in the primary key, not before it, and that
 * order is load-bearing: `MessageSearchQuery` is account-wide (it does not
 * constrain the peer), so `token` must sit immediately after `account_id` for
 * the prefix range scan to stay contiguous. Measured, and asserted by
 * `MessageSearchPlanTest::test_a_wider_index_would_not_change_the_plan` — moving
 * the peer legs ahead of the token costs the plan a temp B-tree for ORDER BY.
 *
 * No autoincrement and NO timestamps: the migration states the curated dial is
 * deliberately timestamp-free, and a timestamp on a rebuildable index row
 * would be actively misleading (2026_09_29_000300:79-88). TfMirrorModel's
 * $timestamps = false is therefore correct here as inherited.
 *
 * @property int $account_id
 * @property string $token
 * @property int $peer_type the enclosing message's peer discriminator: 1=user, 2=chat, 3=channel
 * @property int $peer_id the enclosing message's peer id (signed)
 * @property int $id
 */
final class TfMessageSearch extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_message_search';

    /**
     * Eloquent key surrogate: the leading column of the real composite key.
     * A poor discriminator alone (many tokens per account) — see the class
     * docblock.
     *
     * ADDRESS ROWS BY THE WHOLE KEY, MEASURED FROM THE DDL:
     * (account_id, token, peer_type, peer_id, id)
     * (2026_09_29_000300_create_tf_message_search_table.php:130). The two
     * earlier revisions of this comment said (account_id, token, id) and then
     * (account_id, token, peer_id, id); both are wrong, and both were wrong in
     * the direction that matters — they name a key the writer does NOT use, so
     * a reader reasoning about which rows a delete or an upsert addresses
     * reaches the wrong address. `token` sits immediately after `account_id`
     * on purpose (the prefix range scan must stay contiguous), which is why the
     * message's own peer-scoped key is NOT a prefix of this one; the writer
     * addresses the peer-scoped slice through the separate
     * `tf_message_search_message_idx` index, not through the primary key.
     */
    protected $primaryKey = 'account_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
    ];
}
