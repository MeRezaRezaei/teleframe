<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Mirror;

use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Events\Dispatcher;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\TestCase;

/**
 * The writer must place a decomposed batch PARENT-FIRST, because the mirror is
 * fed out of order and the shipped DDL enforces exactly the relations that
 * order breaks.
 *
 * ## THE DEFECT THIS FILE PINS — silent, permanent loss of message payloads
 *
 * Two load-bearing facts were measured, not assumed, before any code moved:
 *
 *  1. **Nothing ordered the rows.** `grep -c "usort\|ksort\|array_multisort"`
 *     over `MirrorFactWriter.php`, `UpdateIngestor.php` and
 *     `MirrorIngesterFactory.php` returned `0 0 0`. The decomposer emits a
 *     parent and its children as SIBLINGS and no layer decides which lands
 *     first, so the order is whatever the payload's field order happened to be.
 *
 *  2. **A dropped row is invisible AND permanent.** In `write()` the
 *     `$inserted++` sits INSIDE the try, ABOVE the `catch (QueryException)`,
 *     which turns an FK violation into a clue string and RETURNS. So a refused
 *     child is: not counted, not raised, not retried — and the caller advances
 *     a WATERMARK on `inserted`. The watermark moves past the row that was
 *     dropped, the bus is at-least-once per PAGE rather than per row, and the
 *     row is never asked for again. The mirror and Telegram then disagree
 *     permanently with nothing anywhere reporting it.
 *
 * And the out-of-order arrival this collides with is NORMAL, in the repo's own
 * words — `src/Teleframe/Ingest/SafeDelete.php:40-41`:
 *
 *   "A mirror is fed out of order, so a child row may legitimately be written
 *    before its parent exists; promoting 38 of those to enforced FKs converts a
 *    tolerated out-of-order write into a hard raise on Postgres."
 *
 * That sentence describes a host that had no FKs. This repo ships exactly three
 * cross-domain enforced FKs — `2026_09_14_299999_create_tf_foreign_keys.php` —
 * and they land on the three highest-volume children there are:
 * `tf_messages_media`, `tf_messages_entities`, and
 * `tf_messages_reply_markup_rows_buttons`. Measured against the real shipped
 * DDL with `PRAGMA foreign_keys=ON`, a media child, an entities child and a
 * keyboard button each arriving BEFORE their parent are each refused, silently.
 *
 * The writer's own docblock named the fix and never applied it
 * (`MirrorFactWriter.php:22-23`): "An ingest path that feeds a child before its
 * parent triggers an FK clue; re-ordering to parent-first fixes the path."
 *
 * ## WHY `PRAGMA foreign_keys = ON` IS SET IN EVERY TEST HERE
 *
 * `ShippedStoreSchema::capsuleFor()` deliberately leaves enforcement OFF,
 * because sqlite defaults to off and "whether a test wants the FK enforced is a
 * per-test decision". That default is precisely why this repo's suite stayed
 * green while the live path lost data: with enforcement off, an out-of-order
 * child insert SUCCEEDS, so no test in the suite could ever have observed the
 * drop. Every test below turns enforcement on, so the assertions are about what
 * the DATABASE did, not about what the writer's return value claims.
 *
 * ## THE DDL IS EXECUTED, NEVER TRANSCRIBED
 *
 * The schemas come from `ShippedStoreSchema::capsuleFor()` over real migration
 * files. A hand-typed `tf_messages_media` in a test would be a second copy of
 * the DDL, and a second copy silently drifts — the failure
 * `ShippedStoreSchema`'s own docblock records having already cost this repo
 * once. The two `tf_cycle_*` tables are the ONE exception and they are not a
 * transcription of anything: they are a synthetic schema that exists only
 * because the shipped schema is acyclic, so the cycle the sort must survive
 * cannot be expressed by shipped DDL at all.
 */
final class MirrorFactWriterParentFirstOrderingTest extends TestCase
{
    /**
     * The shipped migrations that create the message tree and the three
     * curated cross-domain FKs over it. `299999` is last because it only ever
     * `Schema::table()`s tables the earlier dial creates — applying it alone
     * would forward-reference parents that do not exist.
     */
    private const MIGRATIONS = [
        '2026_09_14_200010_create_tf_messages_tables.php',
        '2026_09_14_200011_create_tf_message_entities_tables.php',
        '2026_09_14_200012_create_tf_message_medias_tables.php',
        '2026_09_14_200003_create_tf_channels_tables.php',
        '2026_09_14_200031_create_tf_channel_participants_tables.php',
        '2026_09_14_299999_create_tf_foreign_keys.php',
    ];

    /** Every table an INSERT listener must recognise. Longest first, so a table name
 *  that is a PREFIX of another can never swallow the longer one —
     *  `tf_messages` is a prefix of three others. */
    private const MIGRATION_TABLES = [
        'tf_messages_reply_markup_rows_buttons',
        'tf_messages_reply_markup_rows',
        'tf_messages_media',
        'tf_messages_entities',
        'tf_messages',
    ];

    private const ACCOUNT = 41;

    private const MESSAGE_ID = 900;

    /** Hard deadline for the hostile-input child. A hang is killed, never awaited. */
    private const DEADLINE_S = 20.0;

    private ?Container $previousContainer = null;

    protected function tearDown(): void
    {
        ShippedStoreSchema::restoreFacadeApplication($this->previousContainer);
        $this->previousContainer = null;

        parent::tearDown();
    }

    /**
     * PROOF 1 — RED, then GREEN.
     *
     * RED is proved on the DATABASE, not on an exception the writer might
     * swallow: the shipped DDL, with enforcement on, refuses the out-of-order
     * media child, and the table is left EMPTY (count 0). That is the defect,
     * demonstrated against the real constraint.
     *
     * GREEN is proved on WHAT LANDS: the very same child-first batch handed to
     * `MirrorFactWriter::write()` finishes with the media row PRESENT (count 1),
     * with the parent present, and with `inserted` counting both. The writer's
     * own catch never fired, so nothing needed swallowing — the row was never
     * out of order by the time it reached `placeRow()`.
     */
    public function test_child_before_parent_is_refused_by_the_ddl_then_landed_by_the_writer(): void
    {
        $db = $this->shippedConnection();

        // ---- RED: the shipped constraint does refuse an out-of-order child.
        try {
            $db->table('tf_messages_media')->insert($this->mediaRow());
            self::fail('the shipped FK must refuse a media child whose parent message does not exist');
        } catch (QueryException $e) {
            self::assertStringContainsString('foreign key', strtolower($e->getMessage()));
        }

        self::assertSame(
            0,
            $db->table('tf_messages_media')->count(),
            'RED: nothing landed, and this is the state the watermark then advanced past',
        );

        // ---- GREEN: the writer places the same batch, parent-first.
        $result = (new MirrorFactWriter)->write($db, [
            ['table' => 'tf_messages_media', 'row' => $this->mediaRow()],
            ['table' => 'tf_messages', 'row' => $this->messageRow()],
        ]);

        self::assertSame(
            1,
            $db->table('tf_messages_media')->count(),
            'GREEN: the child that used to be dropped silently must now be IN the mirror',
        );
        self::assertSame(1, $db->table('tf_messages')->count(), 'the parent landed');
        self::assertSame(2, $result['inserted'], 'inserted counts both rows: neither was dropped');
        self::assertSame([], $result['fkClues'], 'no FK clue, because nothing was refused');
        self::assertSame([], $result['unmappedClues']);
        self::assertSame([], $result['valueClues']);
    }

    /**
     * PROOF 2 — the three curated cross-domain FKs, all GREEN at once, with
     * `inserted` counting every row and every clue bucket empty.
     *
     * All three children are fed BEFORE their parents, which is the worst legal
     * case: nothing in the batch is placeable where it sits.
     */
    public function test_all_three_curated_children_survive_being_fed_before_their_parents(): void
    {
        $db = $this->shippedConnection();

        $rows = [
            ['table' => 'tf_messages_media', 'row' => $this->mediaRow()],
            ['table' => 'tf_messages_entities', 'row' => $this->entityRow()],
            ['table' => 'tf_messages_reply_markup_rows', 'row' => $this->markupRowRow()],
            ['table' => 'tf_messages_reply_markup_rows_buttons', 'row' => $this->buttonRow()],
            ['table' => 'tf_messages', 'row' => $this->messageRow()],
        ];

        $result = (new MirrorFactWriter)->write($db, $rows);

        self::assertSame([], $result['fkClues'], 'a parent-first batch must produce no FK clue at all');
        self::assertSame(5, $result['inserted'], 'all five rows are in the mirror, so all five count');
        self::assertSame(1, $db->table('tf_messages')->count());
        self::assertSame(1, $db->table('tf_messages_media')->count());
        self::assertSame(1, $db->table('tf_messages_entities')->count());
        self::assertSame(1, $db->table('tf_messages_reply_markup_rows')->count());
        self::assertSame(1, $db->table('tf_messages_reply_markup_rows_buttons')->count());
    }

    /**
     * PROOF 3 — in-order input is preserved BYTE FOR BYTE.
     *
     * `write()`'s return value does not expose the order, so the order is
     * observed where it actually happens: a query listener on the real
     * connection records the sequence of INSERTs. The assertions are that the
     * parent-first batch inserts in the caller's order, and that a batch whose
     * ONLY defect is a genuinely unplaceable row produces byte-identical
     * results whichever order it arrives in — i.e. reordering is a no-op for
     * input that was never misordered.
     */
    public function test_already_parent_first_input_is_reproduced_unchanged(): void
    {
        $db = $this->shippedConnection();

        $parentFirst = [
            ['table' => 'tf_messages', 'row' => $this->messageRow()],
            ['table' => 'tf_messages_media', 'row' => $this->mediaRow()],
            ['table' => 'tf_messages_entities', 'row' => $this->entityRow()],
        ];

        $inserts = $this->recordInserts($db, static fn (Connection $db): array => (new MirrorFactWriter)->write($db, $parentFirst));

        self::assertSame(
            ['tf_messages', 'tf_messages_media', 'tf_messages_entities'],
            $inserts,
            'a batch that already arrives parent-first must insert in exactly the caller\'s order — '
            .'the reorder is a no-op for it, not a re-grouping',
        );
        self::assertSame(3, $db->table('tf_messages_media')->count() + $db->table('tf_messages_entities')->count() + $db->table('tf_messages')->count());

        // And a row that cannot be placed AT ALL still degrades identically in
        // both orders. The orphan below is keyed on a DIFFERENT message id from
        // the parent row in the same batch, and that detail is the whole point:
        // an entities child sharing its parent's id would be placed the moment
        // the reorder lifts the parent ahead of it, so it would prove nothing
        // about unplaceability. A child whose parent exists in NO batch is the
        // honest instance — and it must still not count and must still speak.
        $orphan = ['table' => 'tf_messages_entities', 'row' => $this->entityRow(self::MESSAGE_ID + 1)];
        $parent = ['table' => 'tf_messages', 'row' => $this->messageRow()];
        $afterOrphan = (new MirrorFactWriter)->write($db, [$orphan, $parent]);
        $afterParent = (new MirrorFactWriter)->write($db, [$parent, $orphan]);

        self::assertSame($afterOrphan, $afterParent, 'an unplaceable row degrades identically in either order');
        self::assertSame(1, $afterOrphan['inserted'], 'the parent counts; the orphan does not');
        self::assertCount(1, $afterOrphan['fkClues'], 'the orphan still surfaces as a clue — ordering is not a licence to hide it');
    }

    /**
     * PROOF 4 — TERMINATION on a hostile relation graph.
     *
     * A naive topological sort on a cyclic graph does not return a wrong
     * answer; it returns NO answer, for ever, inside the ingest transaction. A
     * hang is strictly worse than the silent drop this change fixes: one loses a
     * row quietly, the other takes the page down. So the deadline here is a
     * real FORK and not an assertion, following the repo's existing pattern in
     * `VaultMtprotoAnswererHandshakeBudgetTest`: the child runs the reorder, the
     * parent SIGKILLs it if it has not exited, and a hang is therefore a RED
     * test rather than an infinite block.
     *
     * The hostile input is three shapes at once:
     *   - a genuine two-table FK CYCLE (`tf_cycle_a` ⇄ `tf_cycle_b`), which no
     *     shipped migration can express because the shipped schema is acyclic;
     *   - a SELF-referencing table carrying thousands of rows, which is what
     *     makes a row-level edge set quadratic — every row waits on every other
     *     — and is the shape a `foreach`-everything implementation melts on;
     *   - rows whose parents are in NO batch, so they stay genuinely
     *     unplaceable and must still degrade rather than loop.
     *
     * The child builds its OWN in-memory sqlite schema, because a forked child
     * must never touch the parent's PDO handle.
     */
    public function test_a_cyclic_and_hostile_row_set_terminates(): void
    {
        if (! function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl_fork required to bound the reorder with a real deadline');
        }

        $startedAt = microtime(true);
        $pid = pcntl_fork();
        if ($pid === -1) {
            self::markTestSkipped('pcntl_fork failed');
        }

        if ($pid === 0) {
            $exit = 0;
            try {
                $this->reorderHostileBatchInChild();
            } catch (\Throwable) {
                $exit = 1;
            }
            $this->childExit($exit);
        }

        $status = null;
        while (microtime(true) < $startedAt + self::DEADLINE_S) {
            if (pcntl_waitpid($pid, $status, WNOHANG) === $pid) {
                self::assertSame(
                    0,
                    (int) pcntl_wexitstatus($status),
                    'the parent-first reorder must degrade a cyclic batch and still exit cleanly',
                );

                return;
            }
            usleep(20000);
        }

        @posix_kill($pid, SIGKILL);
        pcntl_waitpid($pid, $status);

        self::fail(sprintf(
            'the reorder still running after %.1fs (deadline %.1fs): a topological sort over a cyclic relation '
            .'graph does not terminate, which is a WORSE defect than the silent drop it was written to fix',
            microtime(true) - $startedAt,
            self::DEADLINE_S,
        ));
    }

    /**
     * Runs in the forked child only. Builds a private schema carrying a real FK
     * cycle plus a large self-referencing table, and hands both to `write()`.
     *
     * The self-referencing table is the load-bearing half. It is the one shape
     * where the obvious implementation — materialise an edge from row i to row
     * j for every pair in the same table — is quadratic: 4000 rows means 16
     * million edges. The implementation in `parentFirst()` registers each row
     * under exactly ONE blocking parent and lets each table unblock its
     * waiters once, so this is linear, and the deadline proves it.
     */
    private function reorderHostileBatchInChild(): void
    {
        $paths = array_map(self::migrationPath(...), self::MIGRATIONS);
        $db = ShippedStoreSchema::capsuleFor(...$paths)->getConnection();
        $db->statement('PRAGMA foreign_keys = ON');

        // A genuine two-table FK cycle. Created as raw DDL rather than through
        // Blueprint because this schema has no shipped counterpart: the point is
        // to reach a graph the mirror cannot reach today, so that the guard is
        // tested before the guard is needed.
        $db->statement(
            'CREATE TABLE tf_cycle_a (account_id INTEGER NOT NULL, id INTEGER NOT NULL, b_id INTEGER,'
            .' PRIMARY KEY (account_id, id),'
            .' FOREIGN KEY (account_id, b_id) REFERENCES tf_cycle_b (account_id, id))'
        );
        $db->statement(
            'CREATE TABLE tf_cycle_b (account_id INTEGER NOT NULL, id INTEGER NOT NULL, a_id INTEGER,'
            .' PRIMARY KEY (account_id, id),'
            .' FOREIGN KEY (account_id, a_id) REFERENCES tf_cycle_a (account_id, id))'
        );
        // The quadratic shape: every row of this table is a potential parent of
        // every other row of it.
        $db->statement(
            'CREATE TABLE tf_cycle_self (account_id INTEGER NOT NULL, id INTEGER NOT NULL, parent_id INTEGER,'
            .' PRIMARY KEY (account_id, id),'
            .' FOREIGN KEY (account_id, parent_id) REFERENCES tf_cycle_self (account_id, id))'
        );

        $rows = [
            // The cycle: a1 waits for b1, b1 waits for a1. No order satisfies
            // both, so both are emitted in the caller's own order and the
            // database refuses whichever comes second.
            ['table' => 'tf_cycle_a', 'row' => ['account_id' => self::ACCOUNT, 'id' => 1, 'b_id' => 1]],
            ['table' => 'tf_cycle_b', 'row' => ['account_id' => self::ACCOUNT, 'id' => 1, 'a_id' => 1]],
        ];

        for ($id = 1; $id <= 4000; $id++) {
            $rows[] = ['table' => 'tf_cycle_self', 'row' => [
                'account_id' => self::ACCOUNT,
                'id' => $id,
                'parent_id' => $id === 4000 ? null : $id + 1,
            ]];
        }

        // And a real shipped child whose parent is in no batch at all: a
        // genuine drop, which must stay a drop.
        $rows[] = ['table' => 'tf_messages_media', 'row' => $this->mediaRow()];

        $result = (new MirrorFactWriter)->write($db, $rows);

        // The contract under a cycle and under a self-reference: every row is
        // still ATTEMPTED, nothing hangs, and the refusals are reported rather
        // than swallowed.
        self::assertIsInt($result['inserted']);
        self::assertNotSame([], $result['fkClues'], 'a cycle must degrade loudly in the ledger, not vanish');

        // And the self-referencing table is not quietly skipped: of the 4000
        // rows, exactly the one whose parent is NULL satisfies its own FK, and
        // exactly that one is in the mirror. If the sort had emitted any row
        // twice, or bailed out early, this count would say so.
        self::assertSame(
            1,
            $db->table('tf_cycle_self')->count(),
            'only the self-referencing row with a NULL parent can satisfy its own FK',
        );
        // The cycle's own two rows land NOWHERE, and that is the honest
        // outcome rather than a near miss: the cycle exit emits the parked rows
        // in the CALLER's own order, so `tf_cycle_a` goes out before the
        // `tf_cycle_b` it points at and is refused, and `tf_cycle_b` then goes
        // out before the `tf_cycle_a` it points at and is refused too. No
        // ordering of these two rows satisfies both FKs — that is what a cycle
        // IS — and the contract is that the batch degrades, terminates, and
        // says so, rather than hanging or silently discarding both.
        self::assertSame(0, $db->table('tf_cycle_a')->count());
        self::assertSame(0, $db->table('tf_cycle_b')->count());
    }

    /** Exit the forked child without running PHPUnit's shutdown handlers. */
    private function childExit(int $status): never
    {
        if (extension_loaded('FFI')) {
            try {
                $libc = \FFI::cdef('void _exit(int status);', 'libc.so.6');
                $libc->_exit($status);
            } catch (\Throwable) {
                // FFI disabled or not a glibc host — exit normally.
            }
        }

        exit($status);
    }

    /**
     * The shipped message schema, with FK ENFORCEMENT ON.
     *
     * The `PRAGMA` is the whole point of this file: `capsuleFor()` leaves it off
     * by default, and with it off an out-of-order child insert succeeds, so a
     * suite without this line cannot observe the defect it is meant to pin.
     */
    private function shippedConnection(): Connection
    {
        $this->previousContainer = Container::getInstance();

        $paths = array_map(self::migrationPath(...), self::MIGRATIONS);
        $db = ShippedStoreSchema::capsuleFor(...$paths)->getConnection();
        $db->statement('PRAGMA foreign_keys = ON');

        // Query events need an event dispatcher, and a bare Capsule has none —
        // which is exactly why the order had to be observed some other way
        // before this line. Attached to the CONNECTION, not to the container, so
        // no other suite inherits it.
        $db->setEventDispatcher(new Dispatcher(new Container));

        self::assertSame(
            1,
            (int) $db->selectOne('PRAGMA foreign_keys')->foreign_keys ?? 0,
            'foreign-key enforcement must be ON for these assertions to mean anything',
        );

        // Guard the premise: the shipped schema really does constrain the child,
        // which is what makes an out-of-order write lossy rather than merely
        // untidy.
        $foreignKeys = $db->getSchemaBuilder()->getForeignKeys('tf_messages_media');
        self::assertCount(1, $foreignKeys);
        self::assertSame('tf_messages', $foreignKeys[0]['foreign_table']);

        return $db;
    }

    /**
     * Run $write with a query listener attached and return the INSERTed tables
     * in the order they were issued.
     *
     * @return list<string>
     */
    private function recordInserts(Connection $db, \Closure $write): array
    {
        /** @var list<string> $inserts */
        $inserts = [];
        $db->listen(static function ($query) use (&$inserts): void {
            if (! str_contains($query->sql, 'insert into')) {
                return;
            }

            // The table name is the identifier immediately after the phrase; the
            // driver quotes it differently on sqlite and MySQL, and matching on
            // the shipped table names keeps this driver-agnostic.
            foreach (self::MIGRATION_TABLES as $table) {
                if (str_contains($query->sql, $table)) {
                    $inserts[] = $table;

                    return;
                }
            }
        });

        $write($db);

        return $inserts;
    }

    private static function migrationPath(string $file): string
    {
        return dirname(__DIR__, 2).'/src/Laravel/Migrations/'.$file;
    }

    /** @return array<string, int|string|null> */
    private function messageRow(): array
    {
        return [
            'account_id' => self::ACCOUNT,
            'id' => self::MESSAGE_ID,
            'peer_type' => 1,
            'peer_id' => 501558149,
            'constructor' => 'message',
            'date' => 1726000000,
            'message' => 'parent first please',
        ];
    }

    /**
     * FIXTURE CORRECTION (2026-10-03, Task 2.2). Every child row below now
     * carries the ENCLOSING MESSAGE's peer, because `tf_messages` and its
     * children are keyed (account_id, peer_type, peer_id, id[, position]) —
     * Telegram numbers messages PER CHAT, so `id` alone names no message and a
     * child keyed on it re-collapses the same facts across dialogs. These are
     * the rows `MirrorFactDecomposer` produces and `MirrorFactWriter` places,
     * and `placeRow()` derives its upsert key from the LIVE primary key, so a
     * row that could not cover every key column would be skipped with a clue
     * rather than stored. Every assertion in this class is unchanged.
     */

    /**
     * The PREFIXED spelling is not a typo: `tf_messages_media` already owns
     * `peer_type`/`peer_id` for a peer carried INSIDE its own payload, so the
     * enclosing message's scope is `message_peer_type`/`message_peer_id`.
     *
     * @return array<string, int|string|null>
     */
    private function mediaRow(): array
    {
        return [
            'account_id' => self::ACCOUNT,
            'message_peer_type' => 1,
            'message_peer_id' => 501558149,
            'id' => self::MESSAGE_ID,
            'constructor' => 'messageMediaEmpty',
        ];
    }

    /** @return array<string, int|string|null> */
    private function entityRow(int $messageId = self::MESSAGE_ID): array
    {
        return [
            'account_id' => self::ACCOUNT,
            'peer_type' => 1,
            'peer_id' => 501558149,
            'id' => $messageId,
            'position' => 0,
            'constructor' => 'messageEntityBold',
            'offset' => 0,
            'length' => 1,
        ];
    }

    /** @return array<string, int|string|null> */
    private function markupRowRow(): array
    {
        return [
            'account_id' => self::ACCOUNT,
            'peer_type' => 1,
            'peer_id' => 501558149,
            'id' => self::MESSAGE_ID,
            'position' => 0,
            'constructor' => 'keyboardButtonRow',
        ];
    }

    /** @return array<string, int|string|null> */
    private function buttonRow(): array
    {
        return [
            'account_id' => self::ACCOUNT,
            'peer_type' => 1,
            'peer_id' => 501558149,
            'id' => self::MESSAGE_ID,
            'position' => 0,
            'parent_position' => 0,
            'constructor' => 'keyboardButton',
            'text' => 'press me',
        ];
    }
}
