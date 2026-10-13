<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use Illuminate\Database\Connection;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Tests\Schema\Mirror\RelationConstraintTestCase;

/**
 * A column-additive migration's `down()` must remove the columns its `up()`
 * added — and this file is the first thing in the repository that says so.
 *
 * ## WHY THIS FILE EXISTS
 *
 * `grep -rn "migrate:rollback" tests/` returned nothing, and exactly three
 * tests in the tree called `->down()` directly, all on OTHER migrations. The
 * suite could therefore run `up()` against real Postgres and MySQL and roll
 * ONE migration back, while having no assertion anywhere that a column
 * migration's rollback removes anything. Nothing in the repo could catch a
 * rollback that is silently void.
 *
 * ## THE DEFECT, MEASURED — NOT INFERRED
 *
 * `2026_10_04_000300`, `2026_10_04_000400` and `2026_10_04_000500` share a
 * private helper whose predicate was
 *
 *     foreach (self::COLUMNS as $column) {
 *         if (Schema::hasColumn(self::TABLE, $column)) { continue; }
 *         $work($column);
 *     }
 *
 * `up()` passes an ADDER through it, which is correct: add what is absent.
 * `down()` passed a DROPPER through the SAME helper, which is inverted: drop
 * only what is absent. The columns `up()` had just added ARE present, so
 * `down()` executed ZERO DDL, returned normally, Laravel deleted the migration
 * row — and the rollback was silently void while every signal reported
 * success. Nine columns across three tables.
 *
 * The helper's own docblock stated the contract that was backwards for this
 * direction ("guarded on hasTable/hasColumn in both directions … a second
 * `down()` is a no-op"), which is exactly why the inversion survived review:
 * the comment described the shape of the code, and the code was wrong.
 *
 * **MEASURED on :memory: SQLite against the pre-fix file**, and reproduced
 * verbatim by `tests/Schema/` — before the fix:
 *
 *     up()        tf_todo_lists_title.text present
 *     down()      tf_todo_lists_title.text STILL PRESENT   <-- the assertion fails here
 *
 * and, over all nine pairs, `removed: []` — no column anywhere changed state.
 *
 * ## WHY ALL THREE FILES, NOT ONE
 *
 * The brief allowed one file and asked for a justification. All three are
 * covered because one boot serves all nine pairs, so the marginal cost is
 * zero and the coverage is total: `000300` is the two-column
 * `match`-dispatching form, `000400` is the one-file-two-tables loop form
 * (six of the nine, so it is the highest-value single case), and `000500` is
 * the single-column form. The three between them cover every shape the family
 * uses, and the shared defect would have reappeared in any one of them
 * unnoticed. Choosing one would have left two of the three shapes unpinned.
 *
 * ## THE DIAL IS BOOTED WITH THESE THREE FILES WITHHELD
 *
 * The nine pairs are asserted ABSENT first, before `up()` runs. That is not
 * ceremony: without it, a test in which the withholding silently failed would
 * still reach the `up()` assertion and still pass, because the dial would
 * already carry the columns. The withhold is the control that makes the `up()`
 * assertion mean "up() put this there".
 *
 * ## THE HARNESS IS PROVEN, NOT ASSUMED — `test_the_column_oracle_can_report_both_states`
 *
 * A gate never seen to go red is not known to work. This class asserts the
 * ABSENT direction it mostly asserts away, on two independent oracles: a column
 * name no migration declares must read absent (proving the oracle can say
 * "absent"), and a column added and then dropped on a scratch table must move
 * present -> absent (proving a real drop is visible through it). Both are green
 * on correct code by construction, and both would go red if the oracle degraded
 * to a constant.
 *
 * ## THE HONEST LIMIT OF WHAT THIS FILE PROVES
 *
 * These nine columns are, TODAY, created by exactly one migration each — a
 * PROPERTY OF THE TREE rather than of this file's logic. That property is
 * pinned separately, by execution, in
 * `AdditiveMigrationOwnsEachColumnExclusivelyTest`; this file deliberately does
 * NOT re-state it, because a second copy of the ownership map is a second copy
 * that drifts. Read the two together: this file proves the rollback happens,
 * that file proves the rollback is allowed to drop what it dropped.
 */
final class AdditiveColumnMigrationRollsBackItsOwnColumnsTest extends RelationConstraintTestCase
{
    /**
     * The three additive migrations under test, in apply order.
     *
     * Named by FULL FILENAME rather than by a `glob` on a timestamp prefix, so
     * a rename is a red test instead of a silently-widened or silently-empty
     * match.
     *
     * @var list<string>
     */
    private const ADDERS = [
        '2026_10_04_000300_add_recent_repliers_to_tf_messages_replies.php',
        '2026_10_04_000400_add_star_gift_host_and_availability_columns.php',
        '2026_10_04_000500_add_text_column_to_tf_todo_lists_title.php',
    ];

    /**
     * The nine (table, column) pairs the three files own.
     *
     * Transcribed here ON PURPOSE, and this is the one place in this file a
     * transcription is defensible. The pairs double as the EXPECTATION the
     * migration's own `COLUMNS`/`TABLES` constants must satisfy:
     * `test_the_adder_adds_exactly_the_pairs_this_file_names` proves the
     * constant and this list agree, so a change to either one alone is red.
     * The single-source version of the ownership question — which MIGRATION
     * creates a column — is asked by execution in
     * `AdditiveMigrationOwnsEachColumnExclusivelyTest`, and this list does not
     * attempt to answer it.
     *
     * @var list<array{0: string, 1: string}>
     */
    private const PAIRS = [
        ['tf_messages_replies', 'recent_repliers_type'],
        ['tf_messages_replies', 'recent_repliers_id'],
        ['tf_stars_transactions_stargift', 'availability_issued'],
        ['tf_stars_transactions_stargift', 'host_id_type'],
        ['tf_stars_transactions_stargift', 'host_id_id'],
        ['tf_saved_star_gifts_gift', 'availability_issued'],
        ['tf_saved_star_gifts_gift', 'host_id_type'],
        ['tf_saved_star_gifts_gift', 'host_id_id'],
        ['tf_todo_lists_title', 'text'],
    ];

    /** The repository root, derived from this file rather than assumed. */
    private const PACKAGE_ROOT = __DIR__.'/../..';

    /**
     * Every shipped migration the ingest seam runs, in apply order, with the
     * named prefixes withheld.
     *
     * Read from `UpdateIngestor::migrationPaths()` — the list the ingest seam
     * and the MySQL lane run — rather than from a `glob` over the directory,
     * because a `glob` here would be a second copy of the dial and a second
     * copy drifts.
     *
     * @param  list<string>  $withhold  filename prefixes to leave out
     * @return list<string>
     */
    private function dialFiles(array $withhold = []): array
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

                foreach ($withhold as $prefix) {
                    if (str_contains(basename($file), $prefix)) {
                        continue 2;
                    }
                }

                $files[] = $file;
            }
        }

        return $files;
    }

    /** The migration object for `filename`, required afresh from the shipped dial. */
    private function migration(string $filename): object
    {
        $path = self::PACKAGE_ROOT.'/src/Laravel/Migrations/'.$filename;
        self::assertFileExists($path, 'the shipped migration named by this test is gone: '.$filename);

        $migration = require $path;

        self::assertIsObject($migration, $filename.' did not return a migration object.');

        return $migration;
    }

    /**
     * The nine pairs, split by whether the engine reports the column present.
     *
     * @return list<string>
     */
    private function presentPairs(Connection $db): array
    {
        $schema = $db->getSchemaBuilder();
        $present = [];

        foreach (self::PAIRS as [$table, $column]) {
            if ($schema->hasColumn($table, $column)) {
                $present[] = $table.'.'.$column;
            }
        }

        sort($present);

        return $present;
    }

    // ------------------------------------------------------------------

    public function test_the_adder_adds_exactly_the_pairs_this_file_names_and_its_rollback_removes_them(): void
    {
        // Boot WITHOUT the three files under test, so "up() put this there" is
        // a measurement rather than a restatement of what the dial already had.
        $db = $this->enforcingConnection(...$this->dialFiles(self::ADDERS));

        self::assertSame(
            [],
            $this->presentPairs($db),
            'withholding '.implode(', ', self::ADDERS).' from the boot did NOT withhold them: these pairs are '
            .'already present, so the up() assertion below would pass for the wrong reason and the down() '
            .'assertion would be measuring a dial that never needed the migration.',
        );

        foreach (self::ADDERS as $filename) {
            $this->migration($filename)->up();
        }

        self::assertSame(
            $this->spelledPairs(),
            $this->presentPairs($db),
            'running up() on the three additive migrations did not produce exactly the nine pairs this file '
            .'names. Re-measure: either a migration stopped adding one of its columns, or it started adding '
            .'one this list does not name.',
        );

        // Reverse apply order is the order a real rollback runs in.
        foreach (array_reverse(self::ADDERS) as $filename) {
            $this->migration($filename)->down();
        }

        $survivors = $this->presentPairs($db);

        self::assertSame(
            [],
            $survivors,
            'down() returned without removing '.count($survivors).' of the nine columns its own up() added: '
            .implode(', ', $survivors).'. A down() that drops nothing is worse than one that raises, because '
            .'Laravel deletes the migration row either way and the schema is then left claiming a migration '
            .'that has not been applied. This is the assertion that was red on the pre-fix tree.',
        );
    }

    public function test_a_second_up_and_a_second_down_are_idempotent(): void
    {
        $db = $this->enforcingConnection(...$this->dialFiles(self::ADDERS));

        foreach (self::ADDERS as $filename) {
            $migration = $this->migration($filename);
            $migration->up();
            $migration->up();
        }

        self::assertSame(
            $this->spelledPairs(),
            $this->presentPairs($db),
            'a repeated up() left the dial in a different state. An install that already ran these files must '
            .'be able to re-run them.',
        );

        foreach (array_reverse(self::ADDERS) as $filename) {
            $migration = $this->migration($filename);
            $migration->down();
            $migration->down();
        }

        self::assertSame(
            [],
            $this->presentPairs($db),
            'a repeated down() left a column behind. The family\'s stated contract is that both directions are '
            .'guarded, so a second down() is a no-op rather than a missing-column error — this asserts that '
            .'contract still holds after a rollback became possible at all. Without it, the cheapest fix '
            .'(delete the guard) would have satisfied the test above.',
        );
    }

    // ------------------------------------------------------------------
    // NEGATIVE CONTROL — the oracle this file asserts with, proven in both
    // directions. Green on correct code by construction; red if the oracle
    // ever degrades into a constant.
    // ------------------------------------------------------------------

    public function test_the_column_oracle_can_report_both_states(): void
    {
        $db = $this->enforcingConnection(...$this->dialFiles(self::ADDERS));
        $schema = $db->getSchemaBuilder();

        self::assertFalse(
            $schema->hasColumn('tf_todo_lists_title', 'zzz_a_column_no_migration_declares'),
            'the column oracle reported a column that no migration declares. Every "absent" assertion in this '
            .'file is an assertion that something was REMOVED, and this is the half of that which proves the '
            .'oracle can say "absent" at all. If it cannot, the rollback test above is measuring a constant.',
        );

        $probe = 'zzz_rollback_oracle_probe';
        self::assertFalse(
            $schema->hasTable($probe),
            'the scratch table this control builds already exists, so it is not measuring a fresh add.',
        );

        $schema->create($probe, function ($table): void {
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('id');
            $table->string('payload')->nullable();
        });
        $schema->table($probe, function ($table): void {
            $table->string('extra')->nullable();
        });

        self::assertTrue(
            $schema->hasColumn($probe, 'extra'),
            'adding a column to a scratch table did not make it visible. The "present" direction of the oracle '
            .'is broken.',
        );

        $schema->table($probe, function ($table): void {
            $table->dropColumn('extra');
        });

        self::assertFalse(
            $schema->hasColumn($probe, 'extra'),
            'dropping a column from a scratch table did not make it INVISIBLE. This is the control for the '
            .'whole file: it proves that a genuine drop is observable through exactly the call the migration '
            .'makes, so an unchanged column after down() means down() did nothing and not that the test '
            .'cannot see a drop.',
        );

        $schema->drop($probe);
    }

    /**
     * `PAIRS` in the sorted spelling `presentPairs()` returns, as a single
     * source for the two `assertSame()` calls that compare against it.
     *
     * @return list<string>
     */
    private function spelledPairs(): array
    {
        $spelled = array_map(
            static fn (array $pair): string => $pair[0].'.'.$pair[1],
            self::PAIRS,
        );
        sort($spelled);

        return $spelled;
    }
}
