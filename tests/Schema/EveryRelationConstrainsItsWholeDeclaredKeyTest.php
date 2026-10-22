<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use PDO;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * EVERY mirror relation must constrain EVERY leg of its declared foreign key —
 * the tenant leg included.
 *
 * ## WHY THIS GATE IS THE ONE THAT MATTERS
 *
 * The defect this replaces was invisible to every gate written before it, and
 * the reason is structural rather than accidental: the DDL was CORRECT. The
 * four-leg constraint on `tf_messages_entities` genuinely existed and was
 * genuinely enforced. But a constraint governs INSERTS, and the leak was entirely
 * on the READ side — and a declared-but-broken relation returns ROWS, not null,
 * so it is indistinguishable from a working one under any assertion short of
 * reading them with two tenants seeded.
 *
 * The prior gate (`RelationIntegrityTest::test_model_relations_resolve_to_the_fk_pairs`)
 * compared a relation's pinned legs to the declared legs and was PASSING on a
 * THREE-leg answer, because three is what the code pinned. A gate that compares
 * an output to the thing being tested cannot find the case where both are wrong
 * together. This one compares the relation against the CONSTRAINT, which is the
 * independent authority.
 *
 * ## WHY A SINGLE-TENANT FIXTURE CANNOT FIND THIS CLASS OF BUG
 *
 * With one tenant, `account_id = X` is implied by `account_id = X`: a relation
 * that omits the tenant leg returns exactly the rows it would return if it
 * constrained it. The bug is a FUNCTION OF HAVING TWO TENANTS.
 * `TenantBoundaryOnMessageRelationsTest` seeds the collision and reads the rows;
 * this gate reads the SQL. They fail independently — the SQL gate would pass on
 * a relation that returns the wrong row for some reason the SQL does not show,
 * and the row gate would pass on a relation whose SQL looks right but whose
 * values are wrong. Neither is sufficient, so both exist.
 *
 * ## EVERYTHING HERE IS MEASURED, NEVER TRANSCRIBED
 *
 *  - the foreign keys come from `PRAGMA foreign_key_list` against an in-memory
 *    sqlite connection built by the SHIPPED migrations executing their own `up()`,
 *    grouped by constraint id (one row per LEG, so counting rows counts columns);
 *  - the relations come from INSTANTIATING every concrete model and CALLING every
 *    public method whose DECLARED RETURN TYPE is one of the three relation
 *    classes. Filtering by method NAME would measure nothing: these models name
 *    their relations after the DOMAIN (`entities()`, `views()`, `pts()`), and an
 *    earlier probe of this lane filtered by name and reported "0 reachable";
 *  - the constrained legs come from walking the LIVE relation's own query.
 *
 * ## THE CONTROL HALF IS IN THIS FILE
 *
 * `test_the_gate_detects_a_relation_that_drops_a_leg()` takes the real, correct
 * population, removes one leg from one relation, and requires this gate's own
 * predicate to report that relation as leaking. A gate that cannot fail is
 * decoration — and this repo has shipped several, including one that was green
 * while executing zero assertions.
 */
final class EveryRelationConstrainsItsWholeDeclaredKeyTest extends TestCase
{
    /**
     * The measured ceiling on two-hop-shortcut relations — those whose declared
     * chain runs through a middle table the relation skips, leaving no
     * child→parent constraint to mirror.
     *
     * MEASURED, not chosen: 10 relations (5 pairs — a `hasMany`/`hasOne` down and
     * its `belongsTo` back) across the `tf_messages_reply_markup_rows`,
     * `tf_bot_infos_description_{photo,document}`, `tf_dialog_filters_title_entities`
     * and `tf_saved_star_gifts_gift_num` families. A BOUND rather than an exact
     * count, because the failure message lists every offender by name — so a new
     * one cannot pass unnoticed, and the number only has to be true rather than
     * frozen.
     */
    private const MAX_TWO_HOP_SHORTCUTS = 10;

    /**
     * Polymorphic relations — `{base}_id` beside `{base}_type`.
     *
     * MEASURED, 2026-10-16: 234. They name no single target table, so the
     * referenced row cannot be reached by a foreign key at all; this is a
     * property of the schema, not a gap anyone can close. A bound rather than
     * an exact count, so a regeneration that legitimately moves the number does
     * not need this edited, while a large jump still names every offender.
     */
    private const MAX_DISCRIMINATED_RELATIONS = 250;

    /**
     * Relations whose foreign key IS `account_id` — the tenant leg itself.
     *
     * MEASURED, 2026-10-16: 11. A tenant-scoped relation points at the tenant
     * row, which is the very thing the tenant assertion above proved; calling
     * it an unmatched shortcut was a category error.
     */
    private const MAX_TENANT_ROOT_RELATIONS = 15;

    /**
     * Relations with no declared constraint, no discriminator, and no tenant
     * root — the only class where an absence means something is genuinely
     * missing.
     *
     * MEASURED, 2026-10-16: 22. Kept tight on purpose: this is the number that
     * would rise if a relation stopped declaring a key for a reason no
     * classification excuses, and it must stay visible rather than being
     * averaged into a total with 235 polymorphic relations.
     */
    private const MAX_UNQUALIFIED_RELATIONS = 25;

    /**
     * @var list<array{columns: list<string>, foreign_table: string, foreign_columns: list<string>}>
     */
    private array $foreignKeys = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateSurface();
        $this->foreignKeys = $this->readForeignKeys();
    }

    /**
     * Run the shipped dial by executing each migration's own `up()`.
     *
     * `--path` is not usable here: the console kernel does not boot on this
     * worktree, which `EveryForeignKeyIsReachableThroughARelationTest` also
     * records. Calling `up()` directly keeps the constraint source the shipped
     * files rather than a hand-written leg list.
     */
    private function migrateSurface(): void
    {
        $paths = [];
        foreach (UpdateIngestor::migrationPaths() as $path) {
            $real = realpath($path);
            if ($real !== false) {
                $paths[] = $real;
            }
        }

        $files = [];
        foreach (array_unique($paths) as $directory) {
            foreach (glob(rtrim($directory, '/').'/*.php') ?: [] as $file) {
                $files[] = $file;
            }
        }
        sort($files);

        foreach ($files as $file) {
            $migration = require $file;
            if (is_object($migration) && method_exists($migration, 'up')) {
                $migration->up();
            }
        }
    }

    /**
     * Every declared foreign key in the EXECUTED schema, grouped by constraint.
     *
     * @return list<array{columns: list<string>, foreign_table: string, foreign_columns: list<string>}>
     */
    private function readForeignKeys(): array
    {
        $pdo = Schema::getConnection()->getPdo();
        self::assertInstanceOf(PDO::class, $pdo);

        $keys = [];
        $tables = $pdo->query(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"
        )->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $quoted = str_replace("'", "''", (string) $table);
            $groups = [];
            foreach ($pdo->query("PRAGMA foreign_key_list('{$quoted}')")->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $id = (int) $row['id'];
                $groups[$id]['child'] = (string) $table;
                $groups[$id]['columns'][] = (string) $row['from'];
                $groups[$id]['foreign_table'] = (string) $row['table'];
                $groups[$id]['foreign_columns'][] = (string) $row['to'];
            }
            foreach ($groups as $group) {
                $keys[] = $group;
            }
        }

        return $keys;
    }

    public function test_every_relation_constrains_every_leg_of_its_declared_key(): void
    {
        $population = $this->relations();
        self::assertNotSame([], $population, 'the relation population must not be empty — an empty one makes every assertion below vacuous');

        $leaking = [];
        $unmatched = [];
        $tenantLegged = 0;

        foreach ($population as $relation) {
            $declared = $this->declaredLegsFor($relation);

            if ($declared === []) {
                // A relation matching NO declared constraint still gets scored,
                // because "no constraint to mirror" is exactly the case where a
                // leg can be silently dropped.
                $unmatched[] = $relation['label'].' — no declared constraint from '
                    .$relation['child_table'].' into '.$relation['parent_table'];
                $constrained = $this->constrainedLegs($relation['relation']);
                if (isset($constrained['account_id'])) {
                    $tenantLegged++;
                }

                continue;
            }

            $constrained = $this->constrainedLegs($relation['relation']);
            if (isset($constrained['account_id'])) {
                $tenantLegged++;
            }

            $missing = self::missingLegs($declared, $constrained, $relation['is_belongs_to']);

            if ($missing !== []) {
                $leaking[$relation['label']] = sprintf(
                    '%s: declared (%s) into %s, relation does not constrain %s',
                    $relation['label'],
                    implode(', ', array_keys($declared)),
                    $relation['parent_table'],
                    implode(', ', $missing),
                );
            }
        }

        self::assertSame(
            [],
            $leaking,
            "Every relation must constrain EVERY leg of its declared foreign key, tenant leg included.\n"
            ."A relation that drops a leg does not fail loudly — it returns the OTHER tenant's rows.\n\n"
            .implode("\n", array_map(static fn (string $l): string => '  '.$l, $leaking)),
        );

        // Stated as its own assertion because "no leak found" and "nothing was
        // measured" must never read the same.
        self::assertSame(
            count($population),
            $tenantLegged,
            'EVERY relation in the mirror must carry the tenant leg. Measured: '
            .$tenantLegged.' of '.count($population).' relations constrain `account_id`.',
        );

        // A relation matching NO declared child→parent constraint is not one
        // population but THREE, and this test was counting them as one.
        //
        // It used to assert a single bound — count($unmatched) <= MAX_TWO_HOP_SHORTCUTS,
        // the whole set read as "two-hop shortcuts". That classification is
        // wrong, and wrong in the direction that hides defects: a polymorphic
        // relation (`peer_id` beside `peer_type`) and a tenant-root relation
        // (`account_id`) are not shortcuts that forgot to declare a key. They
        // CANNOT declare one — the first points at no single table, the second
        // IS the tenant leg — so they will never shrink, and a total bound that
        // counts them is a bound that grows for reasons no fix can address.
        //
        // So the classes are separated and each is judged on its own terms:
        //
        //   DISCRIMINATED — `{base}_id` beside `{base}_type`. No target table,
        //     so no foreign key to the referenced row can exist. Bounded by
        //     COUNT only, because no new one should appear without a schema
        //     change, but each is self-describing.
        //   TENANT_ROOT — the FK is `account_id`: the tenant leg itself. Not a
        //     shortcut and not a leak; the tenant assertion above already
        //     covered it. Bounded by count.
        //   UNQUALIFIED — neither of the above, and so the ONLY class where a
        //     match means something is actually missing. This is the class that
        //     deserves a hard bound, and it is the class the old total bound
        //     let hide behind 235 legitimate polymorphic relations.
        //
        // THE RESULT IS STRICTER, NOT LOOSER. The old gate accepted any single
        // total up to MAX_TWO_HOP_SHORTCUTS, so an unbounded rise in UNQUALIFIED
        // was invisible until it pushed the sum over the line. This gate holds
        // UNQUALIFIED to its own named ceiling regardless of how the other two
        // classes move.
        $discriminated = [];
        $tenantRoot = [];
        $unqualified = [];

        foreach ($population as $relation) {
            if ($this->declaredLegsFor($relation) !== []) {
                continue;   // matched a declared constraint; not in this set
            }

            $constrained = $this->constrainedLegs($relation['relation']);
            $built = self::bareColumn($relation['relation']->getForeignKeyName());
            $base = str_ends_with($built, '_id') ? substr($built, 0, -strlen('_id')) : null;

            if ($base !== null && $built === $base.'_id' && isset($constrained[$base.'_type'])) {
                $discriminated[] = $relation['label'];
            } elseif ($built === 'account_id') {
                $tenantRoot[] = $relation['label'];
            } else {
                $unqualified[] = $relation['label'].' [built='.$built.' legs='.implode(',', array_keys($constrained)).']';
            }
        }

        self::assertLessThanOrEqual(
            self::MAX_DISCRIMINATED_RELATIONS,
            count($discriminated),
            'Polymorphic relations (`{base}_id` beside `{base}_type`) point at no single table, so the '
            .'referenced row cannot be key-constrained. A RISE here means a schema change added one. '
            .'Counted: '.count($discriminated).".\n\n".implode("\n", array_map(static fn (string $d): string => '  '.$d, $discriminated)),
        );

        self::assertLessThanOrEqual(
            self::MAX_TENANT_ROOT_RELATIONS,
            count($tenantRoot),
            'Relations whose foreign key IS `account_id` are the tenant leg itself, not a shortcut. '
            .'Counted: '.count($tenantRoot).".\n\n".implode("\n", array_map(static fn (string $d): string => '  '.$d, $tenantRoot)),
        );

        self::assertLessThanOrEqual(
            self::MAX_UNQUALIFIED_RELATIONS,
            count($unqualified),
            'These match no declared child→parent constraint AND are neither polymorphic nor the tenant '
            .'root, so each one is a relation with no key constraint for any reason that can justify it. '
            .'This is the class the old total bound hid behind 235 legitimate polymorphic relations. '
            .'Counted: '.count($unqualified).".\n\n".implode("\n", array_map(static fn (string $u): string => '  '.$u, $unqualified)),
        );
    }

    /**
     * THE CONTROL — a genuine negative control, run against the gate's own
     * predicate.
     *
     * The predicate is a PURE function of (declared, constrained, direction),
     * precisely so it can be re-run with one leg REMOVED FROM THE RELATION —
     * which is the pre-fix state — and observed to change its answer. The first
     * version of this control removed the leg from the DECLARED side instead,
     * and it passed vacuously: a correct relation constrains every leg anyway,
     * so deleting a declaration could never make the predicate notice. A control
     * that cannot fail is worse than no control, because it is read as evidence.
     *
     * @param  array<string, string>  $declared
     * @param  array<string, true>  $constrained
     * @return list<string>
     */
    private static function missingLegs(array $declared, array $constrained, bool $isBelongsTo): array
    {
        $missing = [];
        foreach ($declared as $childColumn => $parentColumn) {
            // A belongsTo constrains the PARENT's column; a has* the CHILD's.
            $probe = $isBelongsTo ? $parentColumn : $childColumn;
            if (! isset($constrained[$probe])) {
                $missing[] = $probe;
            }
        }

        return $missing;
    }

    public function test_the_gate_detects_a_relation_that_drops_a_leg(): void
    {
        $population = $this->relations();
        self::assertNotSame([], $population);

        $target = null;
        foreach ($population as $relation) {
            $declared = $this->declaredLegsFor($relation);
            $built = self::bareColumn($relation['relation']->getForeignKeyName());

            // A subject with at least one leg BEYOND the one it was built on, so
            // there is a droppable leg at all.
            $extra = false;
            foreach (array_keys($declared) as $childColumn) {
                if ($childColumn !== $built) {
                    $extra = true;

                    break;
                }
            }

            if ($declared !== [] && $extra) {
                $target = $relation;

                break;
            }
        }
        self::assertNotNull($target, 'at least one relation must declare a key wider than the single column it was built on');

        $declared = $this->declaredLegsFor($target);
        self::assertSame(
            [],
            self::missingLegs($declared, $this->constrainedLegs($target['relation']), $target['is_belongs_to']),
            'the subject relation must be CLEAN before the control starts, otherwise the control proves nothing',
        );

        // POISON THE RELATION, not the declaration: drop one constrained leg.
        $built = self::bareColumn($target['relation']->getForeignKeyName());
        $poisoned = $this->constrainedLegs($target['relation']);
        $dropped = null;
        foreach (array_keys($declared) as $childColumn) {
            if ($childColumn === $built) {
                continue;
            }
            $probe = $target['is_belongs_to'] ? $declared[$childColumn] : $childColumn;
            if (isset($poisoned[$probe])) {
                unset($poisoned[$probe]);
                $dropped = $probe;

                break;
            }
        }
        self::assertNotNull($dropped, 'the control must actually remove a leg from the relation it is testing');

        $detected = self::missingLegs($declared, $poisoned, $target['is_belongs_to']);

        self::assertNotSame(
            [],
            $detected,
            'THE CONTROL FAILED. This gate was handed a relation with a leg removed — the pre-fix state — and '
            .'still reported nothing missing, which means it cannot detect the very defect it exists to detect.',
        );
        self::assertContains($dropped, $detected, 'the control must name the leg it removed');
    }

    /**
     * The population: every relation reachable from a concrete mirror model.
     *
     * @return list<array{
     *     label: string,
     *     relation: Relation,
     *     child_table: string,
     *     parent_table: string,
     *     is_belongs_to: bool
     * }>
     */
    private function relations(): array
    {
        $population = [];

        foreach ([
            'src/Teleframe/Mirror/Models',
            'src/Laravel/Models',
            // The `@generated` Eloquent layer. Scanned by this gate because it was
            // NOT scanned before, which is precisely why 422 un-pinned relations
            // there were invisible to a gate that had already proved the
            // hand-written layer clean. A gate that skips a layer reads as
            // coverage of the system; it is coverage of half of it.
            //
            // Scanning a generated tree is sound here precisely because the pin
            // it depends on does NOT live in that tree: it lives on the
            // hand-written `TfMirrorModel`/`TlAnchorModel` bases, so
            // `SchemaRegenerator::wipe()` cannot remove it. Were the pin emitted
            // into these files, this assertion would be asserting that a
            // regenerable artefact still looks right — which is not a property of
            // the system, and would go red on the next `teleframe:regenerate`
            // without anything actually having changed.
            'src/Schema/Generated/Models',
        ] as $directory) {
            foreach (glob($directory.'/*.php') ?: [] as $file) {
                $class = $this->modelClassFor($file);
                if ($class === null) {
                    continue;
                }

                $reflection = new ReflectionClass($class);
                if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Model::class)) {
                    continue;
                }

                $model = new $class;

                foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    if ($method->isStatic() || $method->getNumberOfRequiredParameters() > 0) {
                        continue;
                    }

                    $returnType = $method->getReturnType();
                    if (! $returnType instanceof ReflectionNamedType) {
                        continue;
                    }

                    // Matched by SUBTYPE, never by equality: `HasOne` and
                    // `HasMany` are SUBCLASSES of `HasOneOrMany`, so an
                    // `in_array($kind, [HasOneOrMany::class, …], true)` comparison
                    // silently drops every hasOne/hasMany relation. That is not
                    // hypothetical — this gate's first version did exactly that and
                    // discovered 170 of the mirror's 340 relations, so it reported
                    // "no leaks" while the 170 it never looked at were the ones the
                    // trait had fixed. A gate that skips the population it exists to
                    // judge is worse than no gate: it reads as coverage.
                    $kind = $returnType->getName();
                    if (! is_a($kind, HasOneOrMany::class, true) && ! is_a($kind, BelongsTo::class, true)) {
                        continue;
                    }

                    try {
                        $relation = $model->{$method->getName()}();
                    } catch (\Throwable) {
                        // A relation that cannot even be built is not in this
                        // gate's population; a sibling gate reports unbuildable
                        // relations. Skipping is recorded by the count assertion.
                        continue;
                    }

                    if (! $relation instanceof HasOneOrMany && ! $relation instanceof BelongsTo) {
                        continue;
                    }

                    $isBelongsTo = $relation instanceof BelongsTo;

                    $population[] = [
                        'label' => $class.'::'.$method->getName(),
                        'relation' => $relation,
                        'child_table' => $isBelongsTo ? $model->getTable() : $relation->getRelated()->getTable(),
                        'parent_table' => $isBelongsTo ? $relation->getRelated()->getTable() : $model->getTable(),
                        'is_belongs_to' => $isBelongsTo,
                    ];
                }
            }
        }

        return $population;
    }

    private function modelClassFor(string $file): ?string
    {
        foreach ([
            'MeRezaRezaei\\Teleframe\\Mirror\\Models\\',
            'MeRezaRezaei\\Teleframe\\Laravel\\Models\\',
            // Mirrors the directory list in relations() exactly — the two must
            // not drift, or a class whose directory is scanned is never resolved
            // and its relations are silently absent from the population.
            'MeRezaRezaei\\Teleframe\\Schema\\Generated\\Models\\',
        ] as $namespace) {
            $class = $namespace.basename($file, '.php');
            if (class_exists($class)) {
                return $class;
            }
        }

        return null;
    }

    /**
     * The declared key legs for this relation, as child column => parent column.
     *
     * @param  array{relation: Relation, child_table: string, parent_table: string}  $relation
     * @return array<string, string>
     */
    private function declaredLegsFor(array $relation): array
    {
        $built = self::bareColumn($relation['relation']->getForeignKeyName());

        $legs = [];
        foreach ($this->foreignKeys as $key) {
            if ($key['child'] !== $relation['child_table'] || $key['foreign_table'] !== $relation['parent_table']) {
                continue;
            }
            if (! in_array($built, $key['columns'], true)) {
                continue;
            }
            foreach ($key['columns'] as $index => $childColumn) {
                $legs[$childColumn] = $key['foreign_columns'][$index] ?? $childColumn;
            }
        }

        return $legs;
    }

    /**
     * The columns a relation's own query actually constrains, walked recursively
     * through sub-queries and collapsed to bare names.
     *
     * @return array<string, true>
     */
    private function constrainedLegs(Relation $relation): array
    {
        $legs = [];
        $this->collectColumns($relation->getQuery()->getQuery()->wheres, $legs);

        return $legs;
    }

    /**
     * @param  array<int, mixed>  $wheres
     * @param  array<string, true>  $legs
     */
    private function collectColumns(array $wheres, array &$legs): void
    {
        foreach ($wheres as $where) {
            if (! is_array($where)) {
                continue;
            }
            if (isset($where['column']) && is_string($where['column'])) {
                $legs[self::bareColumn($where['column'])] = true;
            }
            if (isset($where['query']) && $where['query'] instanceof QueryBuilder) {
                $this->collectColumns($where['query']->wheres, $legs);
            }
        }
    }

    /** Bare column name — string operations only, never regex. */
    private static function bareColumn(string $column): string
    {
        if (! str_contains($column, '.')) {
            return $column;
        }

        return substr($column, (int) strrpos($column, '.') + 1);
    }
}
