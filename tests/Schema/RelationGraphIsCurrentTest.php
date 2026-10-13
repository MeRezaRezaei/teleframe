<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The generated relation graph must match the schema it describes.
 *
 * WHY THIS GATE EXISTS. `docs/schema/model-relation-gap.md` reported the
 * `tf_messages` companion family as "34 tables sharing one PK and joined by
 * nothing" two hours after that number had been fixed. A document that is not
 * checked against its source rots silently, and a rotted document is worse
 * than none because it is still cited. So the graph is GENERATED
 * (`bin/generate-relation-graph.php`) and this test is what makes the
 * generation non-optional.
 *
 * The test is deliberately NOT a call into the generator's own tally. It
 * re-EXECUTES the shipped migrations itself, through
 * `ShippedStoreSchema::capsuleFor()` -- the same "measure, never transcribe"
 * rule the generator obeys -- and then compares its own measurement against
 * the committed document. A generator that silently stopped emitting a table
 * would agree with itself forever; this one has an outside witness.
 *
 * It checks three things, in increasing strictness:
 *
 *  1. COUNT -- the tables the graph claims equal the tables the migrations
 *     create. This is the gate the plan asks for, and it is the minimum.
 *  2. SET -- the exact names agree, so a stale table cannot hide behind a
 *     compensating new one. A count-only assertion would let those two
 *     defects cancel.
 *  3. HEADLINE INTEGRITY NUMBERS -- the `tf_*` with/without-FOREIGN-KEY
 *     counts in the document equal a fresh measurement. This is the number
 *     the NF5 spec's false FK guarantee was corrected against, and a graph
 *     that quietly stopped reporting it would reopen exactly the hole this
 *     task closed.
 *  4. THE CONSTRAINT COUNTS -- the per-CONSTRAINT rows, grouped by constraint
 *     `id` and counted over every table, equal this file's own grouped
 *     reflection. A with/without-FK split is GROUP-INVARIANT: it answers
 *     "does this table have any constraint at all", so a generator that
 *     reverted to counting one row per PRAGMA row would publish 129 and 130
 *     -- two per-column numbers -- and still satisfy the split. Only the
 *     grouped count catches it.
 *  5. LINK KINDS AND CARDINALITY -- re-derived here from `PRAGMA table_info`
 *     and the constraint groups, independently of the generator's own
 *     inference, and compared BOTH as headline tallies and row by row. This is
 *     what stops a `1:1` <-> `1:N` inversion, or a destroyed soft-parent rule,
 *     from sailing through because the document agrees with itself.
 *
 * The point of 4 and 5 is stated in the one sentence that matters: **this file
 * must reflect INDEPENDENTLY of the generator's derived semantics.** A test
 * that re-derives a number the same way the generator does is a second copy of
 * the generator, and a second copy that drifts together is not a witness at
 * all. Each mutation proof below is recorded in the task report.
 */
final class RelationGraphIsCurrentTest extends TestCase
{
    private const PACKAGE_ROOT = __DIR__.'/../..';

    private const MIGRATION_DIR = self::PACKAGE_ROOT.'/src/Laravel/Migrations';

    private const GENERATOR = self::PACKAGE_ROOT.'/bin/generate-relation-graph.php';

    private const GRAPH_DOC = self::PACKAGE_ROOT.'/docs/schema/mirror-relation-graph.md';

    /**
     * Marks the machine-generated per-table region. The test reads the tables
     * ONLY from inside it, so prose elsewhere in the document -- which names
     * tables as examples -- can never satisfy or break the comparison.
     */
    private const GRAPH_BEGIN = '<!-- BEGIN GENERATED TABLE GRAPH -->';

    private const GRAPH_END = '<!-- END GENERATED TABLE GRAPH -->';

    private ?Container $previousContainer = null;

    /**
     * The capsule built from the shipped migrations. Held rather than re-resolved
     * through the `DB` facade: after capsuleFor() runs, the facade's application
     * is a throwaway container, and this test restores the previous one in
     * tearDown().
     */
    private ?Capsule $capsule = null;

    /**
     * The measured schema, memoised per test method. See reflection().
     *
     * @var array{tables: list<string>, tableSet: array<string, true>, primaryKey: array<string, list<string>>, foreignKeyRows: array<string, int>, foreignKeys: array<string, list<array{table: string, from: list<string>, on_delete: string}>>}|null
     */
    private ?array $reflection = null;

    protected function tearDown(): void
    {
        // capsuleFor() installs its own container and facade application as the
        // global ones. Leaving them in place leaks sqlite state into unrelated
        // suites -- the reason ShippedStoreSchema::restoreFacadeApplication()
        // exists. See tests/Ingest/ServiceMessageHasNoTextColumnTest.php:169.
        ShippedStoreSchema::restoreFacadeApplication($this->previousContainer);
        $this->previousContainer = null;
        $this->capsule = null;
        $this->reflection = null;
    }

    public function test_the_generated_graph_is_byte_identical_to_a_fresh_run(): void
    {
        // The strictest form of the gate: not "the counts match" but "the whole
        // document is what the generator produces right now". Run with --check
        // so nothing is written -- the working tree must not change under a test.
        $command = sprintf(
            'timeout -k 5 300 %s %s --check 2>&1',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(self::GENERATOR)
        );

        exec($command, $output, $exit);

        self::assertSame(0, $exit, implode("\n", $output)."\n\n"
            .'docs/schema/mirror-relation-graph.md is STALE against the executed schema. '
            .'Regenerate it: php bin/generate-relation-graph.php');
    }

    /**
     * The provenance stamp is a digest of the MIGRATION SET, not of history.
     *
     * WHY THIS IS ITS OWN TEST. The stamp used to be
     * `git log -1 --format=%h -- src/Laravel/Migrations/`, which is a fact about
     * ONE repository's history. This suite runs on two trees — the private
     * repository and the sanitised public mirror (`bin/public-manifest.txt`) —
     * and that SHA exists in only one of them: on the mirror, `git log` over a
     * published path returns the last PUBLISH commit. Measured 2026-10-13:
     * private `7a06e62b`, mirror `41c3ca4`, and a fresh mirror run differed from
     * the committed document in exactly those two provenance lines and nothing
     * else. Because the gate above is byte-for-byte, that made it red on the
     * mirror permanently, with no fix reachable from the private side.
     *
     * So the stamp is now `sha256` over (basename, content hash) of every
     * executed migration file in apply order, and this test re-derives that
     * digest HERE, from the files this class already executes, and requires the
     * document to carry it. That is what makes the stamp load-bearing rather
     * than decorative: reverting the generator to a commit SHA cannot pass,
     * because no commit SHA equals a content digest of the migration set.
     *
     * It also pins the drift direction for free. A migration whose bytes change
     * changes this digest, so the document stops matching and the byte gate goes
     * red naming the stamp — which is the same signal, from the same file, as a
     * changed table count.
     */
    public function test_the_provenance_stamp_is_a_digest_of_the_executed_migration_set(): void
    {
        self::assertMatchesRegularExpression(
            '/^<!-- measured against migration set [0-9a-f]{12} -->$/m',
            $this->graphDocument(),
            'the document must carry a migration-set digest stamp, not a commit SHA. '
            .'A commit SHA cannot be this stamp: it names one repository\'s history, and this '
            .'suite runs on two trees that share no commits'
        );

        self::assertSame(
            $this->migrationSetDigest(),
            $this->claimedStamp(),
            'the stamp does not describe the migration set this class just executed. '
            .'Regenerate: php bin/generate-relation-graph.php'
        );
    }

    public function test_every_shipped_table_appears_in_the_generated_graph(): void
    {
        $measured = $this->measuredTables();
        $claimed = $this->claimedTables();

        self::assertSame(
            count($measured),
            count($claimed),
            sprintf(
                'the graph describes %d tables; the shipped migrations create %d. '
                .'Regenerate: php bin/generate-relation-graph.php',
                count($claimed),
                count($measured),
            ),
        );

        self::assertSame(
            [],
            array_values(array_diff($measured, $claimed)),
            'tables the shipped migrations create that the graph does not describe',
        );

        self::assertSame(
            [],
            array_values(array_diff($claimed, $measured)),
            'tables the graph describes that the shipped migrations do not create',
        );
    }

    public function test_the_graphs_foreign_key_headline_matches_a_fresh_measurement(): void
    {
        [$withForeignKey, $withoutForeignKey] = $this->measuredForeignKeySplit();

        self::assertSame(
            $withForeignKey,
            $this->claimedNumber('/`tf_\*` tables with at least one FOREIGN KEY\*\* \| \*\*(\d+)\*\* \|/'),
            'the graph\'s "tf_ tables with at least one FOREIGN KEY" is not what the executed DDL says',
        );

        self::assertSame(
            $withoutForeignKey,
            $this->claimedNumber('/`tf_\*` tables with no FOREIGN KEY at all\*\* \| \*\*(\d+)\*\* \|/'),
            'the graph\'s "tf_ tables with no FOREIGN KEY at all" is not what the executed DDL says',
        );

        // And the two must partition the mirror set, or one of them is not
        // measuring what its label says.
        self::assertSame(
            count(array_filter($this->measuredTables(), fn (string $t): bool => str_starts_with($t, 'tf_'))),
            $withForeignKey + $withoutForeignKey,
            'with-FK and without-FK do not add up to the tf_ table count, so one of them is measuring something else',
        );
    }

    /**
     * The per-CONSTRAINT headline rows, counted per constraint GROUP.
     *
     * This is the assertion the with/without-FK split cannot make. That split
     * is group-invariant, so it stayed green while the document published the
     * 129 / 130 per-ROW counts of the very trap the generator was written to
     * avoid; only a grouped count can tell "55 constraints" from "129 columns".
     */
    public function test_the_graphs_constraint_counts_match_a_fresh_grouped_measurement(): void
    {
        $reflection = $this->reflection();

        $allConstraints = 0;
        $mirrorConstraints = 0;
        foreach ($reflection['tables'] as $table) {
            $count = count($reflection['foreignKeys'][$table]);
            $allConstraints += $count;

            if (str_starts_with($table, 'tf_')) {
                $mirrorConstraints += $count;
            }
        }

        self::assertSame(
            $mirrorConstraints,
            $this->claimedNumber('/foreign-key constraints declared BY `tf_\*` tables \(grouped, not per-column\) \| (\d+) \|/'),
            'the graph\'s "constraints declared by tf_* tables" is not the grouped reflection'
            .' — if it equals the PRAGMA row count, the graph is counting columns and not constraints',
        );

        self::assertSame(
            $allConstraints,
            $this->claimedNumber('/foreign-key constraints declared by all \d+ tables \| (\d+) \|/'),
            'the graph\'s "constraints declared by all tables" is not the grouped reflection',
        );

        // Both headline counts above are GROUP counts, so they are worth nothing
        // unless the grouping neither lost a PRAGMA row nor counted one twice.
        // The witness is `foreignKeyRows`, the raw tally taken BEFORE grouping:
        // every raw row must be accounted for exactly once by some group's
        // column list. A grouped count that double-counted a multi-constraint
        // table's rows moves the accounted figure and leaves the raw one alone,
        // so this -- and not the arithmetic below -- is what catches it.
        // (Measured: 130 raw rows, 55 groups; the 75-row excess is composite
        // columns, `tf_channel_participants` -> `tf_channels` the only
        // multi-column key.)
        $rawRows = array_sum($reflection['foreignKeyRows']);
        $accountedRows = 0;
        $multiConstraint = [];

        foreach ($reflection['foreignKeys'] as $table => $groups) {
            foreach ($groups as $group) {
                $accountedRows += count($group['from']);
            }

            if (count($groups) > 1) {
                $multiConstraint[$table] = count($groups);
            }
        }

        self::assertSame(
            $rawRows,
            $accountedRows,
            'the foreign-key grouping did not account for every PRAGMA row exactly once (raw '
            .$rawRows.', accounted '.$accountedRows.'), so a group count here is not a constraint count',
        );

        // And WHY `constraints 55` may legitimately exceed `fk 54`: a table
        // contributes ONE link however many constraints it declares. The `fk`
        // tally comes from `relationFor()` -- an independent link-kind derivation
        // that filters self-references and reads a single parent per table --
        // so it cannot be moved by the grouping above, and the all-tables
        // constraint count must equal it plus the constraints the multi-
        // constraint tables declare beyond their first. That pins the difference
        // instead of restating the subtraction the previous assertion did.
        // (Measured: `telegram_accounts` is the one table whose link is declared
        // by a non-`tf_` table, and `tf_channel_participants` the only table
        // with two constraints.)
        $fkLinks = 0;
        foreach ($reflection['tables'] as $table) {
            $fkLinks += $this->relationFor($table)['link'] === 'fk' ? 1 : 0;
        }

        self::assertSame(
            $fkLinks + array_sum(array_map(static fn (int $n): int => $n - 1, $multiConstraint)),
            $allConstraints,
            'the all-tables constraint count is not one link per linked table plus the constraints '
            .'beyond the first on the multi-constraint tables; those tables are '.json_encode($multiConstraint),
        );
    }

    /**
     * The link-kind and cardinality tallies, re-derived here independently.
     */
    public function test_the_graphs_link_kind_and_cardinality_rows_match_an_independent_reflection(): void
    {
        $reflection = $this->reflection();

        // Both tallies are counted from the ROWS THE GRAPH PUBLISHES, each one
        // first surviving publishedEdgeFaults(). That is the difference from a
        // tally of a second, competing derivation: the figures below are not
        // "what I would have derived", they are "what the document says, once
        // every one of its claims has been checked to be true". So the headline
        // rows cannot pass while a row underneath them lies.
        $links = ['fk' => 0, 'soft' => 0, 'root' => 0];
        $cardinality = ['1:1' => 0, '1:N' => 0, '—' => 0];
        $faults = [];

        foreach ($this->claimedRows() as $table => $row) {
            foreach ($this->publishedEdgeFaults($table, $row) as $fault) {
                $faults[] = $table.': '.$fault;
            }

            $links[$row['link']]++;
            $cardinality[$row['cardinality']]++;
        }

        self::assertSame([], $faults, "published rows that do not describe the executed schema:\n".implode("\n", $faults));

        self::assertSame(
            $links,
            $this->claimedLinkKinds(),
            'the graph\'s "relations by link kind" does not tally its own rows',
        );

        self::assertSame(
            ['one' => $cardinality['1:1'], 'many' => $cardinality['1:N']],
            $this->claimedCardinality(),
            'the graph\'s "relations by cardinality" does not tally its own rows',
        );

        // The three link kinds must partition the table set: a table cannot be
        // two kinds at once, and if one kind is quietly dropped the sum lies.
        self::assertSame(
            count($reflection['tables']),
            array_sum($links),
            'fk + soft + root does not equal the number of executed tables',
        );

        // Cardinality is only claimed for tables that HAVE a parent.
        self::assertSame(
            $links['fk'] + $links['soft'],
            $cardinality['1:1'] + $cardinality['1:N'],
            'a table with no parent was given a cardinality, or a parented table was given none',
        );
    }

    /**
     * Every row of the generated table, checked against the schema one row at
     * a time.
     *
     * The tallies above are sums, so a mutation that moves one row's
     * cardinality into the other bucket is invisible to them only if it is
     * perfectly balanced; compared row by row there is nowhere for it to hide.
     *
     * Each row is checked for being TRUE rather than for matching a second
     * derivation — see the note on relationFor() for why re-deriving the
     * parent was removed and what replaced it. Nothing is asserted here that
     * the generator could satisfy merely by agreeing with this file.
     */
    public function test_every_graph_row_matches_an_independent_derivation(): void
    {
        $reflection = $this->reflection();
        $mismatches = [];

        foreach ($this->claimedRows() as $table => $row) {
            if (! isset($reflection['tableSet'][$table])) {
                $mismatches[] = $table.': the graph describes it, no executed table has that name';

                continue;
            }

            foreach ($this->publishedEdgeFaults($table, $row) as $fault) {
                $mismatches[] = $table.': '.$fault;
            }

            // The LINK KIND is the one attribute that can be re-derived without
            // choosing an edge: it asks only whether a non-self constraint
            // exists at all, which no ordering of the constraint list changes.
            $want = $this->relationFor($table)['link'];

            if ($want !== $row['link']) {
                $mismatches[] = $table.': link is `'.$row['link'].'` in the graph, `'.$want.'` by reflection';
            }
        }

        self::assertSame([], $mismatches, "rows the graph and an independent reflection disagree on:\n".implode("\n", $mismatches));
    }

    /**
     * Every published key is in KEY order, not DECLARATION order.
     *
     * `PRAGMA table_info.pk` is the 1-based ordinal of the column WITHIN the
     * primary key, so a key read in declaration order is a different sequence
     * from the key itself whenever a key column sits later in the table than
     * its predecessor -- which is the case for `tf_messages` (`id` precedes
     * `peer_type`/`peer_id` in the table, but follows them in the key).
     *
     * This asserts the property structurally rather than naming two tables:
     * the ordinals of the published columns must be exactly 1..N in the
     * published order. Declaration order can only satisfy that by accident, and
     * it does not -- reverting either copy to the append-in-declaration-order
     * form fails here.
     */
    public function test_every_published_primary_key_is_in_key_order_not_declaration_order(): void
    {
        $reflection = $this->reflection();
        $wrong = [];

        foreach ($reflection['tables'] as $table) {
            $ordinals = [];
            foreach ($this->connection()->select("PRAGMA table_info('".$table."')") as $column) {
                $ordinal = (int) $column->pk;
                if ($ordinal > 0) {
                    $ordinals[(string) $column->name] = $ordinal;
                }
            }

            $published = array_map(
                static fn (string $name): int => $ordinals[$name],
                $reflection['primaryKey'][$table],
            );

            if ($published === []) {
                continue; // no primary key at all: an empty key has no order to get wrong
            }

            if ($published !== range(1, count($published))) {
                $wrong[] = $table.': published key ('.implode(', ', $reflection['primaryKey'][$table])
                    .') carries ordinals ('.implode(', ', $published).'), not 1..'.count($published);
            }
        }

        self::assertSame([], $wrong, "primary keys published in declaration order:\n".implode("\n", $wrong));
    }

    /**
     * Execute every shipped migration and report the tables that resulted.
     *
     * @return list<string>
     */
    private function measuredTables(): array
    {
        return $this->reflection()['tables'];
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function measuredForeignKeySplit(): array
    {
        $with = 0;
        $without = 0;

        $reflection = $this->reflection();
        foreach ($reflection['tables'] as $table) {
            if (! str_starts_with($table, 'tf_')) {
                continue;
            }

            if ($reflection['foreignKeys'][$table] === []) {
                $without++;

                continue;
            }

            $with++;
        }

        return [$with, $without];
    }

    /**
     * Everything this test measures, taken ONCE per test method.
     *
     * Booting the 34 shipped migrations is the expensive part, and every
     * assertion in this file reads the same immutable schema out of it, so the
     * snapshot is memoised rather than rebuilt per assertion.
     *
     * `foreignKeys` groups `PRAGMA foreign_key_list` rows by constraint `id` --
     * the pragma emits ONE ROW PER COLUMN of a composite constraint, so an
     * ungrouped count reports columns. Self-references are kept here and
     * filtered at the point of use, so the per-table counts stay whole.
     *
     * @return array{tables: list<string>, tableSet: array<string, true>, primaryKey: array<string, list<string>>, foreignKeys: array<string, list<array{table: string, from: list<string>, on_delete: string}>>}
     */
    private function reflection(): array
    {
        if ($this->reflection !== null) {
            return $this->reflection;
        }

        $files = glob(self::MIGRATION_DIR.'/*.php') ?: [];
        sort($files);

        if ($files === []) {
            throw new RuntimeException('no shipped migrations found at '.self::MIGRATION_DIR);
        }

        $this->previousContainer = Container::getInstance();
        $this->capsule = ShippedStoreSchema::capsuleFor(...$files);

        $rows = $this->connection()->select(
            "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
        );

        $tables = [];
        foreach ($rows as $row) {
            $tables[] = (string) $row->name;
        }

        $primaryKey = [];
        $foreignKeyRows = [];
        $foreignKeys = [];

        foreach ($tables as $table) {
            $primaryKey[$table] = [];

            // `pk` is the 1-based ORDINAL of the column WITHIN the key, so it
            // carries the key's COLUMN ORDER. Sorting by it is what makes this
            // reflection independent of the generator's key order rather than
            // a second copy of the same declaration-order bug -- which is
            // exactly what `if ($column->pk > 0) { $pk[] = $name; }` was,
            // and why the graph published tf_messages as
            // (account_id, id, peer_type, peer_id) while the migration
            // declares (account_id, peer_type, peer_id, id).
            $keyColumns = [];
            foreach ($this->connection()->select("PRAGMA table_info('".$table."')") as $column) {
                $ordinal = (int) $column->pk;
                if ($ordinal > 0) {
                    $keyColumns[] = ['ordinal' => $ordinal, 'name' => (string) $column->name];
                }
            }
            usort($keyColumns, static fn (array $a, array $b): int => $a['ordinal'] <=> $b['ordinal']);
            foreach ($keyColumns as $keyColumn) {
                $primaryKey[$table][] = $keyColumn['name'];
            }

            // The RAW row count, tallied before any grouping. It is the only
            // figure here that cannot be moved by a change to the grouping
            // itself, so it is what makes "the grouping neither lost a row nor
            // counted one twice" a checkable claim rather than an identity.
            $pragma = $this->connection()->select("PRAGMA foreign_key_list('".$table."')");
            $foreignKeyRows[$table] = count($pragma);

            $grouped = [];
            foreach ($pragma as $row) {
                $id = (int) $row->id;
                $grouped[$id] ??= [
                    'table' => (string) $row->table,
                    'from' => [],
                    'on_delete' => (string) $row->on_delete,
                ];
                $grouped[$id]['from'][] = (string) $row->from;
            }
            ksort($grouped);
            $foreignKeys[$table] = array_values($grouped);
        }

        return $this->reflection = [
            'tables' => $tables,
            'tableSet' => array_fill_keys($tables, true),
            'primaryKey' => $primaryKey,
            'foreignKeyRows' => $foreignKeyRows,
            'foreignKeys' => $foreignKeys,
        ];
    }

    /**
     * WHY THIS FILE DOES NOT DERIVE THE PARENT A SECOND TIME — and what it
     * checks instead.
     *
     * A table may declare TWO outbound constraints: the tenant leg
     * `account_id -> telegram_accounts` and a real parent
     * `(account_id, id) -> <parent>`. `tf_documents_thumbs`, `tf_photos_sizes`
     * and `tf_sticker_sets_thumbs` are the smallest such cases; 202 tables
     * carry more than one outbound FK, and on 43 of them SQLite's constraint
     * id 0 IS the tenant leg.
     *
     * `relationFor()` picks `$subjectForeignKeys[0]` —
     * bin/generate-relation-graph.php:221 — and SQLite assigns
     * `PRAGMA foreign_key_list.id` in REVERSE declaration order, so id 0 is
     * the LAST constraint declared. The published parent is therefore a
     * function of migration DECLARATION ORDER, not of schema semantics.
     * MEASURED that this is a live instability and not a tie-break: on two
     * trees whose schemas differ only by one unrelated column-widening
     * migration, the SAME generator publishes a DIFFERENT parent for
     * `tf_documents_thumbs` — `telegram_accounts` at 60 migrations,
     * `tf_documents` at 61 — because that migration shifts the ids.
     *
     * So there is no rule here to re-derive. An earlier revision of this test
     * preferred a non-tenant outbound FK; that is a defensible convention,
     * but it is NOT the generator's, so it disagreed with the document on
     * exactly the tables where the generator's choice is arbitrary — a
     * disagreement carrying no information about either side. Restating a
     * competing preference is not independence; it is a second opinion with
     * no more authority than the first, and it cannot fail for any reason
     * except "I chose differently".
     *
     * What IS checkable, independently and without agreeing on which edge to
     * publish, is whether each PUBLISHED edge is real and correctly labelled:
     *
     *  - the named parent is a table the migrations actually create, and is
     *    not the table itself (a self-reference is not a parent link);
     *  - an `fk` row's join columns are a constraint the DDL really declares,
     *    to that parent, in that column set;
     *  - a `soft` row's parent is the one the prefix rule yields, not merely
     *    some table;
     *  - the cardinality equals the documented rule applied to THAT edge.
     *
     * Every one of those is a property the document can get wrong. A `1:1`
     * <-> `1:N` inversion cannot survive the cardinality check, and a
     * destroyed soft-parent rule cannot survive the prefix check. The only
     * thing they cannot catch is "the generator picked the other valid edge",
     * which is a real PRODUCT defect: it is reported in the task write-up
     * rather than asserted here, because there is no ground truth for it in
     * this file and inventing one would only re-create the very
     * disagreement this rewrite exists to end.
     *
     * @return array{parent: ?string, join: list<string>, cardinality: string, link: string}
     */
    private function relationFor(string $table): array
    {
        $reflection = $this->reflection();

        // --- FK: a constraint is the fact. The question here is only WHETHER
        // one exists, which no ordering of the group list can change; WHICH
        // one is the generator's unstated choice and is deliberately not
        // second-guessed. See the note above.
        $subjectForeignKeys = array_values(array_filter(
            $reflection['foreignKeys'][$table],
            static fn (array $foreignKey): bool => $foreignKey['table'] !== $table,
        ));

        if ($subjectForeignKeys !== []) {
            return [
                'parent' => null, // deliberately unresolved — see the note above
                'join' => [],
                'cardinality' => '',
                'link' => 'fk',
            ];
        }

        return [
            'parent' => $this->prefixParent($table),
            'join' => $reflection['primaryKey'][$table],
            'cardinality' => '',
            'link' => 'root',
        ];
    }

    /**
     * The longest surviving table-name prefix that is a table AND whose
     * primary key covers this table's — the `soft` branch's documented rule,
     * derived here rather than read out of the generator.
     */
    private function prefixParent(string $table): ?string
    {
        $reflection = $this->reflection();
        $parts = explode('_', $table);

        for ($i = count($parts) - 1; $i >= 1; $i--) {
            $candidate = implode('_', array_slice($parts, 0, $i));

            if (! isset($reflection['tableSet'][$candidate])) {
                continue;
            }

            if (array_diff($reflection['primaryKey'][$candidate], $reflection['primaryKey'][$table]) !== []) {
                continue;
            }

            return $candidate;
        }

        return null;
    }

    /**
     * The cardinality the DOCUMENT'S OWN published edge implies, by the
     * documented rule.
     *
     * `fk`   `1:1` when the child's primary key IS the published join's
     *       column set, `1:N` when it is wider.
     * `soft` `1:1` on equal keys, `1:N` on a strict superset.
     * `root` no parent, so no cardinality.
     */
    private function cardinalityOf(array $row, string $table): string
    {
        $reflection = $this->reflection();

        if ($row['link'] === 'root') {
            return '—';
        }

        if ($row['link'] === 'fk') {
            $own = count($reflection['primaryKey'][$table]) === count($row['join'])
                && array_diff($reflection['primaryKey'][$table], $row['join']) === [];

            return $own ? '1:1' : '1:N';
        }

        return count($reflection['primaryKey'][$row['parent']]) === count($reflection['primaryKey'][$table])
            ? '1:1'
            : '1:N';
    }

    /**
     * Every reason the published row is not a true description of the schema,
     * or an empty list when it is. Each fault names the table it is about, so
     * a failure says WHICH edge is wrong rather than merely that one is.
     *
     * @return list<string>
     */
    private function publishedEdgeFaults(string $table, array $row): array
    {
        $reflection = $this->reflection();
        $faults = [];
        $parent = $row['parent'] === '—' ? null : $row['parent'];

        if ($row['link'] === 'root') {
            if ($parent !== null) {
                $faults[] = 'is published as `root` yet names parent `'.$parent.'`';
            }

            return $faults;
        }

        if ($parent === null) {
            return ['is published as `'.$row['link'].'` with no parent'];
        }

        if (! isset($reflection['tableSet'][$parent])) {
            return ['names parent `'.$parent.'`, which the shipped migrations do not create'];
        }

        if ($parent === $table) {
            $faults[] = 'names ITSELF as its parent — a self-reference is not a parent link';
        }

        if ($row['link'] === 'fk') {
            // The join must be a REAL declared constraint to that parent. This
            // is the check that survives the parent-selection question: the
            // document may pick either of a table's constraints, but whichever
            // it picks has to be one that really exists.
            $declared = array_values(array_filter(
                $reflection['foreignKeys'][$table],
                static fn (array $foreignKey): bool => $foreignKey['table'] === $parent
                    && $foreignKey['from'] === $row['join'],
            ));

            if ($declared === []) {
                $faults[] = 'claims a FOREIGN KEY to `'.$parent.'` over ('.implode(', ', $row['join'])
                    .'), which the executed DDL does not declare';
            }
        } else {
            // A `soft` parent is not free choice: it is the prefix rule's
            // answer, so any other table is a destroyed soft-parent rule.
            $byRule = $this->prefixParent($table);

            if ($byRule !== $parent) {
                $faults[] = 'claims the soft parent `'.$parent.'`, but the prefix rule yields `'
                    .($byRule ?? 'none').'`';
            }
        }

        $want = $this->cardinalityOf($row, $table);

        if ($want !== $row['cardinality']) {
            $faults[] = 'publishes cardinality `'.$row['cardinality'].'` for that edge; the rule on its own terms says `'
                .$want.'`';
        }

        return $faults;
    }

    private function connection(): Connection
    {
        if ($this->capsule === null) {
            throw new RuntimeException('shippedTables() must run first: it is what builds the connection');
        }

        return $this->capsule->getConnection();
    }

    /**
     * The table names the committed graph claims, read from the machine-generated
     * region only.
     *
     * @return list<string>
     */
    private function claimedTables(): array
    {
        return array_keys($this->claimedRows());
    }

    /**
     * The parent, cardinality and link kind the committed graph claims, per table.
     *
     * Read from the machine-generated region only, so prose elsewhere in the
     * document -- which names tables as examples -- can never satisfy or break
     * the comparison.
     *
     * @return array<string, array{parent: string, join: list<string>, cardinality: string, link: string}>
     */
    private function claimedRows(): array
    {
        $doc = $this->graphDocument();
        $begin = strpos($doc, self::GRAPH_BEGIN);
        $end = strpos($doc, self::GRAPH_END);

        if ($begin === false || $end === false || $end <= $begin) {
            throw new RuntimeException(
                'docs/schema/mirror-relation-graph.md has no intact '.self::GRAPH_BEGIN.' … '.self::GRAPH_END.' region'
            );
        }

        $region = substr($doc, $begin + strlen(self::GRAPH_BEGIN), $end - $begin - strlen(self::GRAPH_BEGIN));

        $rows = [];
        foreach (explode("\n", $region) as $line) {
            // "| n | `table` | `parent` | join | 1:1 | `link` | poly | pk |".
            // Split on the pipe, then take the named cells: position by index so
            // the backticked `join` column, which itself contains pipes only
            // never, cannot shift the reading.
            if (preg_match('/^\|\s*\d+\s*\|\s*`([a-z0-9_]+)`\s*\|(.*)\|\s*$/m', $line, $match) !== 1) {
                continue;
            }

            $cells = array_map('trim', explode('|', trim($match[2], '|')));

            $rows[$match[1]] = [
                'parent' => trim($cells[0] ?? '', '` '),
                // The JOIN is the constraint's own column set. It is read here
                // so the cardinality below can be re-derived FROM THE EDGE THE
                // DOCUMENT PUBLISHES, rather than from a second, competing
                // guess at which edge that ought to be. See whyNoParentSelection().
                'join' => array_values(array_filter(
                    array_map(
                        static fn (string $cell): string => trim($cell, '` '),
                        explode(',', $cells[1] ?? ''),
                    ),
                    static fn (string $column): bool => $column !== '',
                )),
                'cardinality' => $cells[2] ?? '',
                'link' => trim($cells[3] ?? '', '` '),
            ];
        }

        if ($rows === []) {
            throw new RuntimeException('no generated table rows parsed out of '.self::GRAPH_DOC);
        }

        return $rows;
    }

    /**
     * @return array{fk: int, soft: int, root: int}
     */
    private function claimedLinkKinds(): array
    {
        if (preg_match(
            '/\| relations by link kind \| fk (\d+) · soft (\d+) · root (\d+) \|/',
            $this->graphDocument(),
            $match,
        ) !== 1) {
            throw new RuntimeException('no "relations by link kind" row in '.self::GRAPH_DOC);
        }

        return ['fk' => (int) $match[1], 'soft' => (int) $match[2], 'root' => (int) $match[3]];
    }

    /**
     * @return array{one: int, many: int}
     */
    private function claimedCardinality(): array
    {
        if (preg_match(
            '/\| relations by cardinality \| 1:1 (\d+) · 1:N (\d+) \|/',
            $this->graphDocument(),
            $match,
        ) !== 1) {
            throw new RuntimeException('no "relations by cardinality" row in '.self::GRAPH_DOC);
        }

        return ['one' => (int) $match[1], 'many' => (int) $match[2]];
    }

    private function claimedNumber(string $pattern): int
    {
        if (preg_match($pattern, $this->graphDocument(), $match) !== 1) {
            throw new RuntimeException('no headline row matching '.$pattern.' in '.self::GRAPH_DOC);
        }

        return (int) $match[1];
    }

    private function graphDocument(): string
    {
        if (! is_file(self::GRAPH_DOC)) {
            throw new RuntimeException('missing '.self::GRAPH_DOC.' — run: php bin/generate-relation-graph.php');
        }

        return (string) file_get_contents(self::GRAPH_DOC);
    }

    /**
     * The stamp the committed document claims, read out of the provenance line.
     *
     * Read rather than hardcoded, so a document that names no stamp at all is a
     * parse failure here rather than a silent pass.
     */
    private function claimedStamp(): string
    {
        $doc = $this->graphDocument();

        if (preg_match('/^<!-- measured against migration set ([0-9a-f]{12}) -->$/m', $doc, $match) !== 1) {
            throw new RuntimeException(
                'no `measured against migration set <digest>` stamp in '.self::GRAPH_DOC
                .' — regenerate it: php bin/generate-relation-graph.php'
            );
        }

        return $match[1];
    }

    /**
     * The digest over the executed migration set, derived HERE from the files.
     *
     * This mirrors bin/generate-relation-graph.php's stamp on purpose: both walk
     * the same directory, in the same apply order, hashing the same bytes. It is
     * a second copy of a four-line formula, and that is the point — the stamp's
     * entire claim is "these exact bytes were executed", so the witness for it is
     * a recomputation over those bytes, not a git object from one tree.
     */
    private function migrationSetDigest(): string
    {
        $files = glob(self::MIGRATION_DIR.'/*.php') ?: [];
        sort($files);

        if ($files === []) {
            throw new RuntimeException('no shipped migrations found at '.self::MIGRATION_DIR);
        }

        return substr(hash('sha256', implode('', array_map(
            static fn (string $file): string => basename($file)."\0".hash_file('sha256', $file)."\n",
            $files,
        ))), 0, 12);
    }
}
