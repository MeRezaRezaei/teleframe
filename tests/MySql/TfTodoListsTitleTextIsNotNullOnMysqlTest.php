<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\MySql;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;

/**
 * LANE `MySqlCopyingGapFix` — `tf_todo_lists_title.text` is NOT NULL on the
 * ENGINE, which is the only place nullability is decided.
 *
 * ## WHY THIS HALF LIVES HERE AND NOT IN tests/Schema/Mirror
 *
 * `2026_10_10_000200_require_tf_todo_lists_title_text` changes a shipped
 * column's nullability, and the rule it answers is `bin/audit-fidelity.php`
 * rule **C4**:
 *
 *     C4  tf_todo_lists_title  column:text
 *         TL text:string — a wire field with no conditional marker is ALWAYS present on the wire
 *         ours: the column is NULLABLE, so a row can carry a value Telegram has no encoding for
 *
 * The SQLite half of that fix is proved by
 * `tests/Schema/Mirror/TfTodoListsTitleTextRequiredTest`. This half exists
 * because the whole point of the finding is that **`--engine=sqlite` reported
 * `COPYING-GAP=0` while `--engine=mysql` reported it** — a green SQLite run
 * describing a state the SQLite schema did not actually hold. A test that only
 * ran on SQLite would have been green before the fix and green after it, and
 * would therefore have proved nothing at all.
 *
 * ## WHY INFORMATION_SCHEMA AND NOT A RE-READ OF THE MIGRATION
 *
 * The change is asserted by asking `information_schema` what the engine
 * stored, not by re-reading the Blueprint that asked for it. A migration that
 * silently failed to apply on this engine — a dialect refusal, a silently
 * skipped step — would still satisfy an assertion made against its own source.
 * `MySqlTestCase::migratedMysqlConnection()` already migrates FIRST and only
 * then hands out the connection, which is what makes this ordering meaningful.
 */
final class TfTodoListsTitleTextIsNotNullOnMysqlTest extends MySqlTestCase
{
    /** The table under test. */
    private const TABLE = 'tf_todo_lists_title';

    /** The column under test. */
    private const COLUMN = 'text';

    /** The path fragment naming the migration under test. */
    private const MIGRATION = '2026_10_10_000200';

    /**
     * The column's `information_schema` row, read off the engine.
     *
     * @return array<string, mixed>
     */
    private function columnRow(Connection $db): array
    {
        $rows = $db->select(
            'SELECT IS_NULLABLE, COLUMN_DEFAULT, COLUMN_TYPE, COLUMN_KEY '
            .'FROM information_schema.COLUMNS '
            .'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [self::TABLE, self::COLUMN],
        );

        self::assertCount(1, $rows, self::TABLE.'.'.self::COLUMN.' was not found on the engine at all.');

        return (array) $rows[0];
    }

    // ------------------------------------------------------------------
    // PROOF 1 — the engine records it NOT NULL
    // ------------------------------------------------------------------

    public function test_the_engine_records_the_column_not_null(): void
    {
        $row = $this->columnRow($this->migratedMysqlConnection());

        self::assertSame(
            'NO',
            (string) $row['IS_NULLABLE'],
            self::TABLE.'.'.self::COLUMN.' is nullable on real MySQL. The TL declares the field always '
            .'present (TL_telegram_v227.tl:2067 todoList.title:TextWithEntities -> :1668 '
            .'textWithEntities.text:string), so a nullable column admits a row state Telegram has no encoding '
            .'for. This is the finding `bin/audit-fidelity.php --engine=mysql` reports as C4.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 2 — and with NO default, which is a fact and not an omission
    // ------------------------------------------------------------------

    public function test_the_column_carries_no_default(): void
    {
        $row = $this->columnRow($this->migratedMysqlConnection());

        self::assertNull(
            $row['COLUMN_DEFAULT'],
            self::TABLE.'.'.self::COLUMN.' grew a DEFAULT ('.var_export($row['COLUMN_DEFAULT'], true).'). A TL '
            .'`string` has no default: the empty string is a VALUE Telegram sends, not a fallback the '
            .'transport substitutes, so a default here would invent a value the source never produces. It is '
            .'also physically impossible on this engine — MySQL refuses a default on TEXT with ERROR 1101 '
            .'(BLOB/TEXT column can\'t have a default value) — so this can only be a change() that re-declared '
            .'the column with a default rather than a real second defect.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 3 — the change() did not cost the table its foreign keys
    // ------------------------------------------------------------------

    public function test_both_foreign_keys_survived_the_change(): void
    {
        $db = $this->migratedMysqlConnection();

        $legs = [];
        foreach ($db->select(
            'SELECT k.CONSTRAINT_NAME AS name, k.COLUMN_NAME AS col, k.REFERENCED_TABLE_NAME AS parent, '
            .'k.REFERENCED_COLUMN_NAME AS parent_col, r.DELETE_RULE AS on_delete '
            .'FROM information_schema.KEY_COLUMN_USAGE k '
            .'JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA '
            .'AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME '
            .'WHERE k.TABLE_SCHEMA = DATABASE() AND k.TABLE_NAME = ? '
            .'ORDER BY k.CONSTRAINT_NAME, k.ORDINAL_POSITION',
            [self::TABLE],
        ) as $row) {
            $row = (array) $row;
            $legs[$row['name']]['columns'][] = $row['col'];
            $legs[$row['name']]['parent_columns'][] = $row['parent_col'];
            $legs[$row['name']]['target'] = (string) $row['parent'];
            $legs[$row['name']]['on_delete'] = strtoupper((string) $row['on_delete']);
        }

        // Composite legs are read one column at a time, so BOTH sides are
        // accumulated as lists and folded together afterwards — a constraint
        // that lost its second leg then shows up as a shorter list rather than
        // silently becoming two single-column constraints.
        $folded = [];
        foreach ($legs as $name => $leg) {
            $folded[$name] = implode(',', $leg['columns'])
                .' -> '.$leg['target'].'.'.implode(',', $leg['parent_columns'])
                .' @ '.$leg['on_delete'];
        }
        ksort($folded);

        self::assertContains(
            'account_id -> telegram_accounts.user_id @ CASCADE',
            array_values($folded),
            'the tenant foreign key did not survive the nullability change on MySQL. A table with a NOT NULL '
            .'column and no constraint on its tenant accepts rows for accounts that do not exist. Found: '
            .json_encode($folded).'.',
        );

        self::assertContains(
            'account_id,todo_list_id -> tf_todo_lists.account_id,todo_list_id @ CASCADE',
            array_values($folded),
            'the two-column parent foreign key did not survive, or lost a leg and became two single-column '
            .'constraints. Found: '.json_encode($folded).'.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 4 — this migration is IN the dial the engine just executed
    // ------------------------------------------------------------------

    public function test_the_migration_is_part_of_the_shipped_dial_this_engine_ran(): void
    {
        $ran = false;
        foreach (UpdateIngestor::migrationPaths() as $path) {
            $inDir = is_dir($path) ? (glob($path.'/*.php') ?: []) : [$path];
            foreach ($inDir as $file) {
                if (str_contains(basename($file), self::MIGRATION)) {
                    $ran = true;
                }
            }
        }

        self::assertTrue(
            $ran,
            'no file matching '.self::MIGRATION.' is reachable from UpdateIngestor::migrationPaths(), so the '
            .'dial this engine executed does not include it. Every assertion in this file would then be '
            .'describing a schema no install builds.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 5 — a real row lands, and the empty string round-trips
    // ------------------------------------------------------------------

    public function test_a_row_carrying_the_empty_string_lands_and_round_trips(): void
    {
        $db = $this->migratedMysqlConnection();
        $tenant = 1724372757;

        $db->table('telegram_accounts')->insert(['label' => 'probe', 'type' => 'user', 'user_id' => $tenant]);
        $db->table('tf_todo_lists')->insert(['account_id' => $tenant, 'todo_list_id' => 6]);

        $db->table(self::TABLE)->insert([
            'account_id' => $tenant,
            'todo_list_id' => 6,
            'title' => 'probe',
            self::COLUMN => '',
        ]);

        self::assertSame(
            '',
            (string) $db->table(self::TABLE)->where('todo_list_id', 6)->value(self::COLUMN),
            'the empty string did not round-trip. NOT NULL forbids the NULL, not `\'\'` — and `text:""` is an '
            .'ordinary value Telegram sends for an empty title, so a failure here is the migration rejecting '
            .'real wire data, not the column working.',
        );

        // And the state the migration exists to forbid is genuinely refused now.
        //
        // The PARENT IS PLANTED FIRST, and that is the whole point: an insert
        // omitting `todo_list_id` 7's parent would be refused by the foreign key
        // with MySQL 1452, which is a refusal of the wrong thing and would prove
        // nothing about the NOT NULL constraint this migration adds.
        $db->table('tf_todo_lists')->insert(['account_id' => $tenant, 'todo_list_id' => 7]);

        $refused = null;
        try {
            $db->table(self::TABLE)->insert([
                'account_id' => $tenant,
                'todo_list_id' => 7,
                'title' => 'probe',
            ]);
        } catch (QueryException $e) {
            $refused = $e;
        }

        self::assertNotNull(
            $refused,
            'an insert that OMITS the always-present field was ACCEPTED, which means the column is still '
            .'nullable on this engine and the C4 finding is unfixed here.',
        );

        // MEASURED 2026-10-10 on MySQL 8.4.11 in strict mode: an INSERT that
        // OMITS a NOT NULL column carrying no default is refused with **1364**
        // ("Field 'text' doesn't have a default value"), NOT with 1048 ("Column
        // cannot be null"), which is what an explicit `NULL` produces. Both
        // refuse; they are different faults and this file pins the one that
        // actually occurs, because `MirrorFactDecomposer` OMITS the key when the
        // field is absent rather than writing a NULL into it.
        //
        // 1364 is also already owned by the writer, so this is a covered refusal
        // rather than a new failure mode:
        //   src/Schema/Mirror/MirrorFactWriter.php:351
        //     VALUE_NOT_STORABLE_MYSQL_CODES = [1265, 1364, 1366, 1406]
        //
        // THE HONEST LIMIT, stated because a reader will otherwise over-read
        // this assertion: the refusal is a property of STRICT mode. On a
        // NON-strict server MySQL substitutes `''` and the impossible row is
        // stored anyway — which is why the assertion below also checks the row
        // count rather than trusting the exception alone. Laravel's MySQL
        // connector defaults `strict => true`, and the shipped track runs with
        // it (`tests/MySql/MySqlTestCase.php:156`), so a server configured
        // without it would keep accepting these rows and this test would fail
        // loudly rather than pass quietly.
        self::assertSame(
            1364,
            (int) ($refused->errorInfo[1] ?? 0),
            'the refusal must be MySQL 1364 (field has no default value) in strict mode. Any other code means '
            .'the insert failed for an unrelated reason — most likely the parent row for todo_list_id 7 does '
            .'not exist, which would be a FOREIGN KEY refusal and not evidence the NOT NULL constraint bit.',
        );

        self::assertSame(
            0,
            $db->table(self::TABLE)->where('todo_list_id', 7)->count(),
            'the row that omitted the always-present field was STORED. On this connection strict mode is on, so '
            .'it should have been refused; a stored row means the constraint is not doing its job here.',
        );
    }
}
