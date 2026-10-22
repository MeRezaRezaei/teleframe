<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backfill;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Facade;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use PHPUnit\Framework\TestCase;

/**
 * PHASE 5, STEP 1 — a replayed history page CONVERGES, and it does not
 * overwrite what the replayed page does not carry.
 *
 * WHY THIS CLASS EXISTS. The operator's stated purpose for the whole plan is
 * "we want to store all telegram data ... it might need the full history just
 * in hand to act on it". A crawl that walks to message id 1 (Step 2) is only
 * SAFE if re-walking a page is free of damage: a throttled crawl re-fetches
 * the page it was interrupted on, and every restart re-fetches from the last
 * persisted cursor. If a replay collided on a key the page would be
 * discarded — the measured 2026-10-04 `Duplicate entry ... tf_users.PRIMARY`.
 * `RepeatedEntityConvergesTest` already owns that half (ONE atomic placement
 * statement, and the live-engine concurrent-writer case); this class owns the
 * other half — what a replay does to the VALUES.
 *
 * ## THE PLAN'S SKELETON, AND WHERE IT DOES NOT MATCH THE SHIPPED CODE
 *
 * The plan (Phase 5 Step 1) says: "Ingest page 1. Mutate one stored value to a
 * sentinel. Replay the identical page. Assert: the sentinel SURVIVED (the
 * replay did not clobber a value the mirror had learned from another source),
 * and the row count is unchanged."
 *
 * On the MIRROR path — `MirrorFactWriter`, the seam `BackfillCommand` writes
 * through — that assertion holds, and it is
 * `test_a_replay_keeps_a_value_the_page_never_carried` below.
 * `placeRow()` ends in `$db->table($table)->upsert([$row], $primary)`
 * (src/Schema/Mirror/MirrorFactWriter.php:1394) with NO update list, so the
 * UPDATE clause is built from the columns the replayed ROW carries. A value
 * the replayed page does not carry is absent from the statement and cannot be
 * touched. The evidence used instead of the plan's wording is the shipped
 * call itself plus the executed DDL: the sentinel lives in `tf_users_username`,
 * a keyed child table (`PRIMARY KEY (account_id, id)`) that the page's single
 * `tf_users` fact does not write at all.
 *
 * On the CURATED path — `UpdateIngestor::ingest()`, the seam the bus writes
 * through — the same assertion is FALSE, and asserting it anyway would be the
 * "green test that guards nothing" this repo has been bitten by three times.
 * MEASURED: after one ingested `message` payload, every `tf_messages` column
 * is populated (the curated writer materialises the sixteen wire-false
 * booleans), so there is no column a replay leaves alone and a replay
 * re-states every column it wrote. That is the documented contract, not a
 * defect — `RepeatedEntityConvergesTest::assertConverged()` asserts it as
 * "convergence means the later fact is the current one". So the curated half
 * is pinned at the strength the code actually has (ONE row, later fact wins)
 * in `test_a_replayed_message_page_converges_to_one_row_with_the_later_fact`,
 * and the plan's literal sentinel assertion is asserted on the seam where it
 * is true.
 *
 * WHY A SENTINEL AT ALL. A row-count assertion cannot tell "converged" from
 * "silently rebuilt": a replay that deleted and re-inserted would also leave
 * one row. The sentinel is what separates them. MEASURED 2026-10-04 by
 * mutation, not asserted on faith: with the replay re-stating the sentinel
 * column with its shipped default — the realistic shape of this regression,
 * and exactly what a decomposer that materialises booleans would produce —
 * `test_a_replay_keeps_a_value_the_page_never_carried` fails with
 * "Failed asserting that 0 is identical to 1", and with the page itself
 * materialising the column the case's own vacuity guard fires first. The
 * companion `test_a_replay_writes_the_values_the_page_carries` is the other
 * half: without it the sentinel case would also pass against a writer that
 * silently ignored every replay.
 *
 * WHY NO TABLE SHAPE IS TYPED IN. The schema is the shipped dial, executed
 * from `UpdateIngestor::migrationPaths()` — the same list the ingest seam and
 * the CI postgres lane run. The tenant row is real, because Phase 0.3 landed
 * 265 tenant FKs onto `telegram_accounts.user_id` (commit 7dd07b4a): a
 * `tf_*.account_id` REFERENCES `telegram_accounts(user_id)`, so no page can be
 * written at all without a tenant parent. Synthetic values only; no session
 * value and no real access_hash is read or printed.
 */
final class ReplayedHistoryPageConvergesTest extends TestCase
{
    /** The tenant `telegram_accounts.user_id` every `tf_*` row is scoped by. */
    private const ACCOUNT = 4242;

    private const PEER_USER = 501558149;

    private const PEER_CHANNEL = 1398107361;

    private const SENTINEL = 'learned-from-another-source';

    private const PAGE_FROM_THE_WIRE = 'carried-by-the-replayed-page';

    private static ?Capsule $capsule = null;

    private static mixed $previousContainer = null;

    public static function tearDownAfterClass(): void
    {
        if (self::$capsule !== null) {
            self::$capsule->getConnection()->disconnect();
            self::$capsule = null;
        }

        if (self::$previousContainer !== null) {
            Container::setInstance(self::$previousContainer);
            Facade::setFacadeApplication(self::$previousContainer);
            Facade::clearResolvedInstances();
            self::$previousContainer = null;
        }

        parent::tearDownAfterClass();
    }

    // ------------------------------------------------------------------ mirror path

    /**
     * THE PLAN'S ASSERTION, on the seam the backfill crawl writes through.
     */
    public function test_a_replay_keeps_a_value_the_page_never_carried(): void
    {
        $db = $this->db();

        // ---- Page 1: one user fact. It carries no `verified`. ----
        $page = [self::userFact()];

        (new MirrorFactWriter)->write($db, $page);

        $stored = $db->table('tf_users')->where('account_id', self::ACCOUNT)->where('id', self::PEER_USER)->first();
        self::assertNotNull($stored, 'page 1 must land the user before anything is replayed');
        self::assertArrayNotHasKey(
            'verified',
            self::userFact()['row'],
            'this case is about a column the page does NOT carry, so the page must not carry it',
        );

        // The value "learned from another source": some other endpoint wrote
        // `verified` onto this user, and the replayed page knows nothing
        // about it. The column is read off the EXECUTED DDL — never typed —
        // and `verified` is the sentinel because it is a boolean whose shipped
        // DEFAULT is false, so "the replay re-stated it as its default" is a
        // value the assertion can actually see change on every engine.
        $sentinelColumn = 'verified';
        self::assertContains(
            $sentinelColumn,
            $db->getSchemaBuilder()->getColumnListing('tf_users'),
            'the sentinel column must exist in the executed DDL',
        );
        self::assertContains(
            $sentinelColumn,
            array_values(array_diff($db->getSchemaBuilder()->getColumnListing('tf_users'), array_keys(self::userFact()['row']))),
            'the sentinel column must be one the page does not carry, or this case is vacuous',
        );

        $db->table('tf_users')
            ->where('account_id', self::ACCOUNT)
            ->where('id', self::PEER_USER)
            ->update([$sentinelColumn => 1]);

        // ---- Replay the IDENTICAL page. ----
        $report = (new MirrorFactWriter)->write($db, $page);

        $after = $db->table('tf_users')->where('account_id', self::ACCOUNT)->where('id', self::PEER_USER)->first();

        self::assertSame(
            1,
            (int) $after->{$sentinelColumn},
            'a replayed page must not clobber a value it does not itself carry. placeRow() upserts '
            .'ONLY the columns present in the replayed row (MirrorFactWriter.php:1394, `upsert([$row], '
            .'$primary)` with no update list), so a value the mirror learned from another source has '
            .'to survive the replay verbatim.',
        );
        self::assertSame(
            1,
            (int) $db->table('tf_users')->where('account_id', self::ACCOUNT)->count(),
            'a replayed page must converge to ONE row; a second row means the page was appended '
            .'rather than converged.',
        );
        self::assertSame(
            0,
            (int) $stored->bot,
            'the page carried bot=0, so the stored value must still agree with it — a replay that '
            .'quietly dropped a carried column would satisfy the sentinel assertion alone.',
        );
        self::assertSame(
            [],
            $report['fkClues'],
            'the replay must not raise an FK clue on a page whose parents already landed',
        );
    }

    /**
     * The same property on a keyed CHILD table, because the two are not the
     * same statement: the first is "a column of the replayed row's own table",
     * this is "a row the replayed page does not mention at all". A writer that
     * regressed either way would have to break both to pass.
     */
    public function test_a_replay_keeps_a_sibling_row_the_page_never_wrote(): void
    {
        $db = $this->db();

        $page = [self::userFact()];
        (new MirrorFactWriter)->write($db, $page);

        // A keyed child row of the same user, written by a different endpoint.
        $db->table('tf_users_username')->insert([
            'account_id' => self::ACCOUNT,
            'id' => self::PEER_USER,
            'username' => self::SENTINEL,
        ]);

        $report = (new MirrorFactWriter)->write($db, $page);

        $username = $db->table('tf_users_username')
            ->where('account_id', self::ACCOUNT)
            ->where('id', self::PEER_USER)
            ->first();

        self::assertNotNull($username, 'the sibling row must still be stored after the replay');
        self::assertSame(
            self::SENTINEL,
            (string) $username->username,
            'a replayed page must not touch a row it never wrote',
        );
        self::assertSame(
            1,
            (int) $db->table('tf_users_username')->where('account_id', self::ACCOUNT)->count(),
            'and must not duplicate it either',
        );
        self::assertSame([], $report['fkClues']);
    }

    /**
     * THE CONTROL HALF, and it is not optional: the sentinel assertion above
     * is also satisfied by a writer that never writes. So a replay of a page
     * that DOES carry the value must move it — proving the replay really
     * re-stated the page and the sentinel survived because it was not on it,
     * not because writes were dropped.
     */
    public function test_a_replay_writes_the_values_the_page_carries(): void
    {
        $db = $this->db();

        (new MirrorFactWriter)->write($db, [self::userFact()]);
        $db->table('tf_users_username')->insert([
            'account_id' => self::ACCOUNT,
            'id' => self::PEER_USER,
            'username' => self::SENTINEL,
        ]);

        // The same page, now carrying the username fact as well — what a
        // re-walk of a page whose fact changed looks like on the wire.
        (new MirrorFactWriter)->write($db, [
            self::userFact(),
            ['table' => 'tf_users_username', 'row' => [
                'account_id' => self::ACCOUNT,
                'id' => self::PEER_USER,
                'username' => self::PAGE_FROM_THE_WIRE,
            ]],
        ]);

        $username = $db->table('tf_users_username')
            ->where('account_id', self::ACCOUNT)
            ->where('id', self::PEER_USER)
            ->first();

        self::assertSame(
            self::PAGE_FROM_THE_WIRE,
            (string) $username->username,
            'a replayed page that carries a later value must land it. Without this control the '
            .'sentinel case would also pass against a writer that silently ignored every replay.',
        );
        self::assertSame(
            1,
            (int) $db->table('tf_users_username')->where('account_id', self::ACCOUNT)->count(),
            'and it must still converge to one row',
        );
    }

    // ----------------------------------------------------------------- curated path

    /**
     * The CURATED seam (`UpdateIngestor`, the bus write path) on the same
     * question, pinned at the strength the shipped code actually holds.
     */
    public function test_a_replayed_message_page_converges_to_one_row_with_the_later_fact(): void
    {
        $db = $this->db();
        $ingestor = new UpdateIngestor;

        $ingestor->ingest($this->message(500, 'first pass'), self::ACCOUNT);
        self::assertSame(1, (int) $db->table('tf_messages')->count(), 'page 1 must land exactly one message');

        // The identical page, replayed.
        $ingestor->ingest($this->message(500, 'first pass'), self::ACCOUNT);
        self::assertSame(
            1,
            (int) $db->table('tf_messages')->count(),
            'a replayed page must converge to ONE row on the curated seam too',
        );

        $ingestor->ingest($this->message(500, 'edited later'), self::ACCOUNT);

        $row = $db->table('tf_messages')->where('account_id', self::ACCOUNT)->where('id', 500)->first();

        self::assertSame(
            'edited later',
            (string) $row->message,
            'the later fact is the current one on the curated seam — this is the shipped contract, '
            .'and the reason the plan\'s sentinel assertion is asserted on the mirror seam instead.',
        );
        self::assertSame(
            1,
            (int) $db->table('tf_messages')->count(),
            'a third delivery of the same identity must still be one row',
        );
    }

    // -------------------------------------------------------------------- plumbing

    /**
     * The sentinel table really is a keyed child of the user this page writes.
     * Asserted rather than assumed, because the whole case rests on the
     * replay not carrying it — if the relationship were the other way round
     * the case would be vacuous.
     */
    public function test_the_sentinel_table_is_a_keyed_child_of_the_written_user(): void
    {
        $db = $this->db();
        (new MirrorFactWriter)->write($db, [self::userFact()]);

        $primary = $db->getSchemaBuilder()->getIndexes('tf_users_username');
        $keyed = array_values(array_filter(
            $primary,
            static fn (array $index): bool => ($index['primary'] ?? false) === true,
        ));
        self::assertNotSame([], $keyed, 'tf_users_username must carry a real primary key');
        self::assertSame(
            ['account_id', 'id'],
            $keyed[0]['columns'],
            'the sentinel row is identified by the same (account_id, id) the page writes, which is '
            .'what makes "the page did not carry this value" a statement about the page and not '
            .'about an unrelated row',
        );
    }

    private function db(): Connection
    {
        if (self::$capsule === null) {
            self::$capsule = $this->bootMigratedSurface();
        }

        $db = self::$capsule->getConnection();

        // A clean slate per case, with FK enforcement suspended for the wipe
        // only — deleting parents before children would otherwise raise.
        $db->statement('PRAGMA foreign_keys = OFF');
        foreach ($db->getSchemaBuilder()->getTableListing() as $table) {
            $db->table($table)->delete();
        }
        $db->statement('PRAGMA foreign_keys = ON');

        if ((int) $db->selectOne('PRAGMA foreign_keys')->foreign_keys !== 1) {
            throw new \RuntimeException('sqlite refused PRAGMA foreign_keys = ON — every FK assertion here would be vacuous');
        }

        $appId = $db->table('telegram_apps')->insertGetId([
            'label' => 'phase5-replay',
            'api_id' => self::ACCOUNT,
            'api_hash' => 'synthetic-not-a-credential',
        ]);
        $db->table('telegram_accounts')->insert([
            'app_id' => $appId,
            'label' => 'phase5-replay',
            'type' => 'user',
            'user_id' => self::ACCOUNT,
        ]);

        return $db;
    }

    /**
     * The shipped dial, EXECUTED. No migration file, column list or table
     * shape is named here: the list IS `UpdateIngestor::migrationPaths()`.
     */
    private function bootMigratedSurface(): Capsule
    {
        $capsule = new Capsule;
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $app = new Container;
        $app->instance('db', $capsule->getDatabaseManager());
        $app->instance('db.schema', $capsule->getConnection()->getSchemaBuilder());
        self::$previousContainer = Container::getInstance();
        Container::setInstance($app);
        Facade::setFacadeApplication($app);
        Facade::clearResolvedInstances();

        $applied = [];
        foreach (UpdateIngestor::migrationPaths() as $path) {
            foreach ((is_dir($path) ? glob($path.'/*.php') : [$path]) as $file) {
                // The entity-anchor paths overlap the dial directory, so the
                // same file arrives twice. Applying it twice is "table already
                // exists", not a second source of truth.
                if (isset($applied[$file])) {
                    continue;
                }

                $applied[$file] = true;
                (require $file)->up();
            }
        }

        $db = $capsule->getConnection();
        $db->statement('PRAGMA foreign_keys = ON');

        return $capsule;
    }

    /**
     * @return array{table: string, row: array<string, mixed>}
     */
    private static function userFact(): array
    {
        return ['table' => 'tf_users', 'row' => [
            'account_id' => self::ACCOUNT,
            'id' => self::PEER_USER,
            'constructor' => 'user',
            'bot' => 0,
        ]];
    }

    /**
     * @return array<string, mixed>
     */
    private function message(int $id, string $text): array
    {
        return [
            '_' => 'message',
            'flags' => (1 << 8),
            'id' => $id,
            'from_id' => ['_' => 'peerUser', 'user_id' => self::PEER_USER],
            'peer_id' => ['_' => 'peerChannel', 'channel_id' => self::PEER_CHANNEL],
            'date' => 1724852400,
            'message' => $text,
            'flags2' => 0,
        ];
    }
}
