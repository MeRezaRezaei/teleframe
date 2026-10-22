<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorRelationPlan;

/**
 * Task 4.2, family 6: `tf_channel_participants` and `tf_channel_updates`.
 *
 * ## THE WORK LIST IS DISCOVERED, AND THE COUNT BELOW IS THE MEASUREMENT
 *
 * The RED list of `tests/Schema/Mirror/EveryFkableRelationHasAConstraintTest.php`
 * IS the work list; it is read from there and never from a table written here.
 * MEASURED on 2026-10-05, this tree, SQLite, the shipped dial:
 *
 *     timeout -k 10 900 vendor/bin/phpunit --no-coverage \
 *       --filter test_every_single_parent_relation_has_a_real_constraint \
 *       tests/Schema/Mirror/EveryFkableRelationHasAConstraintTest.php 2>&1 \
 *       | grep -oE "tf_[a-z0-9_]+\([^)]*\) → tf_[a-z0-9_]+" | sort -u \
 *       | grep -E "→ tf_channel_participants$|→ tf_channel_updates$"
 *
 * Nine relations, none refused, nine added:
 *
 *   tf_channel_participants_inviter(account_id,channel_id,user_id)   → tf_channel_participants
 *   tf_channel_participants_kicked_by(account_id,channel_id,user_id)→ tf_channel_participants
 *   tf_channel_participants_peer(account_id,channel_id,user_id)      → tf_channel_participants
 *   tf_channel_participants_promoted_by(account_id,channel_id,user_id)→ tf_channel_participants
 *   tf_channel_participants_rank(account_id,channel_id,user_id)      → tf_channel_participants
 *   tf_channel_participants_subscription_until_date(account_id,channel_id,user_id) → tf_channel_participants
 *   tf_channel_updates_dialog(account_id,channel_id,position)       → tf_channel_updates
 *   tf_channel_updates_pts(account_id,channel_id,position)          → tf_channel_updates
 *   tf_channel_updates_timeout(account_id,channel_id,position)      → tf_channel_updates
 *
 * 🔴 THIS MIGRATION STILL ADDS NINE — but only SIX SURVIVE, and that is not drift.
 *
 * This list was MEASURED at 2026-10-05 and it is still what this migration does:
 * `inviter`, `kicked_by` and `promoted_by` are discovered and constrained here, and
 * this file is NOT edited to remove them. On 2026-10-08 a LATER migration,
 * `2026_10_08_000100_fold_channel_participant_scalars_onto_the_participant_fact`,
 * retired those three tables: `channelParticipantSelf#a9478a1a … inviter_id:long`,
 * `channelParticipantAdmin#34c3bb53 … promoted_by:long` and
 * `channelParticipantBanned#d5f0ad91 … kicked_by:long` are each a bare `long` on ONE
 * constructor, so the wire nests nothing there and the child TABLE asserted an object
 * that does not exist (audit row C5, three times). Their values are COLUMNS on
 * `tf_channel_participants` now.
 *
 * So the final schema has SIX relations in this family, and every count that reads
 * the END STATE says six — `TfChannelParticipantsFamilyForeignKeyTest::MEASURED_RELATIONS`
 * and the 203 derived relations in `TfMessagesServiceIsNotAChildTest`. Both were
 * re-MEASURED from a failing assertion, not decremented to taste:
 *
 *     Failed asserting that actual size 6 matches expected size 9.
 *
 * The two numbers are both true at the moment they are true, and this list is the
 * STAGE one. It is left saying nine on purpose: editing a shipped migration to match
 * a later state would make it a lie about what it did on an install that had already
 * run it before the fold existed. The same reasoning, in the same shape, is why the
 * C5 fold left `2026_10_04_000840` untouched when it retired its own table.
 *
 * The prose after this list — "every one of its nine constraints", "at all nine
 * sites", "all nine as missing" — describes what THIS migration did when it ran, and
 * stays as written for the same reason.
 *
 * ## WHY THE FAMILY HAS TWO ROOTS, AND WHY A SINGLE-ROOT READING IS A SILENT LOSS
 *
 * `tf_messages` is a prefix family: one root, and every descendant named after
 * it. This family is not. Its two roots share their first segment
 * (`tf_channel_`) and then diverge — `participants` / `updates` — and nothing in
 * `updates.ChannelDifference` makes one a descendant of the other. So the family
 * is TWO prefixes, and `familyRelations()` accepts a relation whose parent is
 * either root.
 *
 * A copy of the `tf_messages` discovery here would find the participants half
 * and quietly miss the updates half. That failure has no symptom anywhere else:
 * three relations simply stay unconstrained and the anti-rot gate keeps counting
 * them missing, which reads as "family not landed yet" rather than as a broken
 * filter. `tests/Schema/Mirror/TfChannelParticipantsFamilyForeignKeyTest` asserts
 * each root contributes at least one relation so that loss is named.
 *
 * ## WHY THE LEGS ARE THE PARENT'S OWN KEY — MEASURED, NOT ASSUMED
 *
 * This is the risk the plan flagged for this family ("both key on `position`,
 * which is exactly where the leg derivation is most likely to be wrong"), and it
 * was checked against the EXECUTED DDL rather than against this docblock:
 *
 *     tf_channel_participants   PRIMARY KEY (account_id, channel_id, user_id)
 *     tf_channel_updates       PRIMARY KEY (account_id, channel_id, position)
 *
 * and every child's own PRIMARY KEY is the same three columns. So the derived leg
 * set is not merely a subset of the parent's key — it IS the parent's key, and
 * it is also the child's own key. Two consequences, both load-bearing:
 *
 *  - A FOREIGN KEY needs a UNIQUE index on the parent. The only one here is that
 *    primary key, so there is no MySQL 6125 risk (the failure a strict-superset
 *    child produced in the `tf_messages` family, where legs had to be narrowed).
 *  - NO PRIMARY KEY IS CHANGED. Plan rule 2 forbids widening or narrowing a key
 *    to make a constraint fit, because a widened parent with unwidened children
 *    produces children whose `id` resolves to no parent — a NEW inconsistency,
 *    worse than the uniform unconstrained state it replaced. This family
 *    satisfies every one of its nine constraints with the keys exactly as
 *    shipped, so nothing here needed to move.
 *
 * The same measurement is asserted in the gate
 * (`test_both_join_shapes_are_the_parents_own_primary_key`), so a future change
 * to a key makes that assertion red naming the table instead of surfacing as a
 * MySQL 6125 at migration time.
 *
 * ## THE JOIN COLUMNS ARE NOT WRITTEN IN THIS FILE — THEY ARE DERIVED
 *
 * `2026_09_14_200031_create_tf_channel_participants_tables.php` DOES ship two
 * helpers for these shapes — `participantLink()` over
 * `(account_id, channel_id, user_id)` and `channelUpdateLink()` over
 * `(account_id, channel_id, position)` — and it calls them at all nine sites.
 * They were the obvious thing to reuse and reusing them would have been wrong
 * twice over:
 *
 *  - Their column lists are HAND-WRITTEN. Plan rule 1 forbids a hand-written
 *    join column list precisely because it is a second copy of the schema that
 *    drifts; those two literals would be transcribed from a migration that is
 *    frozen in tag v1.3.x, and this file's discovery is derived from
 *    `MirrorRelationPlan` — the same class the gate's RED list is built from. The
 *    migration and the gate therefore cannot disagree about which relations exist
 *    or what their legs are: a disagreement is impossible by construction.
 *  - Their constraints are NOT IN THE EXECUTED SCHEMA. MEASURED after running the
 *    whole dial: `PRAGMA foreign_key_list` reports only the tenant constraint
 *    (`account_id → telegram_accounts.user_id`) on all eleven tables of this
 *    family. Something between `200031` and today rebuilds these tables and the
 *    composite legs do not survive it — which is why the anti-rot gate reports
 *    all nine as missing even though the create migration wrote them. So
 *    "they already exist" was never true, and calling the frozen helper would
 *    have produced a migration with nothing to do and a green suite.
 *
 * So this file establishes its own helper, `channelFamilyLink()`, which takes
 * the relation `MirrorRelationPlan` produced and derives the legs from it.
 * `grep -n 'foreign(' ` below this line finds exactly ONE call, inside the
 * helper.
 *
 * ## WHY ON DELETE CASCADE IS DERIVED, NOT CHOSEN PER TABLE
 *
 * One rule for the whole family, in one place, and it is the same rule
 * `2026_09_14_200031` applied when it wrote `participantLink()` and
 * `channelUpdateLink()`: every child in this family is a 1:1 FACET decomposed
 * from its parent row and has no life outside it.
 *
 * The six participant facets carry exactly one Telegram field each — `flags.2`
 * rank, `flags.0/1` subscription-until, and the `inviter_id` / `promoted_by` /
 * `kicked_by` / peer pair of `channelParticipantSelf|Admin|Banned|Left`. The three
 * channel-diff facets carry `flags.timeout`, `pts`, and the pinned dialog of
 * `channelDifferenceTooLong`. Each row is keyed by the parent row's own key, so
 * there is no orphan facet in the write path: `MirrorFactWriter::parentFirst()`
 * re-orders a decomposed batch so a facet never precedes its parent.
 *
 * That is also why the write path can absorb this migration for free. `parentFirst()`
 * already documents `tf_channel_participants` by name as "a self-referencing
 * table whose parent/child link to itself is shipped by the `participantLink()`
 * Blueprint helper" and re-orders around it — it was written for constraints that
 * this file now actually installs. Today it cannot see them, because they are not
 * in the schema, so a `[facet, parent]` batch is emitted in the caller's order
 * and the facet insert is what fails. Installing the constraints makes the
 * existing re-ordering live.
 *
 * ## ADDITIVE, AND NO SHIPPED MIGRATION IS EDITED
 *
 * `git cat-file -e v1.3.2:src/Laravel/Migrations/2026_09_14_200031_create_tf_channel_participants_tables.php`
 * succeeds: the create migration is in tag v1.3.2 and is therefore NOT editable
 * in place. An in-place edit would leave every install that already ran it
 * unconstrained forever, because those migrations are long since recorded as
 * applied. The add happens HERE, in a new file — the same convention as
 * `2026_10_04_000100`, `…_000200`, `…_000700`, `…_000800` and `…_000810`.
 *
 * ## WHY sqlite IS NOT THE GATE
 *
 * sqlite has no `ALTER TABLE … ADD CONSTRAINT`: Laravel's sqlite grammar
 * achieves the add by rebuilding the table, and sqlite cannot `dropForeign()` BY
 * NAME ("This database driver does not support dropping foreign keys by name"),
 * and it records neither constraint names nor column signedness — so `down()`
 * REFUSES on a driver that cannot express ownership, exactly as
 * `2026_10_04_000700` and `2026_10_04_000800` do, rather than either dropping
 * another migration's constraints or reporting a rollback that changed nothing.
 *
 * The gate that proves this migration works on the TARGET engine is the MySQL
 * lane (`tests/MySql/TfChannelParticipantsFamilyFkOnMysqlTest.php`), not this
 * file's own sqlite exercise. sqlite cannot raise 1452 (refusal), 3780
 * (signedness), 6125 (non-unique parent key), 1059 (name over 64 characters) or
 * 1170 (TEXT in a PRIMARY KEY) at all, and four of those five would have shipped
 * green here. This family's constraint names sit 12 characters under the 64-byte
 * ceiling on the longest table, so 1059 is a live risk rather than a theoretical
 * one, and the identifier budget is asserted in the gate with the measurement.
 *
 * ## THE COST, STATED RATHER THAN DISCOVERED
 *
 * Each `ALTER TABLE … ADD CONSTRAINT` is a separate, non-atomic rebuild on
 * InnoDB. `2026_10_04_000700` measured 265 of them at a 23.35 s floor on empty
 * tables; these nine are the same shape. The mirror is nearly empty right now,
 * which is the cheapest window that will ever exist; after the history fill this
 * becomes an online-DDL problem.
 */
return new class extends Migration
{
    /**
     * The family's ROOTS, plural. Descendants are discovered, never listed.
     *
     * Two entries, not one: see the docblock on why a single-root reading is a
     * silent loss for this family.
     *
     * @var list<string>
     */
    private const FAMILY_ROOTS = ['tf_channel_participants', 'tf_channel_updates'];

    /**
     * A descendant of a root keeps its name.
     *
     * @var list<string>
     */
    private const FAMILY_PREFIXES = ['tf_channel_participants_', 'tf_channel_updates_'];

    /**
     * The suffix every constraint this file creates carries.
     *
     * Length, MEASURED not chosen: MySQL caps an identifier at exactly 64
     * characters (65 is refused with `ERROR 1059 … Identifier name is too long`)
     * and sqlite caps it at none. The longest table name in THIS family is
     * `tf_channel_participants_subscription_until_date` at 47 characters, so the
     * budget left for a suffix is 17. `_ch_parent_fk` is 12, which leaves five
     * characters of headroom.
     *
     * That headroom is thin, and it is asserted in the gate rather than left to
     * this comment: a name that is too long passes every sqlite test and fails on
     * the first real ALTER — which is exactly how the tenant migration's first
     * spelling, `_account_tenant_fk` (18) against a 49-character table, shipped
     * green in the suite and was refused the first real migration.
     *
     * Uniqueness: it must NOT collide with `_acc_tenant_fk`
     * (`2026_10_04_000700`), or with the `_msg_parent_fk` of
     * `2026_10_04_000800`, or with the `fk_participant_link_*` /
     * `fk_channel_update_*` names in `2026_09_14_200031`. `down()` matches on
     * this exact suffix, and a shared one would make it unable to tell whose
     * constraints it held — which is the bug that made the tenant migration
     * remove 271 instead of 265.
     */
    private const CONSTRAINT_SUFFIX = '_ch_parent_fk';

    /**
     * Relations in this family that are REFUSED, with the reason.
     *
     * MEASURED: 0. Every one of the nine is constrained, and the mechanical
     * reason is in the docblock: both parents' PRIMARY KEY is also their
     * children's PRIMARY KEY, so every leg set is already the parent's key and
     * nothing needs narrowing, nothing needs widening, and no primary key moves.
     *
     * The constant is declared and asserted rather than omitted, because its
     * emptiness is the claim worth pinning: an unnamed or vanished refusal in
     * this family would mean a relation dropped out of the constrained set with
     * no recorded mechanical reason, which is the exact gap the anti-rot gate
     * exists to close.
     *
     * @var array<string, string>
     */
    private const REFUSED = [];

    /**
     * How many constraints this run added.
     *
     * Recorded, not returned, so a reader can see the loop skipped none. The
     * gate is what proves it — it asserts the declaration on every discovered
     * family relation from the executed schema rather than trusting this.
     */
    private int $added = 0;

    public function up(): void
    {
        if (! $this->familyIsPresent()) {
            return;
        }

        $added = 0;
        foreach ($this->familyRelations() as $relation) {
            if ($this->existingConstraint($relation) !== null) {
                continue;
            }

            Schema::table($relation['child'], function (Blueprint $blueprint) use ($relation): void {
                self::channelFamilyLink($blueprint, $relation);
            });
            $added++;
        }

        $this->added = $added;
    }

    public function down(): void
    {
        if (! $this->familyIsPresent()) {
            return;
        }

        foreach ($this->familyRelations() as $relation) {
            $declared = $this->existingConstraint($relation);

            if ($declared === null) {
                continue;
            }

            if (! is_string($declared['name'] ?? null) || $declared['name'] === '') {
                throw new RuntimeException(sprintf(
                    'this driver records no foreign key constraint names, so down() cannot tell which of %s\'s '
                    .'constraints belong to this migration (suffix %s) and which belong to '
                    .'2026_10_04_000700_add_tenant_foreign_keys_to_mirror_tables.php (_acc_tenant_fk) or to '
                    .'2026_09_14_299999_create_tf_foreign_keys.php (tf_channel_participants → tf_channels). '
                    .'Refusing rather than guessing: dropping them all would silently remove another '
                    .'migration\'s constraints, and dropping none would report a successful rollback that '
                    .'changed nothing. Roll back on MySQL, where constraints are named, or remove them by '
                    .'hand.',
                    $relation['child'],
                    self::CONSTRAINT_SUFFIX,
                ));
            }

            if ($declared['name'] !== $relation['child'].self::CONSTRAINT_SUFFIX) {
                continue;
            }

            Schema::table($relation['child'], function (Blueprint $blueprint) use ($declared): void {
                $blueprint->dropForeign($declared['name']);
            });
        }
    }

    /**
     * THE FAMILY'S ONE LINK HELPER.
     *
     * Every constraint this file creates goes through here, so the on-delete rule
     * and the leg list are derived from a single place rather than transcribed
     * into N call sites. The legs are NOT written down here either: they arrive on
     * `$relation`, produced by `MirrorRelationPlan` from the executed DDL, which
     * is also what the anti-rot gate's RED list is built from.
     *
     * It is deliberately NOT a call into `participantLink()` /
     * `channelUpdateLink()` in `2026_09_14_200031`, despite those existing and
     * matching these shapes: their leg lists are hand-written literals frozen in
     * tag v1.3.x, and their constraints are absent from the executed schema
     * anyway. Calling them would leave this file with nothing to do and a green
     * suite.
     *
     * @param  array{child:string, parent:string, columns:list<string>, reason:string}  $relation
     */
    private static function channelFamilyLink(Blueprint $table, array $relation): void
    {
        $table->foreign($relation['columns'], $relation['child'].self::CONSTRAINT_SUFFIX)
            ->references($relation['columns'])
            ->on($relation['parent'])
            ->cascadeOnDelete();
    }

    /**
     * Every relation in this family, DISCOVERED.
     *
     * Two independent filters, neither of them a transcribed list:
     *
     *  - the edges come from `MirrorRelationPlan`, i.e. from REFLECTION over the
     *    executed DDL plus the catalog's declared edges — the same derivation the
     *    gate's RED list is built from, so the two cannot disagree;
     *  - membership is "the parent is one of the two roots or one of their
     *    descendants", decided by NAME PREFIX against a table list read off the
     *    engine. Nothing here knows which file created what.
     *
     * @return list<array{child:string, parent:string, columns:list<string>, reason:string}>
     */
    private function familyRelations(): array
    {
        $connection = Schema::getConnection();
        $tables = $this->tableNames($connection);

        // A family member is a table whose name extends one of the roots' — the
        // same longest-prefix ancestry rule `bin/generate-relation-graph.php` and
        // `MirrorRelationPlan` document, applied one level down: the parent of a
        // family relation is inside the family, so the parent set is the roots
        // plus every descendant discovered above them.
        $family = [];
        foreach (self::FAMILY_ROOTS as $root) {
            $family[$root] = true;
        }
        foreach ($tables as $table) {
            foreach (self::FAMILY_PREFIXES as $familyPrefix) {
                if (str_starts_with($table, $familyPrefix)) {
                    $family[$table] = true;
                }
            }
        }

        $relations = [];
        foreach (MirrorRelationPlan::fromConnection($connection)->singleParent() as $relation) {
            if (! isset($family[$relation['parent']])) {
                continue;
            }

            // The REFUSED list is a FILTER, not an inventory. It is empty for this
            // family today, but the line stays so that adding a refusal to it later
            // cannot be a docblock edit alone — `down()` and the gate both read it.
            if (isset(self::REFUSED[$relation['child']])) {
                continue;
            }

            // A relation with no legs cannot be expressed as a FOREIGN KEY at all
            // (`foreign([], …)` is a runtime error), so it is never passed to the
            // helper. `MirrorRelationPlan` classifies those as `unconstrained`
            // rather than `singleParent`, so this is a belt-and-braces guard.
            if ($relation['columns'] === []) {
                continue;
            }

            $relations[] = $relation;
        }

        return $relations;
    }

    /**
     * Is either root on this connection?
     *
     * A dial that has not created this family yet is not an error — the curated
     * dial and the entity dial are applied by different consumers — so this
     * returns rather than throwing.
     */
    private function familyIsPresent(): bool
    {
        foreach (self::FAMILY_ROOTS as $root) {
            if (Schema::hasTable($root)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The bare table names on this connection, prefix- and schema-stripped.
     *
     * `getTableListing()` returns SCHEMA-QUALIFIED, PREFIXED names
     * ("main.PFX_tf_channel_participants") while `hasTable()`/`getForeignKeys()`
     * want the BARE name; both halves of that normalisation are measured, not
     * assumed — `tests/Store/ShippedStoreSchema` documents the same shape.
     *
     * @return list<string>
     */
    private function tableNames(Connection $connection): array
    {
        $prefix = $connection->getTablePrefix();
        $names = [];

        foreach ($connection->getSchemaBuilder()->getTableListing() as $listed) {
            $bare = str_contains($listed, '.')
                ? substr($listed, (int) strrpos($listed, '.') + 1)
                : $listed;

            if ($prefix !== '' && str_starts_with($bare, $prefix)) {
                $bare = substr($bare, strlen($prefix));
            }

            $names[] = $bare;
        }

        sort($names);

        return $names;
    }

    /**
     * The constraint this relation would create, if one is ALREADY declared.
     *
     * Read from the executed schema rather than from a list, so a re-run is a
     * no-op rather than a duplicate-constraint error, and so an install that
     * already carries the constraint keeps it.
     *
     * @param  array{child:string, parent:string, columns:list<string>, reason:string}  $relation
     * @return array<string, mixed>|null
     */
    private function existingConstraint(array $relation): ?array
    {
        foreach (Schema::getConnection()->getSchemaBuilder()->getForeignKeys($relation['child']) as $foreign) {
            if ((string) ($foreign['foreign_table'] ?? '') !== $relation['parent']) {
                continue;
            }

            $legs = array_map('strval', $foreign['foreign_columns'] ?? []);
            if ($legs !== $relation['columns']) {
                continue;
            }

            return $foreign;
        }

        return null;
    }
};
