<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\Assert;
use ReflectionClass;

/**
 * The `tf_messages` family is constrained to its parent, and the database says so.
 *
 * ## WHAT IS BEING PROVED
 *
 * `2026_10_04_000800_add_tf_messages_family_foreign_keys.php` adds a FOREIGN KEY
 * from every single-parent relation inside the `tf_messages` family to that
 * parent's key. This file proves the constraints BITE — that a valid child row
 * lands, that an orphan is refused by the constraint and not by anything else,
 * and that the whole thing disappears when the migration does.
 *
 * ## WHY THE DISCOVERY IS RE-IMPLEMENTED HERE, ON PURPOSE
 *
 * Two implementations of one discovery is not duplication to be removed; it is
 * the only way the migration's discovery is CHECKED rather than trusted. This
 * file re-derives the family relations from the BUILT schema by its own code —
 * longest-prefix ancestry whose parent's primary key is a subset of the child's,
 * intersected down to the parent's key — and asserts the result against the
 * executed DDL. If this and the migration's `familyRelations()` ever disagree,
 * one of the assertions below goes red naming the difference. No table list and
 * no join-column list is written into this file: a transcribed list is a second
 * copy of the schema and it drifts.
 *
 * ## WHY EVERY BEHAVIOURAL ASSERTION RUNS ON AN ENFORCING CONNECTION
 *
 * `ShippedStoreSchema` boots sqlite with `PRAGMA foreign_keys` OFF, where a
 * declared constraint is INERT: the DDL is in `sqlite_master`,
 * `Schema::getForeignKeys()` still reports it, and nothing raises. A negative
 * test written on that default is green because no check happened, and would
 * stay green if the migration were deleted outright. Every behavioural
 * connection here goes through `RelationConstraintTestCase::enforcingConnection()`,
 * which turns the pragma on and READS IT BACK before handing it over.
 *
 * ## THE TENANT CONSTRAINT IS THE TRAP THIS FIXTURE IS BUILT AROUND
 *
 * Every `tf_*` table in this family carries its own `_acc_tenant_fk` to
 * `telegram_accounts.user_id` (D-005). So an "orphan" child built naively —
 * inventing an `account_id` that no tenant carries — is refused by the TENANT
 * constraint, and every assertion below would be green while this migration
 * contributed nothing.
 *
 * Every fixture below therefore plants a REAL tenant first, and makes the
 * orphan-ness live ONLY in the message coordinates (`peer_type`/`peer_id`/`id`).
 * The tenant is valid, the parent message does not exist, so the only constraint
 * that can refuse these rows is the one this migration adds.
 *
 * ## AND THE CONTROL HALF, WITHOUT WHICH THE REFUSAL PROVES NOTHING
 *
 * `test_the_orphan_is_admitted_without_this_migration()` inserts the SAME orphan
 * row into the SAME table on the SAME engine with exactly one file withheld from
 * the dial, and requires it to LAND. That is what makes the refusal in
 * `test_the_orphan_is_refused()` attributable to this migration rather than to
 * sqlite, to the pragma, or to the tenant constraint.
 */
final class TfMessagesFamilyForeignKeyTest extends RelationConstraintTestCase
{
    /**
     * The path fragment naming THIS migration's file.
     *
     * Substring, not a full name, so the control half withholds the file without
     * duplicating the date stamp the migration is renamed under.
     */
    private const MIGRATION = '2026_10_04_000800';

    /** The family ROOT. Descendants are discovered by name prefix, never listed. */
    private const FAMILY_ROOT = 'tf_messages';

    /** A descendant of the root keeps its name. */
    private const FAMILY_PREFIX = 'tf_messages_';

    /**
     * The number of single-parent relations MEASURED inside this family on
     * 2026-10-04, and the number this migration therefore constrains.
     *
     * Measured by booting the whole dial on sqlite and asking
     * `bin/generate-relation-graph.php`'s ancestry rule for the family:
     *
     *     38 relations, of which 1 is REFUSED (the reply-markup button leg), so 37 added.
     *
     * Both numbers are pinned so that discovery cannot silently change length in
     * either direction. If a family gains or loses a table, re-measure and bump
     * BOTH here and in the migration — never edit a count to reach a green run.
     *
     * RE-MEASURED 2026-10-05 by lane `SVC-FK`, and the CONSTRAINED figure is the
     * number that moved:
     *
     * ```
     * timeout -k 5 900 vendor/bin/phpunit --no-coverage \
     *     tests/Schema/Mirror/TfMessagesFamilyForeignKeyTest.php
     * ```
     *
     * `MEASURED_RELATIONS` is unchanged at 38, because this file's OWN ancestry
     * rule is untouched by `SVC-FK` and still derives all 38 — which is the
     * point of the second number existing.
     *
     * `MEASURED_CONSTRAINED` moved 37 → **36**, and not because a constraint was
     * dropped for convenience: one relation of the 38 is a SIBLING and must not
     * be constrained at all. See `siblingRelations()` for the full argument; the
     * short version is that `tf_messages_service` is a member of the `Message`
     * union split out of `tf_messages` by `MirrorCtorSplitter`, so no
     * `tf_messages` row is ever written for a `messageService` payload and the
     * constraint had no parent to resolve to. `2026_10_04_001200` drops it on
     * every install that already ran `000800`.
     */
    private const MEASURED_RELATIONS = 38;

    private const MEASURED_CONSTRAINED = 36;

    /**
     * The one relation in this family that is a SIBLING of the root rather than a
     * child of it, and the reason — because a named exclusion is checkable and a
     * silent one is indistinguishable from a bug in the discovery.
     *
     * Read by `siblingRelations()` from `MirrorRelationPlan::sibling()` and then
     * CROSS-CHECKED against this constant, so the two can never drift apart
     * silently: a second sibling appearing makes the gate red here, and a
     * sibling disappearing makes it red in the other direction.
     */
    private const SIBLING = ['tf_messages_service'];

    /** The memoised sibling set for THIS test instance; null until derived. */
    private ?array $siblings = null;

    /** A `telegram_accounts.user_id` this file plants before every fixture. */
    private const TENANT = 1724372757;

    /** A tenant `telegram_accounts` does NOT carry — used by the tenant test. */
    private const FABRICATED_TENANT = 999999999999;

    /**
     * The witness child, and the ONLY reason it was chosen.
     *
     * `tf_messages_grouped_id` has exactly five columns, all NOT NULL, four of
     * which are the parent message key and one of which is the payload. So the
     * fixture cannot accidentally satisfy the constraint through some other
     * declared FK, there is nothing else to build, and every column the
     * constraint names is visible in the insert.
     */
    private const CHILD = 'tf_messages_grouped_id';

    /** Coordinates of the planted parent message. */
    private const MESSAGE = ['peer_type' => 0, 'peer_id' => 555, 'id' => 7];

    /** Coordinates of a message that is NEVER planted — the orphan. */
    private const ABSENT_MESSAGE = ['peer_type' => 0, 'peer_id' => 999999, 'id' => 4242];

    // ------------------------------------------------------------------
    // the dial
    // ------------------------------------------------------------------

    /**
     * Every shipped migration, in apply order.
     *
     * The DIRECTORY list comes from `UpdateIngestor::migrationPaths()` — the
     * same list the ingest seam and the MySQL lane run — rather than from a glob
     * of `src/Laravel/Migrations`, because a glob here would be a second copy of
     * the dial and a second copy drifts. This migration's own file lives in that
     * directory, so if it is ever moved out of the curated dial this suite fails
     * rather than quietly testing a dial without it.
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
                // `migrationPaths()` yields the curated DIRECTORY and then two
                // entity FILES that already live inside it, so a naive expansion
                // applies `2026_09_14_200030` and `…_200031` twice and dies on
                // "table already exists". Deduped by real path, keeping apply
                // order, because the curated directory is listed first and
                // therefore owns the position of both files.
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

    /**
     * A private constant READ FROM THE MIGRATION rather than transcribed here.
     *
     * A test that hardcodes the spelling it is checking cannot notice the
     * spelling change that broke the rollback, so the suffix and the refusal
     * list are read out of the file itself by reflection.
     */
    private function migrationConstant(string $name): mixed
    {
        $migration = require dirname(__DIR__, 3).'/src/Laravel/Migrations/'.self::MIGRATION
            .'_add_tf_messages_family_foreign_keys.php';

        Assert::assertIsObject($migration, 'the migration file did not return an object');

        return (new ReflectionClass($migration))->getConstant($name);
    }

    // ------------------------------------------------------------------
    // discovery, re-implemented
    // ------------------------------------------------------------------

    /**
     * The family relations, discovered from the BUILT schema by this file's own
     * code — NOT by asking `MirrorRelationPlan`, which is the class the
     * migration uses and therefore the thing under test.
     *
     * The rule, independently implemented: a table's parent is the LONGEST
     * surviving table-name prefix whose primary key is a subset of this table's
     * own; the constraint's legs are that parent's key intersected down to the
     * columns this table actually has.
     *
     * @return list<array{child:string, parent:string, columns:list<string>}>
     */
    private function familyRelations(Connection $db): array
    {
        $prefix = $db->getTablePrefix();

        $tables = [];
        foreach ($db->getSchemaBuilder()->getTableListing() as $listed) {
            $bare = str_contains($listed, '.')
                ? substr($listed, (int) strrpos($listed, '.') + 1)
                : $listed;
            if ($prefix !== '' && str_starts_with($bare, $prefix)) {
                $bare = substr($bare, strlen($prefix));
            }
            $tables[] = $bare;
        }
        sort($tables);
        $tableSet = array_flip($tables);

        $columns = [];
        $primaryKey = [];
        foreach ($tables as $table) {
            $cols = [];
            foreach ($db->getSchemaBuilder()->getColumns($table) as $column) {
                $cols[(string) $column['name']] = true;
            }
            $columns[$table] = $cols;

            // The key comes from the driver's INDEX listing, not the column rows:
            // `getColumns()` carries no key position, and a composite FOREIGN
            // KEY must be declared in the parent's key ORDER, which is not the
            // order the columns sit in the table.
            $key = [];
            foreach ($db->getSchemaBuilder()->getIndexes($table) as $index) {
                if (($index['primary'] ?? false) === true) {
                    $key = array_map('strval', $index['columns']);
                    break;
                }
            }
            $primaryKey[$table] = $key;
        }

        $family = [self::FAMILY_ROOT => true];
        foreach ($tables as $table) {
            if (str_starts_with($table, self::FAMILY_PREFIX)) {
                $family[$table] = true;
            }
        }

        $relations = [];
        foreach ($tables as $table) {
            if ($table === self::FAMILY_ROOT) {
                continue;
            }

            $parts = explode('_', $table);
            $parent = null;
            for ($i = count($parts) - 1; $i >= 1; $i--) {
                $candidate = implode('_', array_slice($parts, 0, $i));
                if (! isset($tableSet[$candidate], $family[$candidate])) {
                    continue;
                }
                if (array_diff($primaryKey[$candidate], $primaryKey[$table]) !== []) {
                    continue;
                }
                $parent = $candidate;
                break;
            }

            if ($parent === null) {
                continue;
            }

            $legs = [];
            foreach ($primaryKey[$parent] as $leg) {
                if (isset($columns[$table][$leg])) {
                    $legs[] = $leg;
                }
            }

            $relations[] = ['child' => $table, 'parent' => $parent, 'columns' => $legs];
        }

        return $relations;
    }

    /**
     * The family's SIBLING relations — the ones this migration must NOT
     * constrain, cross-checked against `SIBLING` in both directions.
     *
     * ## WHY THIS FILE'S OWN ANCESTRY RULE IS NOT ENOUGH ANY MORE
     *
     * The rule above is deliberately independent of `MirrorRelationPlan` — it is
     * the second opinion that catches the migration and the plan disagreeing. It
     * is also, on this evidence, the very rule that produced the defect: a table's
     * parent is the LONGEST surviving name prefix whose primary key covers its
     * own, and `tf_messages_service` keeps `tf_messages`'s name AND carries its
     * IDENTICAL primary key `(account_id, peer_type, peer_id, id)` — MEASURED on
     * the executed schema, which is why `000800` could write a legal-looking
     * constraint over it and why nothing in the DDL looked wrong.
     *
     * Two subject rows cannot be parent and child, and the DDL cannot tell them
     * apart (that identical key is the proof), so the sibling classification comes
     * from `MirrorRelationPlan::sibling()`, whose discriminator is the catalog's
     * ctor→table routing. Independence is preserved where it matters — this file
     * still does its own ancestry and still owns its own numbers — and the one
     * thing it now CONSUMES is pinned both ways, so it cannot become a silent
     * exemption.
     *
     * @return list<string> child table names
     */
    private function siblingRelations(): array
    {
        // MEMOISED per test instance, and deliberately so. Every call boots the
        // whole shipped dial — `enforcingConnection()` migrates from scratch —
        // and this helper is read by four proofs. MEASURED: without the memo
        // `vendor/bin/phpunit --no-coverage
        // tests/Schema/Mirror/TfMessagesFamilyForeignKeyTest.php` took 4m09s at 9
        // tests; the derivation is a property of the SCHEMA, which does not move
        // inside one test method, so re-deriving it per proof buys nothing and
        // costs a full dial rebuild each time.
        if ($this->siblings !== null) {
            return $this->siblings;
        }

        $paths = [];
        foreach (\MeRezaRezaei\Teleframe\Ingest\UpdateIngestor::migrationPaths() as $path) {
            if (! is_dir($path)) {
                $paths[] = $path;

                continue;
            }

            foreach ((array) glob($path.'/*.php') as $file) {
                $paths[] = (string) $file;
            }
        }

        $unique = [];
        foreach ($paths as $path) {
            $unique[realpath($path) ?: $path] = $path;
        }
        $files = array_values($unique);
        sort($files);

        $derived = [];

        foreach (\MeRezaRezaei\Teleframe\Schema\Mirror\MirrorRelationPlan::fromConnection(
            $this->enforcingConnection(...$files)
        )->sibling() as $relation) {
            // Only the family's own siblings are this file's business.
            if ($relation['child'] !== self::FAMILY_ROOT && ! str_starts_with($relation['child'], self::FAMILY_PREFIX)) {
                continue;
            }

            $derived[] = $relation['child'];
        }

        sort($derived);

        $this->siblings = $derived;

        Assert::assertSame(
            self::SIBLING,
            $derived,
            'the sibling relations inside this family are '.json_encode($derived).' and SIBLING names '
            .json_encode(self::SIBLING).'. One side moved: either a new table in this family became a sibling — '
            .'which means this file will go on constraining a relation that refuses every row of that surface — '
            .'or a named sibling stopped being one. Re-derive both, never edit one to match the other.',
        );

        return $derived;
    }

    /**
     * Everything this family deliberately does NOT constrain: the migration's own
     * `REFUSED` list plus the SIBLING relations.
     *
     * Two different reasons, deliberately kept as two lists rather than merged
     * into one: `REFUSED` is a wrong LEG SET where a correct constraint already
     * ships, while a sibling is not a relation at all and now carries NO
     * constraint. Merging them would let PROOF 3 — which proves the refused
     * relation keeps the CORRECT constraint `2026_09_14_299999` ships — quietly
     * start asserting that about a relation that must carry none.
     *
     * @return list<string>
     */
    private function unconstrainedRelations(): array
    {
        return array_values(array_unique([...$this->refused(), ...$this->siblingRelations()]));
    }

    /**
     * @return list<string>
     */
    private function refused(): array
    {
        $refused = $this->migrationConstant('REFUSED');

        Assert::assertIsArray($refused, 'REFUSED must be an array of child => reason');
        foreach ($refused as $child => $reason) {
            Assert::assertIsString($reason, "REFUSED[{$child}] must carry the REASON, not just the name");
            Assert::assertNotSame('', trim($reason), "REFUSED[{$child}] has an empty reason");
        }

        return array_keys($refused);
    }

    // ------------------------------------------------------------------
    // fixtures
    // ------------------------------------------------------------------

    /** Plant the tenant every fixture below needs, so the tenant FK cannot be what refuses. */
    private function plantTenant(Connection $db, int $tenant): void
    {
        $db->table('telegram_accounts')->insert([
            'label' => 'probe',
            'type' => 'user',
            'user_id' => $tenant,
        ]);
    }

    /** @param array{peer_type:int, peer_id:int, id:int} $where */
    private function plantMessage(Connection $db, array $where): void
    {
        $db->table('tf_messages')->insert([
            'account_id' => self::TENANT,
            'id' => $where['id'],
            'peer_type' => $where['peer_type'],
            'peer_id' => $where['peer_id'],
            'constructor' => 'tf_messages',
        ]);
    }

    /**
     * A child row whose TENANT is real and whose parent message does not exist.
     *
     * @param  array{peer_type:int, peer_id:int, id:int}  $message
     */
    private function orphanRow(array $message): array
    {
        return [
            'account_id' => self::TENANT,
            'id' => $message['id'],
            'peer_type' => $message['peer_type'],
            'peer_id' => $message['peer_id'],
            'grouped_id' => 11,
        ];
    }

    // ------------------------------------------------------------------
    // PROOF 1 — the discovered family relations are the MEASURED ones
    // ------------------------------------------------------------------

    public function test_the_discovered_family_relations_are_the_measured_ones(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $discovered = $this->familyRelations($db);

        self::assertCount(
            self::MEASURED_RELATIONS,
            $discovered,
            'the number of single-parent relations inside the tf_messages family moved. That is expected when '
            .'the family gains or loses a table — but then this constant AND the docblock in '
            .self::MIGRATION.'_add_tf_messages_family_foreign_keys.php are both stale. Re-measure on the '
            .'target engine and bump BOTH. Never edit this count to reach a green run: it is the only thing '
            .'standing between a deleted mirror table and a silent green.',
        );

        self::assertCount(
            self::MEASURED_CONSTRAINED,
            array_diff(array_column($discovered, 'child'), $this->unconstrainedRelations()),
            'the number of relations this migration actually constrains is (discovered − refused − siblings). '
            .'If only one of those numbers moved, MEASURED_CONSTRAINED, the migration\'s REFUSED list and '
            .'SIBLING have drifted apart.',
        );
    }

    /**
     * PROOF 1b — THE SIBLING CARRIES NO CONSTRAINT TO THE ROOT, AND THE GATE SAYS
     * WHY OUT LOUD RATHER THAN SKIPPING IT IN SILENCE.
     *
     * The sibling skip in PROOF 2 is the only place in this file where a relation
     * is dropped from an obligation without a constraint existing to match it.
     * That is exactly the shape a reviewer cannot check by reading, so it gets
     * its own proof: the constraint is ABSENT on the executed schema, the
     * relation is REPORTED as refused with a reason naming the constructor, and
     * the sibling's own six children are untouched.
     *
     * Without this, deleting the sibling rule from `MirrorRelationPlan` would
     * make this file's PROOF 1b go red — where PROOF 2 alone would simply start
     * demanding the false constraint again, which is a louder but slower signal.
     */
    public function test_the_sibling_relation_carries_no_constraint_and_is_reported_as_refused(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $siblings = $this->siblingRelations();

        self::assertNotSame(
            [],
            $siblings,
            'no sibling was derived for this family, so the sibling skip in PROOF 2 is dead code — and a dead '
            .'skip is the same as no skip at all, except it also hides the fact that it is dead.',
        );

        foreach ($siblings as $child) {
            $toRoot = [];
            foreach ($db->getSchemaBuilder()->getForeignKeys($child) as $foreign) {
                if ((string) ($foreign['foreign_table'] ?? '') === self::FAMILY_ROOT) {
                    $toRoot[] = $foreign;
                }
            }

            self::assertSame(
                [],
                $toRoot,
                $child.' still declares '.count($toRoot).' foreign key(s) to '.self::FAMILY_ROOT.'. That '
                .'constraint refuses EVERY row of this surface, because no '.self::FAMILY_ROOT.' row is ever '
                .'written for the payload this table stores — so the mirror loses each one with inserted: 0. '
                .'2026_10_04_001200 drops it; if it is back, the derivation has re-invented the relation.',
            );
        }

        // And the refusal is PUBLISHED, not silent: a relation that merely
        // vanished cannot be checked by anyone reading this file.
        $reported = [];
        foreach (\MeRezaRezaei\Teleframe\Schema\Mirror\MirrorRelationPlan::fromConnection($db)->sibling() as $relation) {
            $reported[$relation['child']] = $relation['reason'];
        }

        foreach ($siblings as $child) {
            self::assertArrayHasKey(
                $child,
                $reported,
                $child.' is skipped by this gate but is not in MirrorRelationPlan::sibling(), so the skip is '
                .'unexplained and unfalsifiable. A refusal that hides itself is indistinguishable from a '
                .'broken derivation.',
            );
            self::assertStringContainsString(
                'messageService',
                $reported[$child],
                $child.' is refused without naming the constructor it was split out of, so the reason does '
                .'not say WHICH payload the constraint would have refused.',
            );
        }

        // The six TRUE children of the sibling keep theirs — the half a
        // "unlink the family" fix gets wrong, and the half a silent skip would
        // have let through.
        $service = $this->siblingRelations()[0] ?? self::FAMILY_ROOT;
        $children = [];

        foreach ($db->getSchemaBuilder()->getTableListing() as $listed) {
            $bare = str_contains($listed, '.') ? substr($listed, (int) strrpos($listed, '.') + 1) : $listed;
            if ($bare !== $service && ! str_starts_with($bare, $service.'_')) {
                continue;
            }
            $children[] = $bare;
        }

        $prefixed = array_values(array_filter($children, static fn (string $t): bool => $t !== $service));
        self::assertNotSame(
            [],
            $prefixed,
            'nothing descends from '.$service.' any more, so the assertions below are vacuous.',
        );

        $unconstrained = [];
        foreach ($prefixed as $table) {
            $targets = [];
            foreach ($db->getSchemaBuilder()->getForeignKeys($table) as $foreign) {
                $targets[] = (string) ($foreign['foreign_table'] ?? '');
            }

            if (! in_array($service, $targets, true)) {
                $unconstrained[] = $table;
            }
        }

        self::assertSame(
            [],
            $unconstrained,
            'these tables descend from '.$service.' but carry no constraint to it: '.implode(', ', $unconstrained)
            .'. Their parent row is written by the same payload, so their constraint was TRUE — unlinking the '
            .'sibling must not cost them theirs.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 2 — the DECLARATION, on every relation the migration claims
    // ------------------------------------------------------------------

    public function test_every_family_relation_declares_its_message_parent_foreign_key(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $refused = $this->unconstrainedRelations();
        $missing = [];
        $wrong = [];

        foreach ($this->familyRelations($db) as $relation) {
            if (in_array($relation['child'], $refused, true)) {
                continue;
            }

            $found = null;
            foreach ($db->getSchemaBuilder()->getForeignKeys($relation['child']) as $foreign) {
                if ((string) ($foreign['foreign_table'] ?? '') !== $relation['parent']) {
                    continue;
                }
                // The constraint this migration owns is the one whose legs are
                // the DERIVED legs. A table may carry several constraints to the
                // same parent legitimately (see the refusal proof below).
                if (array_map('strval', $foreign['foreign_columns'] ?? []) === $relation['columns']) {
                    $found = $foreign;
                    break;
                }
            }

            if ($found === null) {
                $missing[] = "{$relation['child']} -> {$relation['parent']} [".implode(',', $relation['columns']).']';

                continue;
            }

            $rule = strtoupper((string) ($found['on_delete'] ?? ''));
            if ($rule !== 'CASCADE') {
                $wrong[] = "{$relation['child']}: on_delete={$rule}";
            }
        }

        self::assertSame(
            [],
            $missing,
            'these family relations declare NO foreign key to their message parent, so a message child row '
            .'naming a message that does not exist is admitted. Delete '
            .self::MIGRATION.'_add_tf_messages_family_foreign_keys.php and this is the failure you get — which '
            .'is why it is asserted, not assumed.',
        );

        self::assertSame(
            [],
            $wrong,
            'the constraint exists but does not CASCADE. A re-delivered message with a shorter field vector '
            .'must take its old children with it, or the dropped ones are orphaned under a re-placed parent; '
            .'a child that outlives its parent fact is the state this whole family constraint exists to '
            .'prevent.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 3 — THE REFUSAL IS WIRED, not dead code
    // ------------------------------------------------------------------

    /**
     * The refused relation keeps the constraint `2026_09_14_299999` already ships,
     * and gains NO second one on the wrong leg.
     *
     * This is the proof that `REFUSED` is a FILTER in the migration rather than an
     * inventory in a docblock. The derived leg set for this relation is wrong —
     * it picks the button's own `position` (its index WITHIN the row) instead of
     * `parent_position` (the row it hangs under) — and the shipped correct
     * constraint references `(account_id,peer_type,peer_id,id,parent_position)`.
     *
     * So the refusal currently holds only by ACCIDENT: the migration's
     * de-duplication check finds the existing constraint's foreign columns equal
     * to the faulty derivation and skips. Drop `tf_reply_markup_buttons_row_fk`
     * and the wrong-column constraint is created on the next migrate. Asserting
     * the COUNT — one constraint to that parent, not two — is what makes the
     * filter load-bearing rather than coincidental.
     */
    public function test_the_refused_relation_carries_only_the_shipped_correct_constraint(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $refused = $this->refused();

        self::assertNotSame(
            [],
            $refused,
            'REFUSED is empty, so this proof is vacuous — and so is the migration\'s refusal claim. The docblock '
            .'names a relation it declines to constrain; an unnamed or vanished refusal means the wrong leg set '
            .'is live.',
        );

        foreach ($refused as $child) {
            // The parent of a refused relation is read from the family ancestry
            // rather than transcribed, so adding a second refusal does not
            // silently leave this assertion pointing at a table nobody constrains.
            $parent = $this->parentOf($db, $child);
            self::assertNotNull(
                $parent,
                "{$child} has no discoverable parent. If the ancestry rule changed, this proof is vacuous — "
                .'re-read bin/generate-relation-graph.php before trusting it.',
            );

            $toParent = array_values(array_filter(
                $db->getSchemaBuilder()->getForeignKeys($child),
                static fn (array $fk): bool => (string) ($fk['foreign_table'] ?? '') === $parent,
            ));

            self::assertCount(
                1,
                $toParent,
                "{$child} must carry EXACTLY ONE constraint to {$parent}. Two means the plan-derived leg set "
                .'(which ends in the button\'s own `position`) was written anyway, and that constraint refuses '
                .'an ordinary one-button-per-row keyboard, whose buttons all sit at position 0 under rows at '
                .'positions 0,1,2,… Zero means 2026_09_14_299999\'s correct constraint is gone and nothing '
                .'replaced it.',
            );

            // The leg that matters is the LOCAL one: the button joins to its row
            // through `parent_position`, and is referenced on `position`.
            $local = array_map('strval', $toParent[0]['columns'] ?? []);
            $foreign = array_map('strval', $toParent[0]['foreign_columns'] ?? []);

            self::assertSame(
                'parent_position',
                end($local),
                "{$child} must join to {$parent} through its `parent_position` — the row it hangs under — not "
                .'through its own `position`, which is only its index WITHIN that row. Legs: ['.implode(',', $local).'].',
            );
            self::assertSame(
                'position',
                end($foreign),
                "{$child}'s referenced leg must be {$parent}.`position`, the row's position. Legs: ["
                .implode(',', $foreign).'].',
            );
            self::assertSame(
                'CASCADE',
                strtoupper((string) ($toParent[0]['on_delete'] ?? '')),
                "{$child} → {$parent} must CASCADE.",
            );
        }
    }

    /** The single-parent ancestor of `$table` under the family's ancestry rule. */
    private function parentOf(Connection $db, string $table): ?string
    {
        foreach ($this->familyRelations($db) as $relation) {
            if ($relation['child'] === $table) {
                return $relation['parent'];
            }
        }

        return null;
    }

    // ------------------------------------------------------------------
    // PROOF 4 — CONTROL: the orphan is ADMITTED without this migration
    // ------------------------------------------------------------------

    public function test_the_orphan_is_admitted_without_this_migration(): void
    {
        $withheld = $this->migrations(withholdThisMigration: true);

        // The control withholds exactly one file, and it must be this migration.
        // If the filter matched nothing, this control is not a control and PROOF
        // 5 would be proving something other than what it claims.
        self::assertSame(
            1,
            count($this->migrations()) - count($withheld),
            'the control withholds exactly one file, and it must be '.self::MIGRATION
            .'. If the filter matched nothing, every refusal asserted elsewhere in this file is unattributable.',
        );

        // Deliberately the shipped default, NOT enforcingConnection(): the point
        // is what the dial WITHOUT this migration permits. The pragma state is
        // asserted so a future default cannot quietly change what this means.
        $permissive = ShippedStoreSchema::capsuleFor(...$withheld)
            ->getConnection();
        self::assertSame(0, $this->foreignKeysPragma($permissive));

        $this->plantTenant($permissive, self::TENANT);
        $permissive->table(self::CHILD)->insert($this->orphanRow(self::ABSENT_MESSAGE));

        self::assertSame(
            1,
            $permissive->table(self::CHILD)->count(),
            'the orphan must actually LAND without '.self::MIGRATION.'. Its tenant is real, so if this row '
            .'were refused the refusal would have come from the tenant constraint, and PROOF 5 would be '
            .'proving that instead of this migration.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 5 — the SAME orphan is REFUSED once the migration ships
    // ------------------------------------------------------------------

    public function test_the_orphan_is_refused(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $this->plantTenant($db, self::TENANT);

        try {
            $db->table(self::CHILD)->insert($this->orphanRow(self::ABSENT_MESSAGE));
            self::fail(
                'a '.self::CHILD.' row naming message ('.implode(',', self::ABSENT_MESSAGE).'), which does not '
                .'exist, was ACCEPTED on a connection whose PRAGMA foreign_keys was read back as 1. Its tenant '
                .'IS valid, so the tenant constraint cannot be what should have refused it — either this '
                ."migration's constraint is absent, or enforcement is not on, and both mean this suite is not "
                .'a gate.',
            );
        } catch (QueryException $e) {
            self::assertStringContainsString(
                'FOREIGN KEY constraint failed',
                $e->getMessage(),
                'the insert must be refused BY THE FOREIGN KEY. Any other QueryException means the fixture is '
                .'wrong — it is not evidence the constraint bit.',
            );
            self::assertStringContainsString(
                '23000',
                $e->getMessage(),
                'sqlite reports a foreign-key refusal as SQLSTATE 23000; assert it so a different refusal class '
                .'cannot satisfy this test.',
            );
        }

        self::assertSame(0, $db->table(self::CHILD)->count(), 'a refused insert must leave nothing behind.');
    }

    // ------------------------------------------------------------------
    // PROOF 6 — a real child row LANDS, its constrained columns as inserted,
    //           and deleting the parent CASCADES
    // ------------------------------------------------------------------

    public function test_a_real_child_row_lands_and_deleting_the_parent_cascades(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $this->plantTenant($db, self::TENANT);
        $this->plantMessage($db, self::MESSAGE);

        $row = $this->orphanRow(self::MESSAGE);
        $db->table(self::CHILD)->insert($row);

        $landed = $db->table(self::CHILD)->first();
        self::assertNotNull($landed, 'the legitimate row must LAND. A constraint that refuses real engine rows '
            .'is worse than no constraint: it is an outage, not a safety net.');

        // The constrained columns, read back as the values inserted.
        foreach (['account_id', 'id', 'peer_type', 'peer_id'] as $leg) {
            self::assertSame(
                $row[$leg],
                (int) $landed->{$leg},
                "the constrained column {$leg} must come back as the value inserted; a mismatch would mean "
                .'the constraint is over a different column set than the one this fixture built.',
            );
        }

        $db->table('tf_messages')
            ->where('account_id', self::TENANT)
            ->where('id', self::MESSAGE['id'])
            ->delete();

        self::assertSame(
            0,
            $db->table(self::CHILD)->count(),
            'deleting the parent message must take its children with it (ON DELETE CASCADE). A child row '
            .'outliving its parent fact is the state this whole family constraint exists to prevent.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 7 — every constraint NAME fits the engine's identifier budget
    // ------------------------------------------------------------------

    /**
     * MEASURED on MySQL 8.4.11: the limit is exactly 64 characters accepted, 65
     * refused with `ERROR 1059 ... Identifier name is too long`. sqlite has NO
     * identifier limit at all, so a name that is too long passes every sqlite
     * test and fails on the first real ALTER — which is exactly how the tenant
     * migration's first spelling shipped green and was refused.
     */
    public function test_every_family_constraint_name_fits_the_engine_identifier_budget(): void
    {
        $limit = 64;

        $db = $this->enforcingConnection(...$this->migrations());
        $suffix = $this->migrationConstant('CONSTRAINT_SUFFIX');
        Assert::assertIsString($suffix);

        $longest = '';
        foreach ($this->familyRelations($db) as $relation) {
            $name = $relation['child'].$suffix;
            self::assertLessThanOrEqual(
                $limit,
                strlen($name),
                "the constraint name for {$relation['child']} is ".strlen($name)." characters, over MySQL's "
                ."{$limit}-character identifier limit, which it refuses with ERROR 1059. Shorten "
                .'CONSTRAINT_SUFFIX in the migration — do not shorten it here, because the test and the '
                .'migration must agree on the same spelling.',
            );
            if (strlen($relation['child']) > strlen($longest)) {
                $longest = $relation['child'];
            }
        }

        self::assertNotSame('', $longest, 'discovery found no family relations, so nothing above is proven');
        self::assertLessThanOrEqual(
            $limit,
            strlen($longest.$suffix),
            "the longest family table ({$longest}) plus the suffix is over the budget.",
        );
    }

    // ------------------------------------------------------------------
    // PROOF 8 — signedness, which sqlite cannot express
    // ------------------------------------------------------------------

    /**
     * Every constrained leg must match its parent's column SIGNEDNESS and type.
     *
     * This is the MySQL 3780 failure (`Referencing column 'x' and referenced
     * column 'y' in foreign key constraint 'f' are incompatible`), and it is the
     * one class of defect sqlite can report as green no matter what, because
     * sqlite has no signedness to disagree about. Asserting it here — against
     * the executed DDL of BOTH tables — is the only local check available, and
     * it is why the MySQL lane exists.
     */
    public function test_every_constrained_leg_matches_its_parents_signedness(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $refused = $this->refused();
        $mismatch = [];
        $checked = 0;

        foreach ($this->familyRelations($db) as $relation) {
            if (in_array($relation['child'], $refused, true)) {
                continue;
            }

            $childColumns = $this->columnTypes($db, $relation['child']);
            $parentColumns = $this->columnTypes($db, $relation['parent']);

            foreach ($relation['columns'] as $leg) {
                $child = $childColumns[$leg] ?? null;
                $parent = $parentColumns[$leg] ?? null;

                if ($child === null || $parent === null) {
                    $mismatch[] = "{$relation['child']}.{$leg} or {$relation['parent']}.{$leg} does not exist";

                    continue;
                }
                $checked++;

                if ($child !== $parent) {
                    $mismatch[] = sprintf(
                        '%s.%s is [%s] but %s.%s is [%s]',
                        $relation['child'], $leg, $child,
                        $relation['parent'], $leg, $parent,
                    );
                }
            }
        }

        self::assertGreaterThan(
            0,
            $checked,
            'no leg was compared, so this proof is vacuous — discovery found no constrained relation.',
        );
        self::assertSame(
            [],
            $mismatch,
            'a constrained leg disagrees with its parent\'s column type or signedness. MySQL refuses the '
            .'ALTER with error 3780 and sqlite cannot raise it at all, so this is the one defect class a '
            .'sqlite-only suite reports as green forever.',
        );
    }

    /**
     * `column_name => "TYPE SIGNEDNESS"`, normalised so the comparison is about
     * the thing MySQL actually refuses on.
     *
     * @return array<string, string>
     */
    private function columnTypes(Connection $db, string $table): array
    {
        $types = [];
        foreach ($db->getSchemaBuilder()->getColumns($table) as $column) {
            $name = (string) $column['name'];
            $type = strtolower((string) ($column['type'] ?? ''));

            $unsigned = str_contains($type, 'unsigned');
            $base = trim(str_replace(
                ['unsigned', 'signed', 'bigint', 'mediumint', 'smallint', 'tinyint', 'integer', 'int', ''],
                ' ',
                $type,
            ));
            $types[$name] = trim($base).($unsigned ? '|UNSIGNED' : '|SIGNED');
        }

        return $types;
    }
}
