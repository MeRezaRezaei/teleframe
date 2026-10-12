<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorRelationPlan;

/**
 * Task 4.2, family 9: `tf_stars_subscriptions` and its five children.
 *
 * ## WHAT THIS FAMILY IS
 *
 * `tf_stars_subscriptions` is a StarsSubscription fact whose `id` is a TL
 * `string` (not a snowflake), and whose five children
 * (`_pricing`, `_chat_invite_hash`, `_title`, `_photo`, `_invoice_slug`) are
 * each single-ctor facts keyed `(account_id, id)` by
 * `2026_09_14_200040_create_tf_stars_business_tables.php::factKey()`.
 *
 * It is the same shape as the `tf_messages` family — the parent's key is
 * EXACTLY the child's key, so the derivation is trivial and every leg is a real
 * column pair on both sides.
 *
 * ## THE COUNT IS MEASURED, AND THE COMMAND SAYS SO
 *
 *     php tests/Support/probe_fk9.php   (family-9 probe, kept out of the dial)
 *
 * reported:
 *
 *     TOTAL singleParent relations with parent in family: 5
 *     REL tf_stars_subscriptions_chat_invite_hash -> tf_stars_subscriptions [account_id,id]
 *     REL tf_stars_subscriptions_invoice_slug    -> tf_stars_subscriptions [account_id,id]
 *     REL tf_stars_subscriptions_photo          -> tf_stars_subscriptions [account_id,id]
 *     REL tf_stars_subscriptions_pricing        -> tf_stars_subscriptions [account_id,id]
 *     REL tf_stars_subscriptions_title          -> tf_stars_subscriptions [account_id,id]
 *
 * Five discovered, five constrained, NONE refused — and that is a measured
 * result, not an omission. `REFUSED` below is EMPTY, which is why
 * `tests/Schema/Mirror/TfStarsSubscriptionsFamilyForeignKeyTest` does not assert
 * a refusal is non-empty: there is nothing to refuse. The test asserts instead
 * that the discovered set is exactly the measured set AND that no family table
 * carries a second constraint to the root, which is the assertion that would go
 * red if a refusal were ever added by accident.
 *
 * ## THE JOIN COLUMNS ARE NOT WRITTEN IN THIS FILE — THEY ARE DERIVED
 *
 * Every other place in this dial that wires a child to a parent routes it
 * through a shared Blueprint helper (`linkChild()`, `participantLink()`,
 * `channelUpdateLink()`, `differenceLink()`, `factKey()`; `tenantLink()` in
 * `2026_10_04_000700`; `messageLink()` in `2026_10_04_000800`) precisely so the
 * columns and the on-delete rule live in ONE place and hundreds of call sites
 * cannot drift apart.
 *
 * This file establishes the family's own helper (`starsSubscriptionLink()`)
 * and derives every leg list through it from `MirrorRelationPlan` — the SAME
 * class the gate derives its work list from. The migration and the gate
 * therefore cannot disagree about which relations exist or what their legs are:
 * that is impossible by construction rather than by review.
 *
 * Consequence, stated because it is the point: no hand-written table list, no
 * hand-written join-column list, no hand-written constraint count decides WHAT
 * gets constrained. `grep -n 'foreign(' ` below this line finds ONE call,
 * inside the helper.
 *
 * ## WHY THE LEGS ARE THE PARENT'S KEY
 *
 * A FOREIGN KEY must reference a UNIQUE index on the parent. Task 4.1's RED
 * step took the legs from the CHILD's key and measured 175 relations whose
 * referenced column set was not unique — refused by MySQL 8.4 with error 6125.
 * `MirrorRelationPlan` intersects the parent's primary key with the child's
 * real columns, so a child whose key is a strict SUPERSET of its parent's yields
 * the parent's key and no 6125.
 *
 * ## WHY THIS FAMILY'S KEY IS UNUSUAL AND WHY IT IS NOT NARROWED
 *
 * `tf_stars_subscriptions.id` is `string(255)` — a TL string, not an integer —
 * while `tf_messages.id` is a snowflake `bigInteger`. Nothing is narrowed or
 * widened to make a constraint fit: the parent's key is `(account_id, id)` and
 * every child carries those exact two columns with the exact two types
 * (`factKey()` writes `unsignedBigInteger('account_id')` + `string($key, 255)`,
 * and `2026_09_14_200040` writes the same pair by hand on the root). Widening or
 * narrowing either side would produce children whose `id` resolves to no parent
 * row — a NEW inconsistency, worse than the uniform state it replaced.
 *
 * The composite PRIMARY KEY of 8 bytes + `varchar(255)` at utf8mb4 is 1028
 * bytes, inside InnoDB's 3072-byte index budget, which is why the MySQL lane
 * rather than this file's sqlite exercise is the gate: 1170 and 1059 cannot be
 * raised by sqlite at all.
 *
 * ## ON DELETE CASCADE IS DERIVED, NOT CHOSEN PER TABLE
 *
 * One rule for the whole family, in one place. A StarsSubscription's children
 * have no life outside it — `MirrorFactDecomposer::decomposeChildren()` links
 * them from the parent row and the write path has no orphan fact — and
 * `SafeDelete` prunes a subscription by its own coordinates. So a re-delivered
 * subscription with a shorter field vector must take its old children with it,
 * or the dropped ones are orphaned under a re-placed parent. That is the same
 * derivation `2026_09_14_299999` applies to the three message constraints
 * already in the dial, extended to this family by derivation rather than
 * repeated five times.
 *
 * ## ADDITIVE, AND NO SHIPPED MIGRATION IS EDITED
 *
 * The create migration this family lives in (`2026_09_14_200040`) is in tag
 * v1.3.x and is therefore NOT editable in place — an in-place edit would leave
 * every install that already ran it unconstrained forever, because those
 * migrations are long since recorded as applied. The add happens HERE, in a new
 * file, the same convention as `2026_10_04_000100`, `…_000200`, `…_000700` and
 * `…_000800`.
 *
 * ## WHY sqlite IS NOT THE GATE
 *
 * sqlite has no `ALTER TABLE … ADD CONSTRAINT`: Laravel's sqlite grammar
 * achieves the add by rebuilding the table, and the rebuild PRESERVES a
 * composite FK the table already had (MEASURED on `tf_messages_entities` in
 * `2026_10_04_000800`). What sqlite cannot do is `dropForeign()` BY NAME
 * ("This database driver does not support dropping foreign keys by name"), and
 * it records neither constraint names nor column signedness — so `down()`
 * REFUSES on a driver that cannot express ownership, exactly as
 * `2026_10_04_000700` and `2026_10_04_000800` do, rather than either dropping
 * another migration's constraints or reporting a rollback that changed nothing.
 *
 * The gate that proves this migration works on the TARGET engine is the MySQL
 * lane (`tests/MySql/TfStarsSubscriptionsFamilyFkOnMysqlTest.php`). sqlite
 * cannot raise 3780 (signedness), 6125 (parent key not unique), 1059 (constraint
 * name over 64 characters) or 1170 (TEXT in a PRIMARY KEY) at all, and three
 * of those four would have shipped green here.
 */
return new class extends Migration
{
    /** The family ROOT. Descendants are discovered, never listed. */
    private const FAMILY_ROOT = 'tf_stars_subscriptions';

    /** The family PREFIX — a descendant of the root keeps its name. */
    private const FAMILY_PREFIX = 'tf_stars_subscriptions_';

    /**
     * The suffix every constraint this file creates carries.
     *
     * Length, MEASURED not chosen: MySQL caps an identifier at exactly 64
     * characters (probed one character at a time on 8.4.11; 65 is refused with
     * `ERROR 1059 … Identifier name is too long`) and sqlite caps it at none.
     * The longest table name in THIS family is
     * `tf_stars_subscriptions_chat_invite_hash` at 39 characters
     * (`strlen()` measured), so the budget for a suffix is 25.
     * `_stars_parent_fk` is 16, and the longest name this file can produce is 55.
     *
     * A name that is too long passes every sqlite test and fails on the first
     * real ALTER — which is exactly how the first spelling of the tenant suffix,
     * `_account_tenant_fk` (18) against a 49-character table, shipped green in
     * the suite and was refused the first real migration.
     *
     * Uniqueness: it must NOT collide with `_acc_tenant_fk`
     * (`2026_10_04_000700`) or with `_msg_parent_fk` (`2026_10_04_000800`).
     * MEASURED on the executed schema before this file was written: the only
     * constraint any `tf_stars_subscriptions*` table carried was the unnamed
     * tenant link to `telegram_accounts`, so this suffix owns nothing here.
     * A shared suffix across families is what made a `down()` unable to tell
     * whose constraints it held — it removed 271 instead of 265.
     */
    private const CONSTRAINT_SUFFIX = '_stars_parent_fk';

    /**
     * Relations in this family that are REFUSED, with the reason.
     *
     * MEASURED EMPTY on 2026-10-04: all five discovered relations have the
     * parent's key `(account_id, id)` as an exact prefix of their own, and no
     * `tf_stars_subscriptions*` table carried any pre-existing constraint to the
     * root. So unlike `2026_10_04_000800` there is nothing to decline.
     *
     * The constant is kept — empty — because a gate that only checks a list when
     * it is non-empty is a gate that cannot notice the list becoming wrong. The
     * sibling test asserts the list's SHAPE on every run and asserts that no
     * family table carries a SECOND constraint to the root, which is the check
     * that fires when a refusal is ever introduced here without its reason.
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
        if (! Schema::hasTable(self::FAMILY_ROOT)) {
            return;
        }

        $added = 0;
        foreach ($this->familyRelations() as $relation) {
            if ($this->existingConstraint($relation) !== null) {
                continue;
            }

            Schema::table($relation['child'], function (Blueprint $blueprint) use ($relation): void {
                self::starsSubscriptionLink($blueprint, $relation);
            });
            $added++;
        }

        $this->added = $added;
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::FAMILY_ROOT)) {
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
                    .'2026_10_04_000800_add_tf_messages_family_foreign_keys.php (_msg_parent_fk). Refusing '
                    .'rather than guessing: dropping them all would silently remove another migration\'s '
                    .'constraints, and dropping none would report a successful rollback that changed nothing. '
                    .'Roll back on MySQL, where constraints are named, or remove them by hand.',
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
     * Every constraint this file creates goes through here, so the on-delete
     * rule and the leg list are derived from a single place rather than
     * transcribed into N call sites. Mirrors `linkChild()` /
     * `participantLink()` / `channelUpdateLink()` / `differenceLink()` /
     * `tenantLink()` / `messageLink()` in the shipped migrations, which is why
     * those shapes exist at all.
     *
     * @param  array{child:string, parent:string, columns:list<string>, reason:string}  $relation
     */
    private static function starsSubscriptionLink(Blueprint $table, array $relation): void
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
     *    executed DDL plus the catalog's declared edges — the same derivation
     *    the gate's RED list is built from, so the two cannot disagree;
     *  - membership is "the parent is the family root or one of its
     *    descendants", decided by NAME PREFIX on a table list read off the
     *    engine.
     *
     * @return list<array{child:string, parent:string, columns:list<string>, reason:string}>
     */
    private function familyRelations(): array
    {
        $connection = Schema::getConnection();
        $tables = $this->tableNames($connection);

        // A family member is a table whose name extends the root's — the same
        // longest-prefix ancestry rule `bin/generate-relation-graph.php` and
        // `MirrorRelationPlan` document, applied one level down: the parent of a
        // family relation is inside the family, so the parent set is the family
        // ROOT plus every descendant discovered above it.
        $family = [self::FAMILY_ROOT => true];
        foreach ($tables as $table) {
            if (str_starts_with($table, self::FAMILY_PREFIX)) {
                $family[$table] = true;
            }
        }

        $relations = [];
        foreach (MirrorRelationPlan::fromConnection($connection)->singleParent() as $relation) {
            if (! isset($family[$relation['parent']])) {
                continue;
            }

            // The REFUSED list is a FILTER, not an inventory. Without this line
            // the constant would be dead code while the file docblock claims a
            // leg set is declined, and the refusal would hold only by ACCIDENT.
            // It is empty here (measured), so this line costs nothing and keeps
            // the filter load-bearing the moment a refusal is added.
            if (isset(self::REFUSED[$relation['child']])) {
                continue;
            }

            $relations[] = $relation;
        }

        return $relations;
    }

    /**
     * The bare table names on this connection, prefix- and schema-stripped.
     *
     * `getTableListing()` returns SCHEMA-QUALIFIED, PREFIXED names
     * ("main.PFX_tf_stars_subscriptions") while `hasTable()`/`getForeignKeys()`
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
