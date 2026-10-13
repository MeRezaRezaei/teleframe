<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use MeRezaRezaei\Teleframe\Ingest\SafeDelete;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerShapeTool;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\TestCase;

/**
 * `updateDeleteMessages` must actually delete, end to end.
 *
 * The defect this pins: `UpdateIngestor` routed the two delete ctors and wrote
 * their ids into `tf_updates_messages`, and did NOTHING else. The delete logic
 * existed — `SafeDelete` removes the message row, the search tokens and fires
 * `MessagesDeleted` — but it had no production caller, and the `MessagesDeleted`
 * event had no listener. So an operator deleting a message in Telegram left
 * the mirror row and its search tokens in place, forever, silently: no
 * exception, no clue, green `migrate`.
 *
 * It was silent in the WORST direction. A missing row is a gap; a row that says
 * a deleted message still exists is a wrong fact, and every reader of it
 * (search, the handler pipeline, the mirror queries) is wrong with it.
 *
 * Two things are asserted here that a lazy fix would fail:
 *
 *  1. The UPDATE'S OWN FACT ROW SURVIVES. `updateDeleteMessages` is itself a
 *     fact — "these ids ceased to exist at pts N". Purging the update row, or
 *     the `tf_updates_messages` child that records the deleted ids, would
 *     destroy the audit trail of the delete and make the delete un-ingestable
 *     on replay. A "delete everything the payload mentions" fix fails here.
 *  2. THE CHILD ROWS ARE GONE. 40 `tf_messages*` tables ship and only two
 *     cascade (`2026_09_14_299999_create_tf_foreign_keys.php`), so deleting
 *     the parent alone would leave ~38 tables of orphans — reactions, replies
 *     and views still readable, still joinable to nothing.
 *
 * The schema is EXECUTED, never transcribed: `ShippedStoreSchema::capsuleFor()`
 * runs the real shipped migration files, and the child rows are seeded by
 * DISCOVERING each table's columns from the live schema rather than re-typing
 * them. A test that builds its own tables proves the author's imagination, and
 * this repo has paid for that lesson more than once today.
 *
 * The "before" is proven by the same test with the side effect removed — see
 * `test_the_delete_is_a_no_op_without_the_wiring()`, which is the failure mode
 * as it presented.
 *
 * Provenance: src/Teleframe/Ingest/UpdateIngestor.php (applyDeleteSideEffect, at
 * the end of writeUpdate()); src/Teleframe/Ingest/SafeDelete.php (both message
 * parents + the 38 `tf_messages*` children + MessageSearchIndexer::forget());
 * src/Laravel/Migrations/2026_09_14_299999_create_tf_foreign_keys.php (the 3
 * declared FKs, of which 2 are message cascades);
 * src/Laravel/Migrations/2026_09_29_000300_create_tf_message_search_table.php
 * (tf_message_search, keyed (account_id, token, peer_type, peer_id, id) — the
 * token sits immediately after account_id on purpose, so the message's own
 * peer-scoped key is not a prefix of the primary key).
 */
final class DeleteUpdateRemovesTheMessageTest extends TestCase
{
    private const PACKAGE_ROOT = __DIR__.'/../..';

    /**
     * The shipped migrations this chain needs, in apply order: the two message
     * parent tables, the message child tables, the search index, and the update
     * fact tables the delete update writes into.
     *
     * SCHEMA DRIFT REPAIR (2026-10-10). `2026_10_10_000100_fold_update_scalar_
     * children_onto_the_update_fact` belongs in this list, immediately after the
     * migration that CREATES `tf_updates` — not at the end, and not by ALTERing
     * the column in by hand. It is the migration that makes this test's schema
     * equal to the schema production has: it folds the three fabricated
     * `tf_updates_{channel,chat,user}` tables up onto `tf_updates` as the
     * `channel_id`/`chat_id`/`user_id` columns, and drops them. Without it the
     * executed schema is the PRE-FOLD one, and the production write path
     * (`UpdateIngestor.php:830`) legitimately inserts a `channel_id` column this
     * schema does not have — which is a real failure, not a test artefact, so the
     * fix is to execute the real migration rather than to hand-build the column.
     *
     * Note what this does NOT change for `seedEveryChildTable()`: that helper
     * discovers tables by the `tf_messages` prefix (`childTables()`), and all
     * three folded tables were `tf_updates_*`, so the seeded set is untouched by
     * the fold.
     */
    private const MIGRATIONS = [
        '2026_09_09_000201_create_telegram_accounts_table.php',
        '2026_09_14_200010_create_tf_messages_tables.php',
        '2026_09_14_200011_create_tf_message_entities_tables.php',
        '2026_09_14_200012_create_tf_message_medias_tables.php',
        '2026_09_14_200030_create_tf_updates_tables.php',
        '2026_10_10_000100_fold_update_scalar_children_onto_the_update_fact.php',
        '2026_09_29_000200_create_tf_messages_rich_message_table.php',
        '2026_09_29_000300_create_tf_message_search_table.php',
    ];

    private const ACCOUNT = 3;

    private const MESSAGE_ID = 4242;

    private ?Container $previousContainer = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousContainer = Container::getInstance();
    }

    protected function tearDown(): void
    {
        ShippedStoreSchema::restoreFacadeApplication($this->previousContainer);
        parent::tearDown();
    }

    public function test_a_delete_update_removes_the_message_row_its_tokens_and_its_children(): void
    {
        $db = $this->boot();
        $this->seedAccount($db);
        $this->mirrorTheMessage($db);

        // Guard: the message and its parts are all present BEFORE the delete.
        // Without this a no-op ingestor would make every assertion below pass
        // for the wrong reason.
        self::assertSame(1, $this->rowCount($db, 'tf_messages', self::MESSAGE_ID), 'precondition: the message row is not mirrored');
        self::assertGreaterThan(0, $this->rowCount($db, 'tf_message_search', self::MESSAGE_ID), 'precondition: the message has no search tokens');
        $seeded = $this->seedEveryChildTable($db);
        self::assertGreaterThan(
            30,
            count($seeded),
            'precondition: too few child tables were seeded to be evidence — '.implode(', ', $seeded),
        );

        $this->ingestDelete($db, [self::MESSAGE_ID]);

        self::assertSame(0, $this->rowCount($db, 'tf_messages', self::MESSAGE_ID), 'the mirrored row survived the delete');
        self::assertSame(0, $this->rowCount($db, 'tf_messages_service', self::MESSAGE_ID), 'the service-message surface survived the delete');
        self::assertSame(0, $this->rowCount($db, 'tf_message_search', self::MESSAGE_ID), 'search tokens survived: the deleted message is still findable');

        foreach ($seeded as $table) {
            self::assertSame(
                0,
                $this->rowCount($db, $table, self::MESSAGE_ID),
                "orphan row left in {$table} — the parent is gone but its child is still readable",
            );
        }
    }

    /**
     * The assertion that stops a lazy fix which deletes everything the payload
     * mentions: the delete update's OWN rows are facts about the delete, and
     * they must outlive it.
     */
    public function test_the_delete_updates_own_fact_row_survives_the_delete(): void
    {
        $db = $this->boot();
        $this->seedAccount($db);
        $this->mirrorTheMessage($db);
        $this->seedEveryChildTable($db);

        // A container with TWO members, so the delete lands on a real non-zero
        // slot index — position 0 is the one a bare single-update ingest would
        // also produce, which would not distinguish anything.
        (new UpdateIngestor)->ingest([
            '_' => 'updates',
            'seq' => 900,
            'updates' => [
                ['_' => 'updateReadHistoryInbox', 'peer' => ['_' => 'peerUser', 'user_id' => 55]],
                ['_' => 'updateDeleteMessages', 'messages' => [self::MESSAGE_ID], 'pts' => 900, 'pts_count' => 1],
            ],
        ], self::ACCOUNT);

        $update = $db->table('tf_updates')
            ->where('account_id', self::ACCOUNT)->where('seq', 900)->where('position', 1)->first();
        self::assertNotNull(
            $update,
            'the delete update fact row did not survive its own side effect — a delete with no record is unreplayable',
        );
        self::assertSame('updateDeleteMessages', $update->constructor);

        // tf_updates_messages is the audit trail: it records WHICH ids the
        // update deleted. Purging it with the messages would erase the only
        // evidence of what happened.
        $recorded = $db->table('tf_updates_messages')
            ->where('account_id', self::ACCOUNT)->where('seq', 900)->where('position', 1)->get();
        self::assertCount(1, $recorded, 'the deleted ids were not recorded on the update');
        self::assertSame(self::MESSAGE_ID, (int) $recorded[0]->message_id);

        // And the pts child the same update wrote is still there.
        self::assertSame(
            1,
            $db->table('tf_updates_pts')
                ->where('account_id', self::ACCOUNT)->where('seq', 900)->where('position', 1)->count(),
            'a child row written by the same update was purged by its own side effect',
        );

        // The message itself is nevertheless gone — the two facts coexist.
        self::assertSame(0, $this->rowCount($db, 'tf_messages', self::MESSAGE_ID));
    }

    /**
     * A never-mirrored id is a no-op, never a raise.
     *
     * `applyDeleteSideEffect` runs INSIDE the ingest transaction, so an
     * exception on an id the mirror never stored would roll back the update's
     * own fact row — and the replay would raise again, forever. A delete for a
     * message filtered out of an update gap is routine, not exceptional.
     */
    public function test_a_never_mirrored_id_is_a_no_op_not_an_error(): void
    {
        $db = $this->boot();
        $this->seedAccount($db);

        $root = $this->ingestDelete($db, [999_999_001], seq: 901, position: 0);

        self::assertNotNull($root, 'the delete update did not store its fact row for an unknown id');
        self::assertSame(1, $db->table('tf_updates')
            ->where('account_id', self::ACCOUNT)->where('seq', 901)->where('position', 0)->count());
    }

    /**
     * `updateDeleteChannelMessages` is the same fact with a scope word, and the
     * curated dial keys both message surfaces (account_id, id) with no channel
     * column — so the same id set must delete, not silently no-op.
     */
    public function test_a_channel_delete_removes_the_message_too(): void
    {
        $db = $this->boot();
        $this->seedAccount($db);
        // FIXTURE CORRECTION (2026-10-03, Task 2.2): the message must live in
        // the channel the delete names, because the peer is now part of the
        // delete predicate — see `mirrorTheMessage()`.
        $this->mirrorTheMessage($db, ['_' => 'peerChannel', 'channel_id' => 777]);
        $this->seedEveryChildTable($db, ['_' => 'peerChannel', 'channel_id' => 777]);

        $root = (new UpdateIngestor)->ingest([
            '_' => 'updateDeleteChannelMessages',
            'channel_id' => 777,
            'messages' => [self::MESSAGE_ID],
            'pts' => 902,
            'pts_count' => 1,
        ], self::ACCOUNT);

        self::assertNotNull($root, 'the channel delete stored no fact row');
        self::assertSame(0, $this->rowCount($db, 'tf_messages', self::MESSAGE_ID), 'a channel delete left the message row behind');
        self::assertSame(0, $this->rowCount($db, 'tf_message_search', self::MESSAGE_ID), 'a channel delete left the search tokens behind');
        self::assertSame(0, $this->rowCount($db, 'tf_messages_reactions', self::MESSAGE_ID), 'a channel delete left child rows behind');
        // The direct path (no frame identity) now derives the update's key from the
        // payload's own content, so `seq`/`position` are no longer (0, 0) and
        // cannot be used as a locator. These two assertions previously found
        // "the" row by (seq 0, position 0); their INTENT is about the ROW —
        // exactly one fact row exists, and it carries the real channel_id — so
        // the locator is now the account alone, which is STRICTLY STRONGER:
        // a stray second row on this account used to pass unnoticed and now
        // fails the count.
        self::assertSame(1, $db->table('tf_updates')
            ->where('account_id', self::ACCOUNT)->count());

        // SCHEMA DRIFT REPAIR (2026-10-10). This chain now EXECUTES
        // `2026_10_10_000100_fold_update_scalar_children_onto_the_update_fact`, so
        // the `channel_id` this payload names is stored as a COLUMN on the
        // `tf_updates` fact row rather than as a row in the dropped
        // `tf_updates_channel` table. Asserted on the SURVIVING row and against
        // the REAL value — never against the `NOT NULL DEFAULT 0` the fold
        // declares for an update that names no such field, which a
        // `count()`-shaped assertion would happily accept. This is the assertion
        // that makes the migration in `MIGRATIONS` load-bearing rather than
        // merely present: without it, a write path that read `channel_id`, threw
        // it away, and wrote the default would still pass every check above.
        self::assertSame(
            777,
            (int) $db->table('tf_updates')
                ->where('account_id', self::ACCOUNT)
                ->value('channel_id'),
            'the channel id the delete named was not folded onto the surviving update fact row as a real value',
        );
    }

    /**
     * ASSERTION ADDED (2026-10-03, Task 2.2): a delete that names a DIFFERENT
     * peer must leave the message alone.
     *
     * This is the direction the peer-scoped predicate protects, and nothing in
     * the class asserted it while the delete ignored the peer entirely — the
     * suite could not have told "the delete worked" from "the delete removed
     * message 5 from all 136 dialogs that hold one". `updateDeleteChannelMessages`
     * for a channel the message is not in must delete nothing at all: no row,
     * no children, no search tokens.
     */
    public function test_a_delete_naming_another_peer_leaves_the_message_alone(): void
    {
        $db = $this->boot();
        $this->seedAccount($db);
        $this->mirrorTheMessage($db, ['_' => 'peerUser', 'user_id' => 55]);
        $this->seedEveryChildTable($db, ['_' => 'peerUser', 'user_id' => 55]);

        $root = (new UpdateIngestor)->ingest([
            '_' => 'updateDeleteChannelMessages',
            'channel_id' => 777,
            'messages' => [self::MESSAGE_ID],
            'pts' => 904,
            'pts_count' => 1,
        ], self::ACCOUNT);

        self::assertNotNull($root, 'the channel delete stored no fact row — the update itself must still be mirrored');

        // Counts are MEASURED before the delete rather than hardcoded: the
        // tokenizer emits one row per distinct token, so "how many token rows
        // does this message have" is a property of the message text, not a
        // constant this test should be asserting.
        $tokens = $this->rowCount($db, 'tf_message_search', self::MESSAGE_ID);
        self::assertGreaterThan(0, $tokens, 'the seeded message was never indexed, so this test would prove nothing');

        self::assertSame(
            1,
            $this->rowCount($db, 'tf_messages', self::MESSAGE_ID),
            'a delete naming channel 777 must not remove a message that lives in user 55\'s dialog',
        );
        self::assertSame(
            $tokens,
            $this->rowCount($db, 'tf_message_search', self::MESSAGE_ID),
            'and must not take its search tokens either',
        );
        self::assertSame(
            1,
            $this->rowCount($db, 'tf_messages_reactions', self::MESSAGE_ID),
            'nor its children',
        );
    }

    /**
     * THE C1 CROSS-DIALOG SURVIVAL TEST. This is the one that was missing.
     *
     * `tf_messages_media` is the ONE `tf_messages*` table that does not spell
     * the enclosing message's scope `peer_type`/`peer_id` — those two columns
     * already carry a peer from INSIDE the `MessageMedia` union payload, so the
     * message's own scope is the PREFIXED pair
     * `message_peer_type`/`message_peer_id` (2026_09_14_200012:28-36). Round 1
     * correctly removed it from `SafeDelete::MESSAGE_CHILD_TABLES` for exactly
     * that reason, and left it deleted by one explicit prefixed-predicate call
     * instead — but NOTHING PROVED IT. `grep` over every `tf_messages_media`
     * seed in this repo: the payload peer sits at 0 on all of them. And every
     * test in this class seeded ONE dialog, so the wrong-column delete and the
     * right-column delete hit the same single row and were indistinguishable.
     * Putting `tf_messages_media` back into `MESSAGE_CHILD_TABLES` left the
     * whole suite green.
     *
     * The bug being pinned, stated as the damage it does: with the table in the
     * list, the shared peer-scoped predicate applied the MESSAGE's peer to the
     * media table's OWN payload-peer columns. So deleting message 5 in dialog X
     * also deleted message 5's media in every OTHER dialog whose media payload
     * happened to carry X's peer — real, ordinary data (`messageMediaWebPage`
     * and friends carry a `peer_saved`/sender-style peer), destroyed silently,
     * in a dialog the delete never named. That is exactly the cross-dialog
     * damage the peer-scoped predicate exists to prevent, reintroduced one
     * table down, which is why the file's own comment needed correcting too.
     *
     * The fixture is therefore built to be INDISTINGUISHABLE except for the
     * column the predicate names: message 5 exists in BOTH dialogs, each
     * dialog's media row is addressed by its own `message_peer_*` pair, and
     * dialog Y's payload peer is set to dialog X's peer. A predicate on
     * `message_peer_*` cannot see the payload peer at all, so Y survives; a
     * predicate on `peer_type`/`peer_id` matches Y and destroys it.
     *
     * Nothing CASCADES here, and that is asserted rather than assumed — see the
     * `PRAGMA foreign_key_list` check below — so Y's survival is decided solely
     * by which column SafeDelete names.
     */
    public function test_deleting_in_one_dialog_leaves_another_dialogs_media_row_alone(): void
    {
        $db = $this->boot();
        $this->seedAccount($db);

        $dialogX = ['_' => 'peerUser', 'user_id' => 55];
        $dialogY = ['_' => 'peerUser', 'user_id' => 77];
        [$xType, $xId] = PeerShapeTool::normalize($dialogX);
        [$yType, $yId] = PeerShapeTool::normalize($dialogY);
        self::assertNotSame(
            [$xType, $xId],
            [$yType, $yId],
            'precondition: the two dialogs must be DIFFERENT peers, or this is a single-dialog test wearing a two-dialog name',
        );

        // THE SAME message id, mirrored into BOTH dialogs through the real
        // ingestor, with each dialog's children seeded under its own address.
        $this->mirrorTheMessage($db, $dialogX);
        $this->mirrorTheMessage($db, $dialogY);
        $this->seedEveryChildTable($db, $dialogX);
        $this->seedEveryChildTable($db, $dialogY);

        // The ordinary shape that does the damage: dialog Y's media payload
        // carries a peer, and that peer is X's. Left at 0 (as every other seed
        // in this repo has it) this test cannot fail, which is exactly why it
        // did not exist.
        $updated = $db->table('tf_messages_media')
            ->where('account_id', self::ACCOUNT)
            ->where('message_peer_type', $yType)
            ->where('message_peer_id', $yId)
            ->where('id', self::MESSAGE_ID)
            ->update(['peer_type' => $xType, 'peer_id' => $xId]);
        self::assertSame(1, $updated, 'precondition: no media row for dialog Y was found to give a payload peer');

        // Preconditions, counted rather than assumed. Two message rows under
        // one id, one media row per dialog, and nothing that could cascade.
        self::assertSame(2, $this->rowCount($db, 'tf_messages', self::MESSAGE_ID), 'precondition: the message is not mirrored in BOTH dialogs');
        self::assertSame(1, $this->mediaRows($db, $xType, $xId), 'precondition: dialog X has no media row of its own');
        self::assertSame(1, $this->mediaRows($db, $yType, $yId), 'precondition: dialog Y has no media row of its own');
        self::assertSame(
            [],
            $db->select('PRAGMA foreign_key_list(tf_messages_media)'),
            'precondition: this chain loads no FK migration, so NO cascade can credit the survival below — the '
            .'result must be decided by which column SafeDelete names. (ShippedStoreSchema also leaves '
            .'`PRAGMA foreign_keys` OFF by default, so a declared FK would not fire either.)',
        );

        $root = $this->ingestDelete($db, [self::MESSAGE_ID], peer: $dialogX);
        self::assertNotNull($root, 'the delete update stored no fact row — the update itself must still be mirrored');

        // Dialog X is gone, in full: the message, its media, and its tokens.
        self::assertSame(
            0,
            $this->rowCountIn($db, 'tf_messages', ['peer_type' => $xType, 'peer_id' => $xId]),
            'the delete named dialog X and left X\'s message behind',
        );
        self::assertSame(0, $this->mediaRows($db, $xType, $xId), 'X\'s own media row survived the delete of X');

        // Dialog Y is untouched — THE ASSERTION.
        self::assertSame(
            1,
            $this->rowCountIn($db, 'tf_messages', ['peer_type' => $yType, 'peer_id' => $yId]),
            'CROSS-DIALOG DAMAGE: deleting message 5 in dialog X also removed dialog Y\'s message 5. The peer-scoped '
            .'delete predicate is supposed to make `id` mean one message, not one id per account.',
        );
        $survivor = $db->table('tf_messages_media')
            ->where('account_id', self::ACCOUNT)
            ->where('message_peer_type', $yType)
            ->where('message_peer_id', $yId)
            ->where('id', self::MESSAGE_ID)
            ->first();
        self::assertNotNull(
            $survivor,
            'CROSS-DIALOG DAMAGE — the exact defect: `tf_messages_media` was deleted on `peer_type`/`peer_id`, which on '
            .'that table are the media PAYLOAD\'s peer and default to 0, NOT the enclosing message\'s scope. So a '
            .'delete in dialog X reached into dialog Y and destroyed message 5\'s media there, because Y\'s payload '
            .'happened to carry X\'s peer. The table must be reached on `message_peer_type`/`message_peer_id`, or not '
            .'reached by the shared predicate at all.',
        );
        // Proved it is Y's row and not a stranger's: the payload peer is still X's.
        self::assertSame($xId, (int) $survivor->peer_id, 'the surviving row must be dialog Y\'s media row, payload peer and all');
        self::assertSame($xType, (int) $survivor->peer_type);

        // The mirrored text of Y's message is intact too, so the survivor is a
        // real row and not a residue of a partial write.
        $yMessage = $db->table('tf_messages')
            ->where('account_id', self::ACCOUNT)
            ->where('peer_type', $yType)->where('peer_id', $yId)->where('id', self::MESSAGE_ID)
            ->first();
        self::assertNotNull($yMessage);
    }

    /**
     * The defect as it presented, and the proof that the wiring is what changed.
     *
     * Identical schema, identical seed, identical payload — but SafeDelete is
     * simply not invoked, which is exactly the pre-fix state (the ctor routed,
     * the ids recorded, nothing deleted). This is the "before" half of the
     * proof: the row is still there.
     */
    public function test_the_delete_is_a_no_op_without_the_wiring(): void
    {
        $db = $this->boot();
        $this->seedAccount($db);
        $this->mirrorTheMessage($db);
        $this->seedEveryChildTable($db);

        // The pre-fix path, verbatim: the update ctor routes into
        // tf_updates + tf_updates_messages and stops there. No SafeDelete.
        $db->transaction(function () use ($db): void {
            $key = ['account_id' => self::ACCOUNT, 'seq' => 903, 'position' => 0];
            $db->table('tf_updates')->updateOrInsert($key, [...$key, 'constructor' => 'updateDeleteMessages']);
            $db->table('tf_updates_messages')->updateOrInsert(
                [...$key, 'message_position' => 0],
                [...$key, 'message_position' => 0, 'message_id' => self::MESSAGE_ID],
            );
        });

        self::assertSame(
            1,
            $this->rowCount($db, 'tf_messages', self::MESSAGE_ID),
            'precondition: the unwired path is expected to leave the message row in place',
        );
        self::assertGreaterThan(0, $this->rowCount($db, 'tf_message_search', self::MESSAGE_ID), 'precondition: tokens still present');
        self::assertSame(1, $this->rowCount($db, 'tf_messages_reactions', self::MESSAGE_ID), 'precondition: child row still present');
        self::assertSame(1, $db->table('tf_updates_messages')
            ->where('account_id', self::ACCOUNT)->where('seq', 903)->count(), 'precondition: the ids were still recorded');

        // And the same id set through the wired path, on the same schema, is gone.
        $this->ingestDelete($db, [self::MESSAGE_ID], seq: 904);
        self::assertSame(0, $this->rowCount($db, 'tf_messages', self::MESSAGE_ID), 'the wired path did not delete the row');
        self::assertSame(0, $this->rowCount($db, 'tf_message_search', self::MESSAGE_ID), 'the wired path did not delete the tokens');
        self::assertSame(0, $this->rowCount($db, 'tf_messages_reactions', self::MESSAGE_ID), 'the wired path did not delete the child rows');
    }

    /**
     * A delete arriving in SECOND position inside an `updates` container must
     * still be ingested and still act.
     *
     * `writeUpdateContainer()` read `$first ??= $this->writeUpdate(...)`, and
     * `??=` short-circuits the CALL as well as the assignment — so the loop
     * wrote only the container's FIRST member and silently dropped the rest. A
     * real `updateDeleteMessages` shares its container with whatever the
     * server batched alongside it, so position 0 was the lucky case and every
     * other position was a no-op: no fact row, no delete, no clue. This pins
     * that all members are written and only the first is returned as the root.
     */
    public function test_every_container_member_is_written_not_only_the_first(): void
    {
        $db = $this->boot();
        $this->seedAccount($db);
        $this->mirrorTheMessage($db);

        $root = (new UpdateIngestor)->ingest([
            '_' => 'updates',
            'seq' => 950,
            'updates' => [
                ['_' => 'updateReadHistoryInbox', 'peer' => ['_' => 'peerUser', 'user_id' => 55]],
                ['_' => 'updateDeleteMessages', 'messages' => [self::MESSAGE_ID], 'pts' => 950, 'pts_count' => 1],
            ],
        ], self::ACCOUNT);

        self::assertNotNull($root, 'the container stored no root at all');
        self::assertSame(0, (int) $root->position, 'the first member is the returned root');

        self::assertSame(2, $db->table('tf_updates')
            ->where('account_id', self::ACCOUNT)->where('seq', 950)->count(), 'a container member was dropped');
        self::assertSame(1, $db->table('tf_updates')
            ->where('account_id', self::ACCOUNT)->where('seq', 950)->where('position', 1)
            ->where('constructor', 'updateDeleteMessages')->count(), 'the second member was not written');
        self::assertSame(0, $this->rowCount($db, 'tf_messages', self::MESSAGE_ID), 'the second-position delete did not act');
    }

    /**
     * SafeDelete's own contract, asserted against the shipped DDL rather than a
     * hand-built one: the return value counts PARENT rows across both message
     * surfaces, so a delete naming a service message is not reported as zero.
     */
    public function test_safe_delete_counts_both_message_surfaces(): void
    {
        $db = $this->boot();
        $this->seedAccount($db);

        $db->table('tf_messages')->insert([
            'account_id' => self::ACCOUNT, 'id' => 10, 'constructor' => 'message',
            'peer_type' => 3, 'peer_id' => 5, 'date' => 1, 'message' => 'a',
        ]);
        $db->table('tf_messages_service')->insert($this->keyedRow($db, 'tf_messages_service', 11, [
            'constructor' => 'messageService', 'peer_type' => 3, 'peer_id' => 5, 'date' => 1,
        ]));

        self::assertSame(2, (new SafeDelete)->deleteMessages(self::ACCOUNT, [10, 11]));
    }

    private function boot(): Connection
    {
        return ShippedStoreSchema::capsuleFor(...$this->migrationPaths())->getConnection();
    }

    private function seedAccount(Connection $db): void
    {
        $db->table('telegram_accounts')->insert([
            'id' => self::ACCOUNT,
            'label' => 'delete-update-'.self::ACCOUNT,
            'type' => 'user',
            'dc_id' => 2,
        ]);
    }

    /**
     * Mirror a real message through the real ingestor, so the row and the
     * search tokens under test are rows the production write path produced.
     *
     * The peer the seeded message lives in.
     *
     * FIXTURE CORRECTION (2026-10-03, Task 2.2). This used to be hardcoded to
     * `peerUser 55` and every caller inherited it — including the test named
     * `test_a_channel_delete_removes_the_message_too`, which then issued
     * `updateDeleteChannelMessages channel_id=777` against a message living in a
     * USER dialog. Those two only ever agreed because the delete predicate
     * ignored the peer entirely. It cannot any more, and it must not: Telegram
     * numbers messages PER CHAT, so `deleteMessages([5])` without a peer purges
     * message 5 from every dialog of the account.
     *
     * So the peer is a parameter, each caller names the peer it means, and
     * `test_a_delete_naming_another_peer_leaves_the_message_alone` asserts the
     * consequence.
     */
    private function mirrorTheMessage(Connection $db, array $peer = ['_' => 'peerUser', 'user_id' => 55]): void
    {
        $root = (new UpdateIngestor)->ingest([
            '_' => 'message',
            'id' => self::MESSAGE_ID,
            'peer_id' => $peer,
            'date' => 1735689600,
            'message' => 'a message that will be deleted',
            'from_id' => ['_' => 'peerUser', 'user_id' => 55],
        ], self::ACCOUNT);

        self::assertNotNull($root, 'the message payload did not mirror — the rest of this test would prove nothing');
        unset($db);
    }

    /**
     * Ingest a real `updateDeleteMessages` through a real `updates` container,
     * so the fact row lands on an addressable (seq, position) key rather than
     * always colliding on (0, 0).
     *
     * `updateDeleteMessages#a20db0e5` carries `peer:Peer` alongside
     * `messages:Vector<int>` — the peer that says WHICH dialog those ids
     * belong to. Omitting it leaves `SafeDelete` in its deliberate
     * account-wide fallback (`SafeDelete::deleteMessages`, "WHEN THE PEER IS
     * UNKNOWN"), so a caller that means one dialog must name it.
     *
     * @param  list<int>  $ids
     * @param  array<string, mixed>  $peer
     */
    private function ingestDelete(Connection $db, array $ids, int $seq = 0, int $position = 0, ?array $peer = null): ?object
    {
        $delete = [
            '_' => 'updateDeleteMessages',
            'messages' => $ids,
            'pts' => $seq,
            'pts_count' => count($ids),
        ];
        if ($peer !== null) {
            $delete['peer'] = $peer;
        }

        (new UpdateIngestor)->ingest([
            '_' => 'updates',
            'seq' => $seq,
            'updates' => [$delete],
        ], self::ACCOUNT);

        return $db->table('tf_updates')
            ->where('account_id', self::ACCOUNT)->where('seq', $seq)->where('position', $position)->first();
    }

    /**
     * Seed a row into EVERY shipped `tf_messages*` child table, so the purge
     * assertion covers the whole set rather than the two or three tables that
     * happen to be convenient.
     *
     * Columns are DISCOVERED from the live schema, never re-typed: the row is
     * built by taking the table's own column list and filling the non-key
     * columns with a value their declared type accepts. That keeps this test
     * honest if the dial adds or drops a column.
     *
     * @return list<string> the tables that were seeded
     */
    private function seedEveryChildTable(Connection $db, array $peer = ['_' => 'peerUser', 'user_id' => 55]): array
    {
        // FIXTURE CORRECTION (2026-10-03, Task 2.2). `keyedRow()` fills every
        // column the DDL declares with a type-shaped placeholder, and the peer
        // columns are integers — so every seeded child used to land at
        // (account_id, 0, 0, MESSAGE_ID), a key no parent row has since the
        // message tables were keyed by their real peer scope. The children were
        // therefore sitting in a different address space from their own parent,
        // and this class's claim to prove "the delete removes the message AND
        // every part of it" was only ever true of the placeholder address.
        // The peer is now threaded in from the same value `mirrorTheMessage()`
        // was given, so the children are genuinely reachable from their parent.
        [$peerType, $peerId] = PeerShapeTool::normalize($peer);

        $seeded = [];
        foreach ($this->childTables($db) as $table) {
            // updateOrInsert, not insert: mirrorTheMessage() already placed the
            // 1:1 children the ingestor owns (from_id, saved_peer_id), and this
            // seeder must top those up rather than collide with them.
            $row = $this->keyedRow($db, $table, self::MESSAGE_ID);
            foreach (['peer_type' => $peerType, 'peer_id' => $peerId] as $col => $value) {
                if (array_key_exists($col, $row)) {
                    $row[$col] = $value;
                }
            }
            // `tf_messages_media` already owns peer_type/peer_id for a peer
            // inside its own payload, so the enclosing message's scope there is
            // the PREFIXED pair — see 2026_09_14_200012.
            foreach (['message_peer_type' => $peerType, 'message_peer_id' => $peerId] as $col => $value) {
                if (array_key_exists($col, $row)) {
                    $row[$col] = $value;
                }
            }

            // The address MUST be the peer-scoped key these tables are keyed by,
            // not the (account_id, id) of the pre-widening dialect. With one
            // seeded peer the two coincide, so a wrong address is invisible —
            // but the moment this fixture seeds a SECOND dialog it would
            // silently update the wrong dialog's row instead of inserting a new
            // one, and the whole class's "the purge removes exactly this
            // message's parts" claim would be testing a collapsed address
            // space. Writing on the real key is what makes
            // `test_deleting_in_one_dialog_leaves_another_dialogs_media_row_alone`
            // meaningful — a claim this comment made for a full round BEFORE
            // that test existed (Task 2.2 fix round 2); it does now.
            $where = ['account_id' => self::ACCOUNT, 'id' => self::MESSAGE_ID];
            foreach (['peer_type' => $peerType, 'peer_id' => $peerId] as $col => $value) {
                if (array_key_exists($col, $row)) {
                    $where[$col] = $value;
                }
            }
            foreach (['message_peer_type' => $peerType, 'message_peer_id' => $peerId] as $col => $value) {
                if (array_key_exists($col, $row)) {
                    $where[$col] = $value;
                }
            }

            $db->table($table)->updateOrInsert($where, $row);
            $seeded[] = $table;
        }

        return $seeded;
    }

    /**
     * @return list<string>
     */
    private function childTables(Connection $db): array
    {
        $children = [];
        foreach ($db->getSchemaBuilder()->getTableListing() as $listed) {
            // sqlite reports attached schemas as `main.tf_messages_views`; the
            // bare table name is what a query builder takes.
            $table = str_contains($listed, '.') ? substr($listed, strrpos($listed, '.') + 1) : $listed;
            if (! str_starts_with($table, 'tf_messages')) {
                continue;
            }
            if ($table === 'tf_messages' || $table === 'tf_messages_service') {
                continue; // the parents, seeded by mirrorTheMessage()
            }
            $children[] = $table;
        }
        sort($children);

        return $children;
    }

    /**
     * A row for a message child table, with (account_id, id) set and every
     * other column filled with a type-appropriate value.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function keyedRow(Connection $db, string $table, int $id, array $overrides = []): array
    {
        $row = [];
        foreach ($db->getSchemaBuilder()->getColumnListing($table) as $column) {
            $row[$column] = match ($column) {
                'account_id' => self::ACCOUNT,
                'id' => $id,
                default => $this->placeholder($db, $table, $column),
            };
        }

        return array_merge($row, $overrides);
    }

    /**
     * @return bool|float|int|string|null
     */
    private function placeholder(Connection $db, string $table, string $column)
    {
        $type = $db->getSchemaBuilder()->getColumnType($table, $column);

        return match ($type) {
            'boolean' => false,
            'integer', 'bigint', 'smallint', 'tinyint' => 0,
            'float' => 0.0,
            'text' => 'x',
            default => 'x',
        };
    }

    /**
     * @return list<string>
     */
    private function migrationPaths(): array
    {
        return array_map(
            static fn (string $name): string => self::PACKAGE_ROOT.'/src/Laravel/Migrations/'.$name,
            self::MIGRATIONS,
        );
    }

    private function rowCount(Connection $db, string $table, int $id): int
    {
        return $db->table($table)->where('account_id', self::ACCOUNT)->where('id', $id)->count();
    }

    /**
     * A row count narrowed to ONE DIALOG, so two rows sharing an `id` are
     * distinguishable — which is the whole point of the cross-dialog test.
     *
     * @param  array<string, int>  $dialog
     */
    private function rowCountIn(Connection $db, string $table, array $dialog): int
    {
        return $db->table($table)
            ->where('account_id', self::ACCOUNT)
            ->where($dialog)
            ->where('id', self::MESSAGE_ID)
            ->count();
    }

    /**
     * `tf_messages_media` rows for one dialog, counted on the PREFIXED pair the
     * table declares for the enclosing message's scope — never on
     * `peer_type`/`peer_id`, which are the media payload's own peer on this
     * table (2026_09_14_200012:28-36).
     */
    private function mediaRows(Connection $db, int $peerType, int $peerId): int
    {
        return $db->table('tf_messages_media')
            ->where('account_id', self::ACCOUNT)
            ->where('message_peer_type', $peerType)
            ->where('message_peer_id', $peerId)
            ->where('id', self::MESSAGE_ID)
            ->count();
    }
}
