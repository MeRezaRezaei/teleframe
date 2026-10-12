<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * NF5 mirror — messages domain (curated dial, Task 3 of the reverse-engineering plan).
 *
 * Hand-authored against TL_telegram_v227.tl (Message union) + the ingest
 * write-path contract (MirrorFactDecomposer: parent rows carry account_id +
 * constructor; children are tf_{parent}_{fact} keyed (account_id, peer_type,
 * peer_id, id[, position[, parent_position]])).
 *
 * - Content messages ↔ service messages split into tf_messages subject-row
 *   (messageEmpty / message) and tf_messages_service (messageService), the
 *   pair MessagesUnion reads. They share the (account_id, peer_type, peer_id,
 *   id) KEY but NOT their columns — 22 vs 13, sharing 12 — because
 *   MirrorCtorSplitter intersects the catalog with each constructor's params.
 *   MessagesUnion projects explicitly and pads the missing side with NULL; a
 *   bare `select *` here is an arity error.
 * - Peer shapes are inline {field}_type / {field}_id pairs (1=user, 2=chat,
 *   3=channel per PeerShapeTool). Signed BIGINT for peer ids: telegram chat /
 *   channel ids are negative — the old generated mirror stored them UNSIGNED
 *   (data corruption for < 0 ids); this surface fixes that.
 * - flags.?true → BOOLEAN NOT NULL DEFAULT FALSE. No nullable, no json (bytes
 *   → lowercase hex TEXT), no auto-increment.
 * - messageEmpty has no payload beyond id, so peer/date/message carry type
 *   defaults; row existence = fact existence.
 * - FK constraints are NOT declared here — cross-domain wiring (account →
 *   telegram_accounts, peer_id → tf_users/tf_chats/tf_channels, media →
 *   tf_documents/tf_photos) is the Task-8 299999 migration after the
 *   worktrees merge.
 *
 * ## WHY EVERY TABLE IN THIS FILE IS KEYED (account_id, peer_type, peer_id, id)
 *
 * **Telegram numbers messages PER CHAT.** `dialog.top_message` (TL 852) is the
 * highest message id in that dialog, and ids are dense per-dialog sequences
 * starting at 1, so `id=1`, `id=5` and `id=100` each exist in essentially every
 * dialog of a real account. Measured on the operator's live database: 136
 * dialogs carry a message with id=1, 135 with id=5, 135 with id=100;
 * sum(top_message) across those dialogs is 204,138,975 while max(top_message) is
 * only 38,826,515 — so only that many (account_id, id) key slots exist and
 * 165,312,562 messages (80.98%) would overwrite one another.
 *
 * The write path agrees with the wire. `messages.getHistory#4423e6c5` takes a
 * MANDATORY `peer:InputPeer` and an `offset_id` that is a peer-relative cursor,
 * and `message#7600b9d3` (schema/sources/TL_telegram_v227.tl:121) carries
 * `id:int` AND `peer_id:Peer` in the SAME constructor. If ids were globally
 * unique, `peer:` would be decoration. It is not decoration; it is half the
 * address.
 *
 * The failure mode this replaces is SILENT. `UpdateIngestor::writeMessage()`
 * upserts, so a collision was never an error: the second message's body, date
 * and flags silently REPLACED the first's, and a peer-scoped read (`where id <
 * N order by id desc` — what MessageQuery issues) then returned whichever
 * dialog wrote last. No SQLSTATE, no migrate-time signal, no startup signal.
 *
 * ## WHY THE ~32 CHILDREN CARRY THE PEER AND NOT JUST THE PARENT
 *
 * A child's key must be at least as SPECIFIC as its parent's, because a child
 * cannot exist without its parent (`decomposeChildren()` links them; there is
 * no orphan fact). Three shapes, one invariant:
 *
 *  - 1:1 children (`tf_messages_from_id`, `tf_messages_fwd_from`, …). "1:1"
 *    describes the CARDINALITY of message→child, not the child's identity. A
 *    child row is a FACT ABOUT one message, and once the parent's identity is
 *    four columns "the from_id of message 5" is ambiguous — there is one in
 *    every dialog. A child keyed (account_id, id) would still collapse peer A's
 *    author onto peer B's.
 *  - 1:N children (`tf_messages_restriction_reason`,
 *    `tf_messages_reply_markup_rows`, …). `position` disambiguates the
 *    elements, but WITHIN one message, i.e. within one (account_id, peer_type,
 *    peer_id, id). Element 0 of the entity vector of message 5 in dialog A is a
 *    different entity from element 0 in dialog B, and both sit at position 0. So
 *    position is necessary and NOT sufficient.
 *  - `tf_messages_reply_markup_rows_buttons` carries SIX coordinates for the
 *    same reason its own comment gives.
 *
 * Landing the parent alone would have been a NEW inconsistency rather than a
 * fix: a widened parent with 32 unwidened children produces children whose `id`
 * resolves to no parent row at all.
 *
 * ## THE PEER COLUMNS ARE NOT NULL WITH NO DEFAULT
 *
 * Every writer in this package supplies both halves — `UpdateIngestor::
 * writeMessage()` normalizes `payload['peer_id']` through `PeerShapeTool`, and
 * `MirrorFactDecomposer::fillPeerHalf()` does the same for the catalog-driven
 * path — so a default would buy nothing and cost the loud failure. A zero
 * default on a key column recreates exactly the 0/0 corruption fixed elsewhere
 * in this plan: the write lands, the row is unreadable, and nothing says why.
 * `tf_dialogs.peer_type` still carries `->default(0)`, and
 * `MirrorResolverKeyMatchesShippedDdlTest::test_a_peer_keyed_table_with_a_ddl_default_would_corrupt_silently_not_drop_loudly`
 * pins that contrast deliberately.
 *
 * ## OPERATOR NOTE (2026-10-02; failure mode CORRECTED 2026-10-04) — THE WIDENING DOES NOT APPLY TO AN EXISTING DB
 *
 * Everything above describes a table this file CREATES. It does not ALTER a
 * table that already exists: NO ALTER adds the peer columns to `tf_messages`
 * (the dial does carry 19 other `Schema::table(...)` sites — 8 FKs in 299999
 * and three app-owned ALTERs — but none on `tf_messages`) and no rebuild step.
 *
 * WHAT THE OPERATOR SEES — MEASURED 2026-10-04. This note previously claimed
 * the 80.98% silent-overwrite rate "continues, unchanged," until a rebuild.
 * FALSE: a pre-widening database has NO `peer_type`/`peer_id` columns at all,
 * so the mirror does not keep overwriting silently — it STOPS WRITING, LOUDLY:
 * `UpdateIngestor:409`'s `updateOrInsert` on `['account_id','peer_type',
 * 'peer_id','id']` raises pg `SQLSTATE 42703 … column "peer_type" does not
 * exist` on the existence probe, before any write, row intact. `SafeDelete`
 * (:194-220) and the search index fail identically — the old wording sent an
 * operator hunting silent corruption, not diagnosing a stopped mirror.
 *
 * An in-place widen is not merely omitted, it is IMPOSSIBLE as declared: the
 * peer columns are NOT NULL with no default, so an ALTER has no legal value
 * for the rows already there. A full-history RE-BACKFILL is therefore still
 * REQUIRED — drop the mirror tables and re-ingest from the wire. Operator-only.
 *
 * The rows that already collided under `(account_id, id)` before the widening are
 * UNRECOVERABLE. Also in the graph's "Honest limits" and in that spec's §7a.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->createMessages();
        $this->createMessageChildren();
        $this->createServiceMessages();
        $this->createServiceMessageChildren();
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_messages_service_ttl_period');
        Schema::dropIfExists('tf_messages_service_reactions');
        Schema::dropIfExists('tf_messages_service_reply_to');
        Schema::dropIfExists('tf_messages_service_saved_peer_id');
        Schema::dropIfExists('tf_messages_service_from_id');
        Schema::dropIfExists('tf_messages_service');
        Schema::dropIfExists('tf_messages_reply_markup_rows_buttons');
        Schema::dropIfExists('tf_messages_reply_markup_rows');
        Schema::dropIfExists('tf_messages_reply_markup');
        Schema::dropIfExists('tf_messages_suggested_post');
        Schema::dropIfExists('tf_messages_factcheck');
        Schema::dropIfExists('tf_messages_reactions');
        Schema::dropIfExists('tf_messages_restriction_reason');
        Schema::dropIfExists('tf_messages_summary_from_language');
        Schema::dropIfExists('tf_messages_schedule_repeat_period');
        Schema::dropIfExists('tf_messages_paid_message_stars');
        Schema::dropIfExists('tf_messages_report_delivery_until_date');
        Schema::dropIfExists('tf_messages_effect');
        Schema::dropIfExists('tf_messages_quick_reply_shortcut_id');
        Schema::dropIfExists('tf_messages_ttl_period');
        Schema::dropIfExists('tf_messages_post_author');
        Schema::dropIfExists('tf_messages_edit_date');
        Schema::dropIfExists('tf_messages_grouped_id');
        Schema::dropIfExists('tf_messages_replies');
        Schema::dropIfExists('tf_messages_forwards');
        Schema::dropIfExists('tf_messages_views');
        Schema::dropIfExists('tf_messages_reply_to');
        Schema::dropIfExists('tf_messages_guestchat_via_from');
        Schema::dropIfExists('tf_messages_via_business_bot_id');
        Schema::dropIfExists('tf_messages_via_bot_id');
        Schema::dropIfExists('tf_messages_fwd_from');
        Schema::dropIfExists('tf_messages_saved_peer_id');
        Schema::dropIfExists('tf_messages_from_rank');
        Schema::dropIfExists('tf_messages_from_boosts_applied');
        Schema::dropIfExists('tf_messages_from_id');
        Schema::dropIfExists('tf_messages');
    }

    private function createMessages(): void
    {
        Schema::create('tf_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // NOT NULL WITH NO DEFAULT, deliberately. Both halves are half of
            // this row's identity, and a default here would put the failure
            // back that the widened key just removed: a write that somehow
            // omitted the peer would land at (account_id, 0, 0, id) and be
            // indistinguishable from a real message in a peer nobody has.
            // `tf_dialogs.peer_type` still carries ->default(0) and
            // `MirrorResolverKeyMatchesShippedDdlTest` documents that as the
            // silent-corruption shape; this table is on the loud side, where a
            // dropped key column is a clue instead of a lie. Every writer in
            // this package supplies both halves —
            // `UpdateIngestor::writeMessage()` normalizes `payload['peer_id']`
            // through `PeerShapeTool`, and `MirrorFactDecomposer::fillPeerHalf()`
            // does the same for the catalog-driven path.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->text('constructor');
            $table->integer('date')->default(0);
            $table->text('message')->default(DB::raw("('')"));
            $table->boolean('out')->default(false);
            $table->boolean('mentioned')->default(false);
            $table->boolean('media_unread')->default(false);
            $table->boolean('silent')->default(false);
            $table->boolean('post')->default(false);
            $table->boolean('from_scheduled')->default(false);
            $table->boolean('legacy')->default(false);
            $table->boolean('edit_hide')->default(false);
            $table->boolean('pinned')->default(false);
            $table->boolean('noforwards')->default(false);
            $table->boolean('invert_media')->default(false);
            $table->boolean('offline')->default(false);
            $table->boolean('video_processing_pending')->default(false);
            $table->boolean('paid_suggested_post_stars')->default(false);
            $table->boolean('paid_suggested_post_ton')->default(false);

            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
            // NO SECOND INDEX, deliberately. This block used to carry
            // `$table->index(['account_id','peer_type','peer_id','id'])` for the
            // per-peer read path (MessageQuery: where('id','<',…)->orderByDesc
            // ('id') and its ascending twin). That column list IS now the
            // primary key, so a second B-tree over the same columns is pure
            // write amplification on the hottest table in the mirror — every
            // upsert maintaining two identical structures for no read benefit.
            // The per-peer read path is still served: a MySQL/InnoDB secondary
            // lookup walks the clustered PK. Asserted, not assumed, by
            // `MessagePeerScopedPrimaryKeyTest::test_the_per_peer_index_is_not_left_duplicated_by_the_primary_key`.
        });
    }

    private function createMessageChildren(): void
    {
        // from_id — inline peer pair child (Peer payload field).
        Schema::create('tf_messages_from_id', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->tinyInteger('from_id_type')->default(0);
            $table->bigInteger('from_id_id')->default(0);
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });

        Schema::create('tf_messages_from_boosts_applied', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->integer('from_boosts_applied');
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });

        Schema::create('tf_messages_from_rank', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->text('from_rank');
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });

        Schema::create('tf_messages_saved_peer_id', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->tinyInteger('saved_peer_id_type')->default(0);
            $table->bigInteger('saved_peer_id_id')->default(0);
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });

        // fwd_from — single-ctor MessageFwdHeader flattened 1:1.
        Schema::create('tf_messages_fwd_from', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->boolean('imported')->default(false);
            $table->boolean('saved_out')->default(false);
            $table->tinyInteger('from_id_type')->default(0);
            $table->bigInteger('from_id_id')->default(0);
            $table->text('from_name')->default(DB::raw("('')"));
            $table->integer('date');
            $table->integer('channel_post')->default(0);
            $table->text('post_author')->default(DB::raw("('')"));
            $table->tinyInteger('saved_from_peer_type')->default(0);
            $table->bigInteger('saved_from_peer_id')->default(0);
            $table->integer('saved_from_msg_id')->default(0);
            $table->tinyInteger('saved_from_id_type')->default(0);
            $table->bigInteger('saved_from_id_id')->default(0);
            $table->text('saved_from_name')->default(DB::raw("('')"));
            $table->integer('saved_date')->default(0);
            $table->text('psa_type')->default(DB::raw("('')"));
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });

        Schema::create('tf_messages_via_bot_id', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->bigInteger('via_bot_id');
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });

        Schema::create('tf_messages_via_business_bot_id', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->bigInteger('via_business_bot_id');
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });

        Schema::create('tf_messages_guestchat_via_from', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->tinyInteger('guestchat_via_from_type')->default(0);
            $table->bigInteger('guestchat_via_from_id')->default(0);
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });

        // reply_to — messageReplyHeader / messageReplyStoryHeader union,
        // constructor-discriminated, flattened 1:1. Nested reply_from /
        // reply_media / quote_entities cannot nest under the flat decomposer
        // write path (child columns are flat by construction) → deferred.
        Schema::create('tf_messages_reply_to', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->text('constructor');
            $table->boolean('reply_to_scheduled')->default(false);
            $table->boolean('forum_topic')->default(false);
            $table->boolean('quote')->default(false);
            $table->boolean('reply_to_ephemeral')->default(false);
            $table->integer('reply_to_msg_id')->default(0);
            $table->tinyInteger('reply_to_peer_id_type')->default(0);
            $table->bigInteger('reply_to_peer_id_id')->default(0);
            $table->integer('reply_to_top_id')->default(0);
            $table->text('quote_text')->default(DB::raw("('')"));
            $table->integer('quote_offset')->default(0);
            $table->integer('todo_item_id')->default(0);
            $table->text('poll_option')->default(DB::raw("('')"));
            $table->tinyInteger('peer_type')->default(0);
            $table->bigInteger('peer_id')->default(0);
            $table->integer('story_id')->default(0);
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });

        Schema::create('tf_messages_views', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->integer('views');
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });

        Schema::create('tf_messages_forwards', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->integer('forwards');
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });

        // replies — single-ctor messageReplies flattened. recent_repliers
        // vector is unkeyable under the flat write path → deferred.
        Schema::create('tf_messages_replies', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->boolean('comments')->default(false);
            $table->integer('replies');
            $table->integer('replies_pts');
            $table->bigInteger('channel_id')->default(0);
            $table->integer('max_id')->default(0);
            $table->integer('read_max_id')->default(0);
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });

        Schema::create('tf_messages_edit_date', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->integer('edit_date');
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });

        Schema::create('tf_messages_post_author', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->text('post_author');
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });

        Schema::create('tf_messages_grouped_id', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->bigInteger('grouped_id');
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });

        Schema::create('tf_messages_ttl_period', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->integer('ttl_period');
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });

        Schema::create('tf_messages_quick_reply_shortcut_id', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->integer('quick_reply_shortcut_id');
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });

        Schema::create('tf_messages_effect', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->bigInteger('effect');
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });

        Schema::create('tf_messages_report_delivery_until_date', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->integer('report_delivery_until_date');
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });

        Schema::create('tf_messages_paid_message_stars', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->bigInteger('paid_message_stars');
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });

        Schema::create('tf_messages_schedule_repeat_period', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->integer('schedule_repeat_period');
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });

        Schema::create('tf_messages_summary_from_language', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->text('summary_from_language');
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });

        // restriction_reason — Vector<RestrictionReason> (1:N, positioned).
        Schema::create('tf_messages_restriction_reason', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->integer('position');
            $table->text('platform');
            $table->text('reason');
            $table->text('text');
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id', 'position']);
        });

        // reactions — single-ctor messageReactions raised to presence table;
        // results / recent_reactions / top_reactors are vectors of nested
        // objects → deferred to the flat write path's next cycle.
        Schema::create('tf_messages_reactions', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->boolean('min')->default(false);
            $table->boolean('can_see_list')->default(false);
            $table->boolean('reactions_as_tags')->default(false);
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });

        // factcheck — single-ctor factCheck flattened.
        Schema::create('tf_messages_factcheck', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->boolean('need_check')->default(false);
            $table->text('country')->default(DB::raw("('')"));
            $table->text('text')->default(DB::raw("('')"));
            $table->bigInteger('hash');
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });

        // suggested_post — single-ctor suggestedPost; price StarsAmount
        // flattened to amount/nanos.
        Schema::create('tf_messages_suggested_post', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->boolean('accepted')->default(false);
            $table->boolean('rejected')->default(false);
            $table->integer('schedule_date')->default(0);
            $table->bigInteger('price_amount')->default(0);
            $table->integer('price_nanos')->default(0);
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });

        // reply_markup — ReplyMarkup union (3 ctors) + positioned rows +
        // positioned buttons (flat KeyboardButton union, immediate fields).
        Schema::create('tf_messages_reply_markup', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->text('constructor');
            $table->boolean('resize')->default(false);
            $table->boolean('single_use')->default(false);
            $table->boolean('selective')->default(false);
            $table->boolean('persistent')->default(false);
            $table->text('placeholder')->default(DB::raw("('')"));
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });

        Schema::create('tf_messages_reply_markup_rows', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->integer('position');
            $table->text('constructor');
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id', 'position']);
        });

        Schema::create('tf_messages_reply_markup_rows_buttons', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->integer('position');
            $table->integer('parent_position');
            $table->text('constructor');
            $table->boolean('requires_password')->default(false);
            $table->boolean('same_peer')->default(false);
            $table->boolean('quiz')->default(false);
            $table->text('text')->default(DB::raw("('')"));
            $table->text('url')->default(DB::raw("('')"));
            $table->text('data')->default(DB::raw("('')"));
            $table->text('query')->default(DB::raw("('')"));
            $table->text('fwd_text')->default(DB::raw("('')"));
            $table->integer('button_id')->default(0);
            $table->bigInteger('user_id')->default(0);
            $table->integer('max_quantity')->default(0);
            $table->text('copy_text')->default(DB::raw("('')"));
            // A button's identity is SIX coordinates, not two. `position` is its
            // index within a row; `parent_position` says WHICH row of
            // tf_messages_reply_markup_rows it hangs under; `account_id` is the
            // tenant; and `peer_type`/`peer_id` say WHICH DIALOG the keyboard was
            // sent in, because Telegram numbers messages per chat so message id 5
            // exists in essentially every dialog of a real account. Declaring
            // only (account_id, id, position) let two buttons that share a
            // position under one row collide -- which is what Telegram emits for
            // an ordinary one-button-per-row keyboard, every row's lone button at
            // position 0 -- and omitting the peer halves repeated the parent
            // table's own defect one level down.
            //
            // The failure was silent and worse than a lost row. MirrorFactWriter
            // derives its upsert key from the LIVE primary key
            // (MirrorFactWriter.php placeRow(), primaryKeyColumns()) specifically
            // so it never guesses, so it asked this table, was told three
            // columns, and issued updateOrInsert for button #2 -- overwriting
            // button #1's text, data and url instead of inserting. Declared DDL
            // and live DDL agreed with each other and were both wrong: no
            // migrate-time signal, no startup signal, no SQLSTATE.
            //
            // The key is named explicitly because Laravel's generated name would
            // exceed the 64-character identifier limit MySQL and Postgres
            // silently truncate at. dropPrimary() needs no name on any driver.
            $table->primary(
                ['account_id', 'peer_type', 'peer_id', 'id', 'position', 'parent_position'],
                'tf_reply_markup_buttons_pkey'
            );
        });
    }

    private function createServiceMessages(): void
    {
        Schema::create('tf_messages_service', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->text('constructor');
            $table->integer('date')->default(0);
            $table->boolean('out')->default(false);
            $table->boolean('mentioned')->default(false);
            $table->boolean('media_unread')->default(false);
            $table->boolean('reactions_are_possible')->default(false);
            $table->boolean('silent')->default(false);
            $table->boolean('post')->default(false);
            $table->boolean('legacy')->default(false);

            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
            // No index here either, and for the same reason as tf_messages: the
            // old (account_id, peer_type, peer_id) index is a strict PREFIX of
            // the primary key this block now declares, so every lookup it could
            // serve is served by the PK and every write it maintained was a
            // second structure maintained for nothing.
        });
    }

    private function createServiceMessageChildren(): void
    {
        Schema::create('tf_messages_service_from_id', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->tinyInteger('from_id_type')->default(0);
            $table->bigInteger('from_id_id')->default(0);
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });

        Schema::create('tf_messages_service_saved_peer_id', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->tinyInteger('saved_peer_id_type')->default(0);
            $table->bigInteger('saved_peer_id_id')->default(0);
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });

        Schema::create('tf_messages_service_reply_to', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->text('constructor');
            $table->boolean('reply_to_scheduled')->default(false);
            $table->boolean('forum_topic')->default(false);
            $table->boolean('quote')->default(false);
            $table->boolean('reply_to_ephemeral')->default(false);
            $table->integer('reply_to_msg_id')->default(0);
            $table->tinyInteger('reply_to_peer_id_type')->default(0);
            $table->bigInteger('reply_to_peer_id_id')->default(0);
            $table->integer('reply_to_top_id')->default(0);
            $table->text('quote_text')->default(DB::raw("('')"));
            $table->integer('quote_offset')->default(0);
            $table->integer('todo_item_id')->default(0);
            $table->text('poll_option')->default(DB::raw("('')"));
            $table->tinyInteger('peer_type')->default(0);
            $table->bigInteger('peer_id')->default(0);
            $table->integer('story_id')->default(0);
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });

        Schema::create('tf_messages_service_reactions', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->boolean('min')->default(false);
            $table->boolean('can_see_list')->default(false);
            $table->boolean('reactions_as_tags')->default(false);
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });

        Schema::create('tf_messages_service_ttl_period', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            // Peer scope, carried from the enclosing message. See the file
            // docblock: a child's key must be at least as specific as its
            // parent's, and this child's parent is (account_id, peer_type,
            // peer_id, id). Written by MirrorFactWriter from the parent row.
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->integer('ttl_period');
            $table->primary(['account_id', 'peer_type', 'peer_id', 'id']);
        });
    }
};
