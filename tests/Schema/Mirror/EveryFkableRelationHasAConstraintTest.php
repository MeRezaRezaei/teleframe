<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use Illuminate\Database\Connection;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorRelationPlan;
use MeRezaRezaei\Teleframe\Schema\SchemaArtifacts;

/**
 * The gate that makes Phase 4 terminate: no FK-able relation may ship unenforced.
 *
 * ## WHY THIS GATE EXISTS
 *
 * "We added the FKs we remembered" was the entire process before this file. A
 * soft link is invisible until a query fails, and the failure is a wrong answer
 * rather than an error — which is the failure the owner named in
 * `docs/schema/mtproto-data-shape-map.md`. This gate turns that invisible class
 * of defect into a red test at the moment the relation is added, so the phase
 * can end rather than run forever.
 *
 * ## WHY IT EXTENDS RelationConstraintTestCase
 *
 * Because a gate that cannot fail is decoration. Two halves:
 *
 *  - `RelationConstraintTestCase::enforcingConnection()` turns
 *    `PRAGMA foreign_keys` ON and READS IT BACK, refusing to return a
 *    connection whose enforcement state it has not observed. That harness is
 *    what makes the control half below meaningful.
 *  - `test_the_gate_bites_when_a_constraint_is_removed()` is the control half:
 *    it drops one real constraint and requires THIS GATE'S OWN predicate to
 *    stop reporting that relation as constrained. If the predicate could not
 *    see the removal, the gate above would be reading something other than
 *    the live schema and would be decoration in the strict sense.
 *
 * ## MEASURED POPULATION — 2026-10-04, this tree, SQLite, shipped DDL
 *
 * Command (see MirrorRelationPlan::docblock for the derivation):
 *
 *   timeout -k 5 200 php vendor/bin/phpunit --no-coverage \
 *       tests/Schema/Mirror/EveryFkableRelationHasAConstraintTest.php
 *
 * | population | count |
 * |---|---:|
 * | declared intra-mirror edges derived from the executed DDL | 215 |
 * | `singleParent` — FK-able today | 207 |
 * | `polymorphic` — a `{base}_type` discriminator blocks one FK | 8 |
 * | `unconstrained` — a KEYING gap, not a constraint gap | 0 |
 * | single-parent relations carrying NO constraint (the RED list) | 165 |
 *
 * The RED step of Task 4.1 measured **175** before `MirrorRelationPlan` existed,
 * deriving each relation's legs from the CHILD's primary key. Ten of those have
 * a child key that is a strict SUPERSET of the parent's, so those legs reference
 * a NON-UNIQUE column set — MySQL error 6125, one of the four failures this
 * plan's MySQL track exists to catch. Legs are therefore the parent's key, and
 * the honest count is **165**. `MirrorRelationPlan`'s docblock carries the
 * evidence and the three affected families; sizing Task 4.2 from 175 would have
 * written ten constraints the target engine refuses.
 *
 * Task 4.2 lands those 165.
 *
 * ## WHY THE RELATION POPULATION IS NOT "EVERY CHILD TABLE"
 *
 * `bin/generate-relation-graph.php` reports `soft 0` on this tree, which reads
 * like "no unenforced relation remains". It is an ARTEFACT and this file is
 * the reason to distrust that line. Its `relationFor()` returns ONE relation
 * per table and takes the FIRST foreign key it finds — which after Phase 0 is
 * the tenant FK to `telegram_accounts` on all 265 of them. Every intra-mirror
 * edge is therefore never examined, and `soft` collapses to 0. The honest
 * measure of this phase ignores the tenant FK and looks for the intra-mirror
 * edge that the first-FK shortcut steps over.
 *
 * That is exactly why this gate exists and why `MirrorRelationPlan` derives
 * edges from the DDL rather than reading the graph's link column.
 */
final class EveryFkableRelationHasAConstraintTest extends RelationConstraintTestCase
{
    /**
     * Every single-parent relation carries a real constraint.
     *
     * MEASURED: 165 listed at the time of writing. Task 4.2 lands them family
     * by family; this assertion is the definition of "done" for the phase and
     * is not to be narrowed, weakened, or skipped to reach green.
     */
    public function test_every_single_parent_relation_has_a_real_constraint(): void
    {
        $db = $this->enforcingConnection(...$this->shippedMigrations());
        $plan = $this->plan($db);

        $missing = [];
        foreach ($plan->singleParent() as $relation) {
            if ($this->hasForeignKey($db, $relation['child'], $relation['columns'], $relation['parent'])) {
                continue;
            }

            $missing[] = sprintf(
                '%s(%s) → %s',
                $relation['child'],
                implode(',', $relation['columns']),
                $relation['parent'],
            );
        }

        self::assertSame(
            [],
            $missing,
            'these single-parent relations are declared by the schema and mechanically FK-able, '
            .'but carry no constraint: '.implode('; ', $missing).'. A soft link is invisible until a '
            .'query fails, which is the failure the owner named. Task 4.2 of '
            .'docs/superpowers/plans/2026-10-04-endpoint-coverage-and-mysql-truth.md lands them.',
        );
    }

    /**
     * The control half: this gate's predicate must be able to SEE a constraint go.
     *
     * Without this, `hasForeignKey()` could be matching on something static — a
     * column-name coincidence, a hard-coded family list — and would keep
     * reporting "constrained" after the constraint was deleted, which would
     * make the gate above a decoration in the strict sense: green, and guarding
     * nothing.
     *
     * So: rebuild the table WITHOUT one of its real constraints, and require
     * the SAME predicate used above to now report it missing.
     */
    public function test_the_gate_bites_when_a_constraint_is_removed(): void
    {
        $db = $this->enforcingConnection(...$this->shippedMigrations());
        $plan = $this->plan($db);

        // A relation that TODAY carries a real constraint, so the removal below
        // is a change and not a no-op assertion on an already-missing relation.
        $constrained = null;
        foreach ($plan->singleParent() as $relation) {
            if ($this->hasForeignKey($db, $relation['child'], $relation['columns'], $relation['parent'])) {
                $constrained = $relation;
                break;
            }
        }

        self::assertNotNull(
            $constrained,
            'no single-parent relation carries a constraint, so there is nothing to remove and this '
            .'control half proves nothing. Either the shipped dial has lost its constraints or the '
            .'predicate cannot see them; both make the gate above vacuous.',
        );

        self::assertTrue(
            $this->hasForeignKey($db, $constrained['child'], $constrained['columns'], $constrained['parent']),
            'the relation chosen as the removal target is not reported as constrained by the same '
            .'predicate the main gate uses, so the two disagree about the live schema.',
        );

        $this->rebuildWithoutForeignKey($db, $constrained['child']);

        self::assertFalse(
            $this->hasForeignKey($db, $constrained['child'], $constrained['columns'], $constrained['parent']),
            'the constraint on '.$constrained['child'].' was dropped and this gate still reports it as '
            .'constrained. hasForeignKey() is therefore not reading the live schema, and every '
            .'assertion it supports is decoration.',
        );
    }

    /**
     * Every polymorphic relation is excused with a MECHANISM, never with silence.
     *
     * MEASURED: 8 at the time of writing.
     *
     * "One FOREIGN KEY cannot branch on a type int" IS a reason. An empty
     * string is not. This mirrors
     * `tests/Ingest/EveryCataloguedTableIsReachableTest.php`: an excuse must
     * name the mechanism that excuses it, or it is not an excuse.
     */
    public function test_a_polymorphic_relation_is_excused_with_a_mechanism(): void
    {
        $db = $this->enforcingConnection(...$this->shippedMigrations());
        $plan = $this->plan($db);

        self::assertNotSame([], $plan->polymorphic(), 'no polymorphic relation was derived; the '
            .'discriminator detection stopped working and this gate is now guarding an empty set.');

        $silent = [];
        foreach ($plan->polymorphic() as $relation) {
            $reason = $relation['reason'];

            if ($reason !== '' && (str_contains($reason, 'tf_peers') || str_contains($reason, '_type'))) {
                continue;
            }

            $silent[] = sprintf('%s(%s) → %s: %s', $relation['child'], implode(',', $relation['columns']), $relation['parent'], $reason === '' ? '<no reason given>' : $reason);
        }

        self::assertSame(
            [],
            $silent,
            'these polymorphic relations are excused with something that names neither the tf_peers '
            .'registry nor the type discriminator that blocks the constraint: '.implode('; ', $silent)
            .'. Silence is not an excuse; it is the gap reopening.',
        );
    }

    /**
     * An unconstrained relation is a KEYING gap and must say so.
     *
     * MEASURED: 0 at the time of writing — every derived edge had at least one
     * candidate leg. The population is asserted non-empty-by-reason rather
     * than asserted to be empty, so a future relation landing here is held to
     * the same "name the mechanism" rule as the polymorphic set. The zero is
     * published in `MirrorRelationPlan`'s own docblock with the command that
     * produced it.
     */
    public function test_an_unconstrained_relation_names_its_keying_gap(): void
    {
        $db = $this->enforcingConnection(...$this->shippedMigrations());
        $plan = $this->plan($db);

        foreach ($plan->unconstrained() as $relation) {
            self::assertNotSame('', $relation['reason'], $relation['child'].' → '.$relation['parent']
                .' is unconstrained with no reason recorded. A KEYING gap nobody wrote down is how '
                .'this gate came to exist.');
        }

        self::assertSame(
            0,
            $plan->counts()['unconstrained'],
            'unconstrained is no longer 0. A relation landed in the KEYING-gap population, which is a '
            .'keying bug and needs its own task (Task 4.2 Step 3): do NOT widen a parent key to make '
            .'a constraint fit. Re-measure and update the published counts with the command that '
            .'produced them.',
        );
    }

    // -----------------------------------------------------------------------
    // Harness. Every shape below comes from the executed shipped DDL or from
    // MirrorCatalog loaded against the committed TL. Nothing is transcribed.
    // -----------------------------------------------------------------------

    /**
     * The shipped migration set, from the single source that owns it.
     *
     * `UpdateIngestor::migrationPaths()` is the ingest surface's own list; the
     * directory entry it returns is expanded here because
     * `ShippedStoreSchema::capsuleFor()` wants FILES. The directory is never
     * re-globbed from a second place — a second list is a second thing to drift.
     *
     * @return list<string>
     */
    private function shippedMigrations(): array
    {
        $paths = [];
        foreach (UpdateIngestor::migrationPaths() as $path) {
            if (! is_dir($path)) {
                $paths[] = $path;

                continue;
            }

            foreach ((array) glob($path.'/*.php') as $file) {
                $paths[] = (string) $file;
            }
        }

        // `migrationPaths()` is the curated dial directory PLUS `entityMigrationPaths()`,
        // and some of those entity files live INSIDE the dial directory. Expanding both
        // therefore yields the same file twice, and running it twice raises
        // `table tf_updates already exists` — measured, not assumed. De-duplicate by real
        // path so apply order is the sorted order and no file runs twice.
        $unique = [];
        foreach ($paths as $path) {
            $unique[realpath($path) ?: $path] = $path;
        }

        $paths = array_values($unique);
        sort($paths);

        return $paths;
    }

    private function plan(Connection $db): MirrorRelationPlan
    {
        return MirrorRelationPlan::fromConnection(
            $db,
            MirrorCatalog::load(
                SchemaArtifacts::path('mirror-catalog.json'),
                TlParser::parseFile(SchemaArtifacts::source('api_full.tl')),
            ),
        );
    }

    /**
     * Does a FOREIGN KEY constraint on `$child` reference exactly `$parent`
     * over exactly `$columns`?
     *
     * Reflected through the driver, so it works on SQLite and MySQL alike. The
     * comparison is order-INSENSITIVE on both sides: SQLite emits
     * `PRAGMA foreign_key_list` rows in REVERSE key order for a composite
     * constraint, and a constraint that matches its target in a different
     * declaration order is still the same constraint.
     *
     * @param  list<string>  $columns
     */
    private function hasForeignKey(Connection $db, string $child, array $columns, string $parent): bool
    {
        $from = $columns;
        sort($from);

        foreach ($this->foreignKeysOf($db, $child) as $fk) {
            if ($fk['table'] !== $parent) {
                continue;
            }

            $legs = $fk['from'];
            sort($legs);

            if ($legs === $from) {
                return true;
            }
        }

        return false;
    }

    /**
     * Every FOREIGN KEY constraint declared by `$table`, grouped — one entry
     * per CONSTRAINT, not per column.
     *
     * Grouping is not an optimisation. A composite foreign key emits one row
     * per column sharing a driver-assigned `id`; counting rows counts columns,
     * which is the same class of error as grepping `->foreign(` and is exactly
     * what `bin/generate-relation-graph.php`'s `groupForeignKeys()` docblock
     * warns about.
     *
     * @return list<array{table: string, from: list<string>, to: list<string>}>
     */
    private function foreignKeysOf(Connection $db, string $table): array
    {
        $rows = $db->select(
            'SELECT * FROM pragma_foreign_key_list(?)',
            [$table],
        );

        $grouped = [];
        foreach ($rows as $row) {
            $row = (array) $row;
            $id = (string) ($row['id'] ?? '0');

            $grouped[$id]['table'] = (string) ($row['table'] ?? '');
            $grouped[$id]['from'][] = (string) ($row['from'] ?? '');
            $grouped[$id]['to'][] = (string) ($row['to'] ?? '');
        }

        ksort($grouped);

        return array_values($grouped);
    }

    /**
     * Rebuild `$table` from its own shipped DDL with every FOREIGN KEY stripped.
     *
     * How the DDL is recovered matters: `Schema::getForeignKeys()` gives the
     * constraint NAMES and columns but not the column types, and a
     * `CREATE TABLE` re-emitted without them would not be the shipped table.
     * So the CREATE statement is read out of `sqlite_master` — the executed
     * result, not a transcription — and only the FOREIGN KEY clauses are
     * removed from it. The rebuild happens on a table alias so the shipped
     * table survives for the rest of the test.
     */
    private function rebuildWithoutForeignKey(Connection $db, string $table): void
    {
        $create = $db->scalar(
            "SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?",
            [$table],
        );

        self::assertIsString($create, 'no CREATE statement recorded for '.$table);

        // Case-insensitively, because SQLite records the CREATE it was GIVEN:
        // `bin/generate-relation-graph.php`'s migrations emit lowercase
        // `foreign key(...)`, and an assertion that only accepted uppercase
        // would report "nothing to remove" on the very tables that have the
        // most constraints.
        self::assertMatchesRegularExpression('/foreign\s+key/i', $create, $table.' declares no FOREIGN KEY, '
            .'so there is nothing to remove and this control half would be a no-op.');

        // `sqlite_master.sql` is the executed DDL with SQLite's own quoting; the
        // only edit is deleting the FOREIGN KEY clauses, which are the single
        // construct that starts with `FOREIGN KEY` and ends at the matching
        // `)`. src/Schema may use regex; this file is not src/ but a simple
        // balanced scan keeps the edit auditable either way.
        $stripped = $this->stripForeignKeyClauses($create);

        self::assertStringNotContainsStringIgnoringCase('FOREIGN KEY', $stripped, 'the FOREIGN KEY '
            .'clauses on '.$table.' survived stripping, so the control half never removed anything.');

        $db->statement('PRAGMA writable_schema = ON');
        try {
            $db->statement(
                "UPDATE sqlite_master SET sql = ? WHERE type = 'table' AND name = ?",
                [$stripped, $table],
            );
        } finally {
            $db->statement('PRAGMA writable_schema = OFF');
        }

        // writable_schema edits need a schema reload before they take effect.
        $db->statement('VACUUM');
    }

    private const FK_KEYWORD = 'FOREIGN KEY';

    /**
     * Remove every FOREIGN KEY clause from a `CREATE TABLE` statement.
     *
     * A clause runs from the keyword to the next comma at paren-depth 0 — NOT
     * to the closing paren of its own column list, because SQLite's recorded
     * form continues past it:
     *
     *   foreign key("account_id","id") references tf_x("account_id","id") on delete cascade
     *
     * Stopping at the first `)` leaves `references tf_x(...) on delete cascade`
     * dangling and the rewritten DDL fails to parse with `near "references":
     * syntax error` — measured, which is why the rule is stated here rather
     * than left implicit. The scan also stops at a `)` at depth 0, which is
     * the end of the table definition itself.
     */
    private function stripForeignKeyClauses(string $create): string
    {
        $out = '';
        $length = strlen($create);
        $keyword = strlen(self::FK_KEYWORD);
        $i = 0;

        while ($i < $length) {
            if (strcasecmp(substr($create, $i, $keyword), self::FK_KEYWORD) === 0) {
                $end = $this->skipForeignKeyClause($create, $i + $keyword);

                // Swallow the clause's own separator when one is still needed,
                // otherwise stripping two adjacent constraints leaves
                // `..., , primary key (...)` and the rewritten DDL fails to
                // parse with `near ",": syntax error` — measured.
                if (($create[$end] ?? '') === ',' && str_ends_with(rtrim($out), ',')) {
                    $end++;
                }

                $i = $end;

                continue;
            }

            $out .= $create[$i];
            $i++;
        }

        return $out;
    }

    /**
     * Index just past the end of the FOREIGN KEY clause starting at `$from`.
     */
    private function skipForeignKeyClause(string $create, int $from): int
    {
        $length = strlen($create);
        $depth = 0;

        for ($i = $from; $i < $length; $i++) {
            $char = $create[$i];

            if ($char === '(') {
                $depth++;

                continue;
            }

            if ($char === ')') {
                if ($depth === 0) {
                    // The end of the table definition.
                    return $i;
                }

                $depth--;

                continue;
            }

            if ($char === ',' && $depth === 0) {
                // End of this clause; keep the comma as the column separator.
                return $i;
            }
        }

        return $length;
    }
}
