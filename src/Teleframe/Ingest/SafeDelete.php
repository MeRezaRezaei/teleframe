<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\Events\MessagesDeleted;

/**
 * TDLib-style explicit delete operations: remove messages from tf_messages
 * by their Telegram message IDs. Fires MessagesDeleted so consumers
 * (Centrifugo, Redis bus, handler pipeline) can react to deletions.
 *
 * Curated dial: tf_messages is keyed (account_id, id) — the Telegram
 * message id IS the `id` column (NATURAL key, not a surrogate). The legacy
 * extracted `message_id` column is gone, so deletes match on `id`.
 *
 * The delete is COMPLETE or NO-OP, never partial: both message parents, all
 * 38 `tf_messages*` child tables, and the derived search tokens go together.
 * Purging only the parent would leave a message whose reactions, replies and
 * views rows are still readable and still joinable to nothing — the same
 * silent-wrong-rows defect one table up.
 */
final class SafeDelete
{
    /**
     * Every `tf_messages*` table that is a CHILD of a message row, keyed
     * (account_id, id) exactly like its parent.
     *
     * An explicit purge list, not a set of cascade foreign keys, and the
     * reason is evidence rather than taste:
     *
     *  - The curated dial's FK philosophy is already written down in
     *    2026_09_14_299999_create_tf_foreign_keys.php: only 3 FKs ship
     *    across 40 `tf_messages*` tables, and its header calls the omitted
     *    refs "deliberate soft routing links (the row may be mirror-id or
     *    not yet mirrored); never FK'd". A mirror is fed out of order, so a
     *    child row may legitimately be written before its parent exists;
     *    promoting 38 of those to enforced FKs converts a tolerated
     *    out-of-order write into a hard raise on Postgres.
     *  - Adding an FK to a table that already holds violating rows FAILS the
     *    whole `migrate` on both MySQL and Postgres. Any host that has run
     *    the mirror seam for a while may hold such rows, and the cure would
     *    then be a data-cleanup migration — strictly more dangerous than the
     *    silent orphan this goal removes.
     *  - `tf_message_search` cannot carry the FK at all: it is keyed
     *    (account_id, token, peer_type, peer_id, id) with a 255-char indexed
     *    token — token IMMEDIATELY after account_id and NOT after the peer legs,
     *    deliberately, so the prefix range scan stays contiguous
     *    (2026_09_29_000300_create_tf_message_search_table.php:130; the plan
     *    measurement and its pin are in TfMessageSearch's class docblock) —
     *    so the message's own peer-scoped key is not even a PREFIX of it, and
     *    its purge is already owned by MessageSearchIndexer::forget(). A
     *    cascade is not available for the one child that search actually reads.
     *  - The precedent for this exact shape already exists in this package:
     *    UpdateIngestor::deleteUpdateChildren() purges 17 `tf_updates_*`
     *    children with a PHP list, for the same reason.
     *
     * `tf_messages_entities` DOES cascade (299999) and is listed anyway: a
     * cascade is a property of the host's FK enforcement, which sqlite leaves
     * off by default, and a purge that is correct only when enforcement happens
     * to be on is not correct.
     *
     * ## `tf_messages_media` IS DELIBERATELY NOT IN THIS LIST
     *
     * It is the one message table that does NOT spell the enclosing message's
     * scope `peer_type`/`peer_id`: those two columns already belong to a peer
     * carried INSIDE the `MessageMedia` union payload, so the message's own
     * scope is the PREFIXED pair `message_peer_type`/`message_peer_id`
     * (`2026_09_14_200012_create_tf_message_medias_tables.php:28-36`). Listing
     * it here made the shared predicate delete it on the WRONG columns — the
     * media payload's own peer — which silently destroyed the media rows of
     * every OTHER dialog whose payload happened to carry that peer. That is
     * precisely the cross-dialog damage the peer-scoped predicate exists to
     * prevent, reintroduced one table down. It is deleted once, correctly, by
     * the explicit prefixed-predicate call after this loop.
     *
     * @var list<string>
     */
    private const MESSAGE_CHILD_TABLES = [
        'tf_messages_edit_date',
        'tf_messages_effect',
        'tf_messages_entities',
        'tf_messages_factcheck',
        'tf_messages_forwards',
        'tf_messages_from_boosts_applied',
        'tf_messages_from_id',
        'tf_messages_from_rank',
        'tf_messages_fwd_from',
        'tf_messages_grouped_id',
        'tf_messages_guestchat_via_from',
        'tf_messages_paid_message_stars',
        'tf_messages_post_author',
        'tf_messages_quick_reply_shortcut_id',
        'tf_messages_reactions',
        'tf_messages_replies',
        'tf_messages_reply_markup',
        'tf_messages_reply_markup_rows',
        'tf_messages_reply_markup_rows_buttons',
        'tf_messages_reply_to',
        'tf_messages_report_delivery_until_date',
        'tf_messages_restriction_reason',
        'tf_messages_rich_message',
        'tf_messages_saved_peer_id',
        'tf_messages_schedule_repeat_period',
        'tf_messages_service_action',
        'tf_messages_service_from_id',
        'tf_messages_service_reactions',
        'tf_messages_service_reply_to',
        'tf_messages_service_saved_peer_id',
        'tf_messages_service_ttl_period',
        'tf_messages_suggested_post',
        'tf_messages_summary_from_language',
        'tf_messages_ttl_period',
        'tf_messages_via_bot_id',
        'tf_messages_via_business_bot_id',
        'tf_messages_views',
    ];

    /**
     * The two PARENT message tables, both keyed (account_id, id).
     *
     * `message` and `messageService` are two curated surfaces over one id
     * space, so a `updateDeleteMessages` id can name a row in either. Purging
     * only `tf_messages` would leave every deleted service message behind on
     * the same defect this class exists to remove.
     *
     * @var list<string>
     */
    private const MESSAGE_PARENT_TABLES = [
        'tf_messages',
        'tf_messages_service',
    ];

    public function __construct(
        private readonly ?Dispatcher $events = null,
    ) {}

    /**
     * Delete messages by their Telegram IDs.
     *
     * A never-mirrored id is a NO-OP, not an error: Telegram deletes messages
     * this account never received (a `updateDeleteMessages` can arrive for a
     * message that was filtered out of the update gap, or that a previous
     * `messageEmpty` never placed). `deleteMessages` is called from inside the
     * ingest transaction, so raising on an id that was simply never stored
     * would roll back the update's own fact row and turn a routine delete into
     * a poison pill that repeats on every replay. The delete simply removes
     * zero rows and the caller moves on.
     *
     * Order inside is fixed: parents first, then the ~38 child tables, then
     * the search tokens. On a host with FK enforcement the parent delete
     * already cascaded the two cascading children, so their explicit delete is
     * a no-op; on a host without enforcement it is the only thing that removes
     * them. Either way the row and all of its parts end up gone.
     *
     * ## WHY THE PEER IS PART OF THE DELETE PREDICATE
     *
     * Telegram numbers messages PER CHAT (see
     * `2026_09_14_200010_create_tf_messages_tables.php`), so `deleteMessages`
     * by (account_id, id) does not delete "that message" — it deletes message
     * `id` in EVERY dialog of the account. `updateDeleteMessages#a20db0e5`
     * carries `messages:Vector<int>` together with a `peer:Peer`, and
     * `updateDeleteChannelMessages#c32a5e12b` carries `channel_id`: both say
     * which chat the ids belong to, and a delete that ignored the peer would
     * quietly destroy unrelated dialogs' history — the same class of silent
     * cross-dialog damage as the key this change widened.
     *
     * WHEN THE PEER IS UNKNOWN the delete stays account-wide. That is
     * deliberately the OVER-broad direction: it can remove rows that should
     * have survived, never rows that should have gone, and it preserves the
     * pre-2026-10-03 behaviour for a caller that has no peer to offer. Every
     * in-repo caller that HAS the peer on the wire now passes it.
     *
     * @param  list<int>  $messageIds
     * @return int Number of PARENT rows actually deleted (0 for a never-mirrored id)
     */
    public function deleteMessages(int $accountId, array $messageIds, ?int $peerId = null, ?int $peerType = null): int
    {
        if ($messageIds === []) {
            return 0;
        }

        // ONE predicate, applied to parents, children and tokens alike, so the
        // three can never disagree about WHICH rows are gone. The peer column
        // NAMES are a parameter because `tf_messages_media` is the one message
        // table that spells the enclosing message's scope `message_peer_type` /
        // `message_peer_id` — it already owns `peer_type`/`peer_id` for a peer
        // inside its own payload. A missing peer must WIDEN the predicate, never
        // narrow it to a peer nobody named, which is why the clauses are
        // conditional rather than always present.
        $scope = fn ($query, string $typeCol = 'peer_type', string $idCol = 'peer_id') => $query
            ->where('account_id', $accountId)
            ->when($peerId !== null, fn ($q) => $q->where($idCol, $peerId))
            ->when($peerType !== null, fn ($q) => $q->where($typeCol, $peerType))
            ->whereIn('id', $messageIds);

        $deleted = 0;
        foreach (self::MESSAGE_PARENT_TABLES as $parent) {
            $deleted += $scope(DB::table($parent))->delete();
        }

        // The child tables here are keyed by the SAME peer-scoped key as the
        // parents, spelled `peer_type`/`peer_id` (Task 2.2 widened all of them
        // together), so the one predicate reaches every part of the message.
        foreach (self::MESSAGE_CHILD_TABLES as $child) {
            $scope(DB::table($child))->delete();
        }

        // `tf_messages_media` is the ONE exception and is deliberately absent
        // from the loop above: it spells the enclosing message's scope
        // `message_peer_type`/`message_peer_id`, because its own `peer_type`/
        // `peer_id` carry a peer inside the `MessageMedia` payload. Applying the
        // short-name predicate to it would delete the media rows of every OTHER
        // dialog whose payload happens to carry the same peer — the exact
        // cross-dialog damage this method's peer scoping exists to prevent.
        // Deleted ONCE here, on the declared FK columns.
        $scope(DB::table('tf_messages_media'), 'message_peer_type', 'message_peer_id')->delete();

        // Drop the derived search rows too. They are an index over a row that
        // no longer exists, so leaving them behind would let a deleted
        // message's id keep matching a search and join to nothing. Done for the
        // whole $messageIds set rather than the $deleted count, because the
        // token rows are keyed the same way and the two must not disagree about
        // which ids are gone — and because a token row can outlive its message
        // through any of the paths that write tokens without the row.
        //
        // The peer-scoped path goes through `MessageSearchIndexer::forget()`,
        // which is the writer's own inverse. The UNKNOWN-peer path cannot: it
        // would address the 0/0 index rows, which do not exist, and leave the
        // real tokens behind — turning a delete into a no-op in the one
        // direction that must not fail. So it deletes through the same account +
        // id predicate as everything else, keeping the over-breadth deliberate
        // and consistent across all three surfaces.
        if ($peerId !== null || $peerType !== null) {
            (new MessageSearchIndexer)->forgetMany($accountId, $messageIds, $peerType, $peerId);
        } else {
            DB::table('tf_message_search')
                ->where('account_id', $accountId)
                ->whereIn('id', $messageIds)
                ->delete();
        }

        if ($deleted > 0) {
            $this->events?->dispatch(new MessagesDeleted(
                $accountId,
                $messageIds,
                $peerId,
            ));
        }

        return $deleted;
    }
}
