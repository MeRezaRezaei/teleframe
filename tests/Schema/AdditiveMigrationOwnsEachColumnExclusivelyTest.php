<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\Facade;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Tests\Schema\Mirror\RelationConstraintTestCase;
use ReflectionClass;

/**
 * Every column an additive migration owns must enter the dial through THAT
 * migration and no other — pinned by EXECUTION, because it is a property of
 * the TREE rather than of any one file.
 *
 * ## WHY THIS IS THE DURABLE HALF
 *
 * `2026_10_04_000300`, `_000400` and `_000500` each own nine columns between
 * them, and once their `down()` is repaired it will `dropColumn()` each one
 * unconditionally. That is only safe while each column is created by exactly
 * ONE migration.
 *
 * It is not safe for long, because `src/Schema/schema/mirror-shape.json` is a
 * GENERATED artifact that already lists all nine as canonical columns of their
 * tables. The moment a regeneration folds them into a create migration — which
 * is precisely what a regeneration does with a canonical column — the create
 * migration creates the column, the additive migration's `up()` guard sees it
 * present and skips, and the additive migration's now-working `down()` drops a
 * column it never created, from a file another migration owns.
 *
 * So without this gate the repair is safe until the next regeneration and not
 * one hour after it, and nothing in the tree would notice. This file is that
 * notice.
 *
 * ## THE COLUMN LIST IS READ FROM THE MIGRATIONS, NEVER FROM `mirror-shape.json`
 *
 * Deliberately, and the reason is the whole trap: `mirror-shape.json` is the
 * generated file that would gain a second mention of these columns in the exact
 * scenario this gate exists to catch. Asserting the generated catalog is right
 * about itself is not a check. `ownedTables()` and `ownedColumns()` therefore
 * read `TABLE` / `TABLES` / `COLUMNS` off the migration objects themselves by
 * reflection, so the expectation moves when the migrations move and is
 * structurally incapable of agreeing with the generated artifact by accident.
 *
 * ## WHY THE MEASUREMENT IS EXECUTED, NOT A SOURCE SCAN
 *
 * A `grep` for the column name does not work here, and the reason is measured
 * rather than assumed:
 *
 *  * `text` — one of the nine — is declared in ELEVEN migration files, on ten
 *    different tables (`tf_users`, `tf_channels`, `tf_messages`,
 *    `tf_messages_media`, `tf_stars_transactions_stargift`, `tf_bots`,
 *    `tf_todo_items_title`, `tf_todo_lists_title`, …). A name-based grep says
 *    "11 creators" for a column that has exactly one, and the gate would be
 *    wrong on the very first row.
 *  * the other eight are declared through a variable — `match ($column) {
 *    'host_id_type' => $table->tinyInteger($column) }` — so a literal-argument
 *    grep finds ZERO of them and would report an exclusive count of 0 for all
 *    eight. The gate would be green and vacuous.
 *
 * Neither failure is visible in the output, which is the reason for measuring
 * by EXECUTION: boot the dial one file at a time, snapshot each owned table's
 * column listing after every file, and record which file is the one the column
 * first appears on. That is a property of the schema, so it cannot disagree
 * with the code that makes the schema, and it cannot miss a declaration
 * because the declaration was spelled through a variable.
 *
 * ## WHY `enforcingConnection()` IS NOT USED HERE
 *
 * `RelationConstraintTestCase` is extended only for the facade-application
 * restore its `tearDown()` performs: this test installs its own capsule the
 * same way `ShippedStoreSchema` does, so a leaked global facade application
 * would carry this suite's in-memory sqlite into whatever ran next. FK
 * enforcement is deliberately NOT switched on — nothing here inserts or
 * orphans a row, and turning the pragma on would only add a way for this file
 * to fail for a reason that has nothing to do with column ownership.
 *
 * ## THE HONEST LIMIT, STATED RATHER THAN HIDDEN
 *
 * "Exactly one creator" is measured as "the column enters the dial at exactly
 * one point in the apply order". That is the operationally complete form of
 * the invariant, and it is what makes the repair safe: a SECOND creator can
 * only appear BEFORE the additive migration (a create or earlier ALTER), and
 * this measurement would then attribute the column to that earlier file and
 * fail. A second declaration AFTER the owner is already caught by execution at
 * migrate time — an unguarded duplicate column is an error the dial raises —
 * and a guarded one changes nothing, so there is nothing for this gate to miss
 * there. What this file does NOT claim is a per-line provenance of the DDL; it
 * claims which file is responsible for the column existing, which is the
 * question the rollback depends on.
 *
 * ## THE CONTROLS, AND WHY A GREEN GATE HERE MEANS NOTHING WITHOUT THEM
 *
 * Two of the three tests exist to make the third non-vacuous:
 *
 *  * `test_each_adder_introduces_exactly_the_columns_it_owns` compares the
 *    MEASURED introduction set against the migration's own `TABLES × COLUMNS`
 *    constants. If the snapshot loop ever stopped recording anything, this
 *    compares `[]` against nine names and fails — so "nothing was recorded" can
 *    never be reported as "everything is exclusive".
 *  * `test_the_attribution_names_a_different_file_for_a_different_column`
 *    points the same measurement at `tf_messages_replies.id`, a column the
 *    create migration owns, and requires the attribution to name THAT file. A
 *    measurement that attributed everything to the three adders — the failure
 *    mode a buggy snapshot loop would have — is red here.
 */
final class AdditiveMigrationOwnsEachColumnExclusivelyTest extends RelationConstraintTestCase
{
    /**
     * The additive migrations whose columns must stay exclusively theirs.
     *
     * @var list<string>
     */
    private const ADDERS = [
        '2026_10_04_000300_add_recent_repliers_to_tf_messages_replies.php',
        '2026_10_04_000400_add_star_gift_host_and_availability_columns.php',
        '2026_10_04_000500_add_text_column_to_tf_todo_lists_title.php',
    ];

    /**
     * A column of one of those tables, owned by NOBODY in `ADDERS`.
     *
     * Exists only as the needle for the attribution control below.
     */
    private const FOREIGN_COLUMN_TABLE = 'tf_messages_replies';

    private const FOREIGN_COLUMN = 'id';

    private const FOREIGN_COLUMN_OWNER = '2026_09_14_200010_create_tf_messages_tables.php';

    /** The repository root, derived from this file rather than assumed. */
    private const PACKAGE_ROOT = __DIR__.'/../..';

    /** @var array<string, string>|null per-test cache of "table.column" => introducing file */
    private ?array $attribution = null;

    /** @var array<string, list<string>>|null per-test cache of file => columns it introduced */
    private ?array $introducedByFile = null;

    /** @var list<string> */
    private array $ownedTables = [];

    // ------------------------------------------------------------------

    /**
     * Which file's `up()` first put `TABLE.COLUMN` into the dial.
     *
     * MEASURED by booting the shipped dial one file at a time and snapshotting
     * each owned table's column listing after every file — see the class
     * docblock for why a source scan cannot answer this.
     *
     * @return array<string, string> "table.column" => file that introduced it
     */
    private function attribution(): array
    {
        if ($this->attribution !== null) {
            return $this->attribution;
        }

        $attribution = $this->introducedByFile();

        $this->attribution = [];

        foreach ($attribution as $file => $columns) {
            foreach ($columns as $column) {
                $this->attribution[$column] = $file;
            }
        }

        return $this->attribution;
    }

    /**
     * @return array<string, list<string>> file => the "table.column" pairs its up() introduced
     */
    private function introducedByFile(): array
    {
        if ($this->introducedByFile !== null) {
            return $this->introducedByFile;
        }

        $capsule = new Capsule;
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $app = new Container;
        $app->instance('db', $capsule->getDatabaseManager());
        $app->instance('db.schema', $capsule->getConnection()->getSchemaBuilder());
        Container::setInstance($app);
        // A bare Container is what the shipped harness
        // (tests/Store/ShippedStoreSchema::capsuleFor) installs; the facade only
        // ever reads db/db.schema off it.
        // @phpstan-ignore-next-line
        Facade::setFacadeApplication($app);
        Facade::clearResolvedInstances();

        $schema = $capsule->getConnection()->getSchemaBuilder();

        // Only the tables the adders own are snapshotted. Every table would
        // mean a full column listing per file per table; these four carry the
        // nine columns under test and the needle for the control.
        $watched = $this->ownedTables();
        $seen = array_fill_keys($watched, []);

        $introduced = [];

        foreach ($this->dialFiles() as $file) {
            $migration = require $file;

            if (! is_object($migration) || ! method_exists($migration, 'up')) {
                self::fail($file.' is in the dial but returned no migration object.');
            }

            $migration->up();

            foreach ($watched as $table) {
                if (! $schema->hasTable($table)) {
                    continue;
                }

                $now = $schema->getColumnListing($table);

                foreach (array_diff($now, $seen[$table]) as $column) {
                    $introduced[basename($file)][] = $table.'.'.$column;
                }

                $seen[$table] = $now;
            }
        }

        ksort($introduced);
        $this->introducedByFile = $introduced;

        return $introduced;
    }

    /**
     * Every table the three adders own, read off their `TABLE` / `TABLES`
     * constants — never transcribed, and never read from a generated artifact.
     *
     * @return list<string>
     */
    private function ownedTables(): array
    {
        if ($this->ownedTables !== []) {
            return $this->ownedTables;
        }

        $tables = [];

        foreach (self::ADDERS as $filename) {
            foreach ($this->tablesOwnedBy($filename) as $table) {
                $tables[] = $table;
            }
        }

        $tables = array_values(array_unique($tables));
        sort($tables);
        $this->ownedTables = $tables;

        return $tables;
    }

    /**
     * The columns one migration declares it owns, read off its `COLUMNS`.
     *
     * @return list<string>
     */
    private function ownedColumns(string $filename): array
    {
        $columns = $this->declared($filename, 'COLUMNS');
        sort($columns);

        return $columns;
    }

    /**
     * The tables one migration declares it touches — `TABLES` (a list) and
     * `TABLE` (a single name) both count, and a file is free to use either.
     *
     * Both are read OPTIONALLY: `2026_10_04_000400` declares `TABLES` and the
     * other two declare `TABLE`, and requiring both would make this file
     * assert a house style the migrations never adopted.
     *
     * @return list<string>
     */
    private function tablesOwnedBy(string $filename): array
    {
        $tables = array_merge(
            $this->declared($filename, 'TABLES', required: false),
            $this->declared($filename, 'TABLE', required: false),
        );

        $tables = array_values(array_unique($tables));
        sort($tables);

        return $tables;
    }

    /**
     * A migration's declared string constant as a flat list of strings.
     *
     * `TABLE` is one name and `TABLES` / `COLUMNS` are lists; both are read the
     * same way so the caller never has to know which shape a file uses.
     *
     * @return list<string>
     */
    private function declared(string $filename, string $constant, bool $required = true): array
    {
        $path = self::PACKAGE_ROOT.'/src/Laravel/Migrations/'.$filename;
        self::assertFileExists($path, 'the shipped migration named by this test is gone: '.$filename);

        $migration = require $path;
        self::assertIsObject($migration, $filename.' did not return a migration object.');

        $value = (new ReflectionClass($migration))->getConstant($constant);

        // `ReflectionClass::getConstant()` answers a name the class does not
        // declare with `false`, NOT null — measured, because reading `false` as
        // "the constant holds the value false" turns an absent constant into a
        // nonsense entry in the table list.
        if ($value === null || $value === false) {
            if (! $required) {
                return [];
            }

            self::fail(
                $filename.' declares no '.$constant.' constant, so this test cannot tell which columns the '
                .'file claims to own. Without the constant the exclusivity claim is unmeasurable — fix the '
                .'migration or this test, but do not guess the list here.',
            );
        }

        $names = is_array($value) ? array_values($value) : [$value];

        foreach ($names as $name) {
            self::assertIsString(
                $name,
                $filename.'\''.$constant.' holds a non-string entry, which this test cannot read as a table or '
                .'column name.',
            );
        }

        /** @var list<string> $names */
        return $names;
    }

    /**
     * The shipped dial, in apply order, read from the seam that owns the list.
     *
     * @return list<string>
     */
    private function dialFiles(): array
    {
        $files = [];
        $seen = [];

        foreach (UpdateIngestor::migrationPaths() as $path) {
            $inDir = is_dir($path) ? (glob($path.'/*.php') ?: []) : [$path];
            sort($inDir);

            foreach ($inDir as $file) {
                $file = (string) $file;
                $key = realpath($file) ?: $file;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $files[] = $file;
            }
        }

        return $files;
    }

    // ------------------------------------------------------------------
    // THE INVARIANT
    // ------------------------------------------------------------------

    public function test_each_owned_column_enters_the_dial_only_through_its_owning_migration(): void
    {
        $attribution = $this->attribution();

        $wrong = [];
        $absent = [];

        foreach (self::ADDERS as $filename) {
            foreach ($this->tablesOwnedBy($filename) as $table) {
                foreach ($this->ownedColumns($filename) as $column) {
                    $pair = $table.'.'.$column;
                    $owner = $attribution[$pair] ?? null;

                    if ($owner === null) {
                        $absent[] = $pair;

                        continue;
                    }

                    if ($owner !== $filename) {
                        $wrong[] = $pair.' is created by '.$owner;
                    }
                }
            }
        }

        self::assertSame(
            [],
            $absent,
            'these columns never appeared on the dial at all: '.implode(', ', $absent).'. A column that no '
            .'migration introduces is not an exclusive-creator problem, it is a missing column, and it means '
            .'the dial did not apply what this file reads.',
        );

        self::assertSame(
            [],
            $wrong,
            'a repaired down() would drop columns it does not own: '.implode(' | ', $wrong).'. These columns '
            .'are listed in an additive migration\'s COLUMNS, but some EARLIER migration already created '
            .'them, so that additive migration\'s up() guard skips and its down() dropColumn() removes a '
            .'column belonging to another file. This is what a regeneration folding the canonical columns '
            .'into a create migration would produce — and until it does, the drop is merely a lie.',
        );
    }

    // ------------------------------------------------------------------
    // CONTROL 1 — the measurement records something, and records the RIGHT
    // something. Without this, "no column was misattributed" would also be
    // what a measurement that recorded NOTHING would report.
    // ------------------------------------------------------------------

    public function test_each_adder_introduces_exactly_the_columns_it_owns(): void
    {
        $introduced = $this->introducedByFile();
        $problems = [];

        foreach (self::ADDERS as $filename) {
            $expected = [];

            foreach ($this->tablesOwnedBy($filename) as $table) {
                foreach ($this->ownedColumns($filename) as $column) {
                    $expected[] = $table.'.'.$column;
                }
            }

            sort($expected);

            $actual = $introduced[$filename] ?? [];
            sort($actual);

            if ($actual !== $expected) {
                $problems[] = $filename.' introduced ['.implode(', ', $actual).'] but owns ['
                    .implode(', ', $expected).']';
            }
        }

        self::assertSame(
            [],
            $problems,
            'an additive migration did not introduce exactly the columns it declares it owns. '
            .implode(' | ', $problems).'. This is the control for the exclusivity gate: if the measurement '
            .'recorded nothing at all, this would compare an empty set against nine declared names and fail '
            .'here rather than letting the exclusivity gate pass vacuously.',
        );
    }

    // ------------------------------------------------------------------
    // CONTROL 2 — the attribution DISCRIMINATES. A measurement that named
    // the three adders for everything would satisfy the exclusivity gate
    // perfectly; this requires it to name a fourth, different file.
    // ------------------------------------------------------------------

    public function test_the_attribution_names_a_different_file_for_a_different_column(): void
    {
        $pair = self::FOREIGN_COLUMN_TABLE.'.'.self::FOREIGN_COLUMN;

        self::assertNotSame(
            self::ADDERS,
            [self::FOREIGN_COLUMN_OWNER],
            'this test\'s needle is one of the adders, so it cannot discriminate between them. Pick a column '
            .'that a create migration owns.',
        );

        self::assertSame(
            self::FOREIGN_COLUMN_OWNER,
            $this->attribution()[$pair] ?? null,
            'the measurement did not attribute '.$pair.' to '.self::FOREIGN_COLUMN_OWNER.', which is the file '
            .'that creates it. The attribution map is therefore not discriminating: an implementation that '
            .'credited every column to the three additive migrations would satisfy '
            .'test_each_owned_column_enters_the_dial_only_through_its_owning_migration() while being wrong, '
            .'and this is what catches that.',
        );
    }
}
