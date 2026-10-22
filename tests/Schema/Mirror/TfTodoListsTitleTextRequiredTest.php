<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use Illuminate\Database\Connection;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlParam;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;

/**
 * `tf_todo_lists_title.text` must be NOT NULL, because the wire always sends it.
 *
 * ## WHY THIS FILE EXISTS
 *
 * `bin/audit-fidelity.php` reports, on `--engine=mysql` only:
 *
 *     C4  tf_todo_lists_title  column:text
 *         TL text:string — a wire field with no conditional marker is ALWAYS present on the wire
 *         ours: the column is NULLABLE, so a row can carry a value Telegram has no encoding for
 *
 * `2026_10_10_000200_require_tf_todo_lists_title_text` is the fix. This file
 * proves the fix on the engine the change is actually made on — the executed
 * dial — and, just as importantly, proves that the change does NOT quietly cost
 * the table its two foreign keys, which is the failure mode a `change()` carries
 * on SQLite because SQLite rebuilds the table to apply one.
 *
 * ## THE PREMISE IS READ FROM THE TL, NOT TRANSCRIBED
 *
 * `test_the_wire_field_this_column_carries_is_required_at_both_hops()` parses
 * `schema/sources/TL_telegram_v227.tl` with the repository's own parser and
 * asserts the invariant the migration encodes: EVERY constructor of `TodoList`
 * declares `title` with no conditional marker, and EVERY constructor of
 * `TextWithEntities` declares `text` with no conditional marker. That is what
 * "always present on the wire" means, and a transcription of two quoted lines
 * could not notice Telegram adding `title:flags.31?TextWithEntities`.
 */
final class TfTodoListsTitleTextRequiredTest extends RelationConstraintTestCase
{
    /** The path fragment naming THIS migration's file. */
    private const MIGRATION = '2026_10_10_000200';

    /** Its full filename. */
    private const MIGRATION_FILE = '2026_10_10_000200_require_tf_todo_lists_title_text.php';

    /** The table under test. */
    private const TABLE = 'tf_todo_lists_title';

    /** The column under test. */
    private const COLUMN = 'text';

    /** A tenant every fixture plants first, so the tenant FK cannot be what refuses. */
    private const TENANT = 1724372757;

    /** The repository root, derived from this file rather than assumed. */
    private const PACKAGE_ROOT = __DIR__.'/../../..';

    /**
     * The table's outbound foreign keys, as `child_columns -> parent_columns`.
     *
     * MEASURED on a booted dial, not transcribed: `tf_todo_lists_title` declares
     * exactly two, and this file re-derives the pair on every run so a third
     * arriving cannot pass unnoticed. Both matter here because `change()` on
     * SQLite rebuilds the table, and a rebuild is precisely the operation that
     * drops constraints nobody restated.
     *
     * @return list<string>
     */
    private function expectedForeignKeys(): array
    {
        return [
            'account_id -> telegram_accounts.user_id',
            'account_id -> tf_todo_lists.account_id',
            'todo_list_id -> tf_todo_lists.todo_list_id',
        ];
    }

    /**
     * Every shipped migration, in apply order.
     *
     * Read from `UpdateIngestor::migrationPaths()` — the list the ingest seam
     * and the MySQL lane run — rather than a glob, because a glob here would be
     * a second copy of the dial and a second copy drifts.
     *
     * @return list<string>
     */
    private function migrations(bool $withholdThisMigration = false): array
    {
        $files = [];
        $seen = [];

        foreach (UpdateIngestor::migrationPaths() as $path) {
            $inDir = is_dir($path) ? (glob($path.'/*.php') ?: []) : [$path];
            sort($inDir);

            foreach ($inDir as $file) {
                $key = realpath($file) ?: $file;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                if ($withholdThisMigration && str_contains(basename($file), self::MIGRATION)) {
                    continue;
                }

                $files[] = $file;
            }
        }

        return $files;
    }

    /** The migration object, required afresh so a re-read sees a fresh instance. */
    private function migration(): object
    {
        $migration = require self::PACKAGE_ROOT.'/src/Laravel/Migrations/'.self::MIGRATION_FILE;

        self::assertIsObject($migration, 'the migration file did not return an object');

        return $migration;
    }

    /**
     * `PRAGMA table_info` row for `TABLE.COLUMN`, read off the engine.
     *
     * @return array<string, mixed>|null
     */
    private function columnInfo(Connection $db, string $table, string $column): ?array
    {
        foreach ($db->select('SELECT * FROM pragma_table_info(?) WHERE name = ?', [$table, $column]) as $row) {
            return (array) $row;
        }

        return null;
    }

    /** Whether the engine records `TABLE.COLUMN` as NOT NULL. */
    private function isNotNull(Connection $db, string $table, string $column): bool
    {
        $info = $this->columnInfo($db, $table, $column);

        self::assertNotNull($info, "{$table}.{$column} does not exist on a booted shipped dial.");

        return (int) $info['notnull'] === 1;
    }

    /**
     * The table's outbound foreign-key legs, as readable strings.
     *
     * @return list<string>
     */
    private function foreignKeyLegs(Connection $db, string $table): array
    {
        // `foreign_key_list` is NOT a table-valued pragma, so it cannot take a
        // bound parameter — MEASURED: `PRAGMA foreign_key_list(?)` is a syntax
        // error ("near \"?\": syntax error"), while `pragma_table_info(?)` in the
        // sibling helper above binds fine. Hence the literal, which is safe
        // because every caller passes a class constant.
        $legs = [];
        foreach ($db->select('PRAGMA foreign_key_list('.$table.')') as $row) {
            $row = (array) $row;
            $legs[] = $row['from'].' -> '.$row['table'].'.'.$row['to'];
        }
        sort($legs);

        return $legs;
    }

    // ------------------------------------------------------------------
    // PROOF 1 — the premise, read from the TL rather than transcribed
    // ------------------------------------------------------------------

    public function test_the_wire_field_this_column_carries_is_required_at_both_hops(): void
    {
        $scheme = TlParser::parseFile(self::PACKAGE_ROOT.'/schema/sources/TL_telegram_v227.tl');

        // Hop 1: `TodoList` — every constructor must declare `title` unconditionally.
        $todoList = $scheme->ensureType('TodoList');
        self::assertNotSame([], $todoList->constructors(), 'the TL declares no TodoList constructor.');

        foreach ($todoList->constructors() as $constructor) {
            $title = $this->paramNamed($constructor->allParams(), 'title');

            self::assertNotNull(
                $title,
                'TodoList constructor '.$constructor->name.' no longer declares a `title` field. The catalog '
                .'declares `["title","FK→TextWithEntities"]` for this table, so a rename here means the '
                .'column this migration constrains is no longer the column the wire names — re-derive the '
                .'column before trusting NOT NULL.',
            );

            self::assertNull(
                $title->conditional(),
                'TodoList.'.$constructor->name.'.'.$title->name.' became CONDITIONAL ('
                .$title->raw.'). A conditional field may legitimately be absent, which is exactly the state '
                .'NOT NULL forbids — this migration would then reject a legitimate row.',
            );

            self::assertSame(
                'TextWithEntities',
                $title->baseType(),
                'TodoList.title changed its base type to '.$title->baseType().'. The column then carries a '
                .'different TL type than the one this migration reasons about.',
            );
        }

        // Hop 2: `TextWithEntities` — every constructor must declare `text` unconditionally.
        $twe = $scheme->ensureType('TextWithEntities');
        self::assertNotSame([], $twe->constructors(), 'the TL declares no TextWithEntities constructor.');

        foreach ($twe->constructors() as $constructor) {
            $text = $this->paramNamed($constructor->allParams(), 'text');

            self::assertNotNull(
                $text,
                'TextWithEntities constructor '.$constructor->name.' no longer declares a `text` field. This '
                .'is the field the column actually stores; without it there is nothing to be NOT NULL about.',
            );

            self::assertNull(
                $text->conditional(),
                'TextWithEntities.'.$constructor->name.'.'.$text->name.' became CONDITIONAL ('
                .$text->raw.'). That is the ONLY thing that would make this column legitimately nullable.',
            );

            self::assertSame(
                'string',
                $text->baseType(),
                'TextWithEntities.text changed its base type to '.$text->baseType().'. A TL `string` is '
                .'always transmitted and has no default; another base type may have one, which would change '
                .'whether NOT NULL alone is correct.',
            );
        }
    }

    /**
     * @param  list<TlParam>  $params
     */
    private function paramNamed(array $params, string $name): ?TlParam
    {
        foreach ($params as $param) {
            if ($param->name === $name) {
                return $param;
            }
        }

        return null;
    }

    // ------------------------------------------------------------------
    // PROOF 2 — the executed dial declares it NOT NULL
    // ------------------------------------------------------------------

    public function test_the_shipped_dial_declares_the_column_not_null(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        self::assertTrue(
            $this->isNotNull($db, self::TABLE, self::COLUMN),
            self::TABLE.'.'.self::COLUMN.' is still NULLABLE on a booted shipped dial. This is rule C4 read '
            .'off the engine: the TL declares the field always present, so a nullable column admits a row '
            .'state Telegram has no encoding for.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 3 — the CONTROL: withhold the file and the column is nullable
    // ------------------------------------------------------------------

    public function test_withholding_the_migration_leaves_the_column_nullable(): void
    {
        $withheld = $this->migrations(withholdThisMigration: true);

        self::assertSame(
            1,
            count($this->migrations()) - count($withheld),
            'the control must withhold exactly one file, and it must be '.self::MIGRATION.'. If the filter '
            .'matched nothing, every assertion attributing NOT NULL to this migration would be unattributable.',
        );

        $db = $this->enforcingConnection(...$withheld);

        self::assertFalse(
            $this->isNotNull($db, self::TABLE, self::COLUMN),
            self::TABLE.'.'.self::COLUMN.' is NOT NULL even with '.self::MIGRATION.' withheld. Something else '
            .'in the dial declares it, so the requirement is being met by accident and this migration is '
            .'not what closes the finding.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 4 — reversible, idempotent, and it does NOT cost the foreign keys
    // ------------------------------------------------------------------

    public function test_the_migration_is_reversible_idempotent_and_keeps_both_foreign_keys(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        // The starting state, so every later assertion is relative to a measurement.
        self::assertTrue($this->isNotNull($db, self::TABLE, self::COLUMN), 'the dial did not start NOT NULL.');
        $before = $this->foreignKeyLegs($db, self::TABLE);
        sort($before);
        self::assertSame(
            $this->expectedForeignKeys(),
            $before,
            self::TABLE.'\'s outbound foreign keys are not the pair this file expects. Re-measure them: a '
            .'change() on SQLite rebuilds the table, and the set it loses is whatever this assertion stops '
            .'describing.',
        );

        $migration = $this->migration();

        // down() must restore exactly the spelling 2026_10_04_000500 shipped.
        $migration->down();
        self::assertFalse(
            $this->isNotNull($db, self::TABLE, self::COLUMN),
            'down() left '.self::TABLE.'.'.self::COLUMN.' NOT NULL, so a rollback does not undo this migration.',
        );
        self::assertSame(
            $before,
            $this->foreignKeyLegs($db, self::TABLE),
            'down() cost '.self::TABLE.' a foreign key. A table rebuild on SQLite drops constraints that were '
            .'not restated, and a NOT NULL column with no constraint on its tenant accepts rows for accounts '
            .'that do not exist.',
        );

        // up() again, and a second time, because an install that already ran this
        // file must not fail on a re-run.
        $migration->up();
        self::assertTrue($this->isNotNull($db, self::TABLE, self::COLUMN), 'up() did not restore NOT NULL.');

        $migration->up();
        self::assertTrue(
            $this->isNotNull($db, self::TABLE, self::COLUMN),
            'a second up() left the column in a different state. The migration must be idempotent.',
        );

        self::assertSame(
            $before,
            $this->foreignKeyLegs($db, self::TABLE),
            'after up() -> down() -> up() -> up() the foreign keys are not the pair this file measured at the '
            .'start. Something in the cycle is dropping a constraint that a later run does not restore.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 5 — the PRE-FLIGHT refuses, loudly, and changes nothing
    // ------------------------------------------------------------------

    public function test_the_preflight_refuses_over_a_null_row_and_changes_nothing(): void
    {
        // Build the dial WITHOUT this migration so a nullable column really is
        // there to be violated — the refusal must be proven against the state the
        // migration finds on an install that has not yet run it.
        $db = $this->enforcingConnection(...$this->migrations(withholdThisMigration: true));
        self::assertFalse($this->isNotNull($db, self::TABLE, self::COLUMN), 'the control dial is not nullable.');

        $db->table('telegram_accounts')->insert(['label' => 'probe', 'type' => 'user', 'user_id' => self::TENANT]);
        $db->table('tf_todo_lists')->insert(['account_id' => self::TENANT, 'todo_list_id' => 5]);

        $db->table(self::TABLE)->insert([
            'account_id' => self::TENANT,
            'todo_list_id' => 5,
            'title' => 'probe',
        ]);
        self::assertSame(
            1,
            $db->table(self::TABLE)->whereNull(self::COLUMN)->count(),
            'the fixture did not produce the NULL row the pre-flight exists to refuse.',
        );

        $thrown = null;
        try {
            $this->migration()->up();
        } catch (\RuntimeException $e) {
            $thrown = $e;
        }

        self::assertNotNull(
            $thrown,
            'a row carrying NULL in a column the TL declares always present was migrated over without a '
            .'word. Backfilling `\'\'` would assert the title was empty, which the wire does not say, and on '
            .'a non-strict server the ALTER does exactly that silently.',
        );

        self::assertStringContainsString(
            '1 row(s)',
            $thrown->getMessage(),
            'the refusal must NAME the count, so an operator can size the problem instead of guessing.',
        );

        // The refusal must leave the schema exactly as it found it.
        self::assertFalse(
            $this->isNotNull($db, self::TABLE, self::COLUMN),
            'the migration raised and STILL changed the column. The pre-flight is supposed to run before the '
            .'ALTER, so a refused run must leave nothing half-applied.',
        );
        self::assertSame(
            1,
            $db->table(self::TABLE)->whereNull(self::COLUMN)->count(),
            'the refused run deleted or rewrote the offending row. A pre-flight is a refusal, not a repair — '
            .'the operator decides what the row should have been.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 6 — a REAL row still lands, because NOT NULL here is not an outage
    // ------------------------------------------------------------------

    public function test_a_row_carrying_the_wires_value_lands(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $db->table('telegram_accounts')->insert(['label' => 'probe', 'type' => 'user', 'user_id' => self::TENANT]);
        $db->table('tf_todo_lists')->insert(['account_id' => self::TENANT, 'todo_list_id' => 6]);

        $db->table(self::TABLE)->insert([
            'account_id' => self::TENANT,
            'todo_list_id' => 6,
            'title' => 'probe',
            self::COLUMN => '',
        ]);

        self::assertSame(
            1,
            $db->table(self::TABLE)->where('todo_list_id', 6)->count(),
            'a legitimate row must LAND. NOT NULL forbids the NULL, not the empty string — and `text:""` is an '
            .'ordinary value Telegram sends for an empty title, so refusing it would be a refusal of real data.',
        );

        self::assertSame(
            '',
            (string) $db->table(self::TABLE)->where('todo_list_id', 6)->value(self::COLUMN),
            'the empty string did not round-trip. Reading it back as NULL would mean the column silently '
            .'converts a wire value into the state this migration exists to forbid.',
        );
    }
}
