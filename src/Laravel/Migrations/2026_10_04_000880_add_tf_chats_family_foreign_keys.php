<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorRelationPlan;

/**
 * Task 4.2, family 8: `tf_chats` and everything descended from it.
 *
 * ## WHY THIS FAMILY IS SMALL AND WHY IT IS STILL WORTH LANDING
 *
 * Five relations, all the same shape, and no `REFUSED` entry. That is the
 * cheapest possible family — and it is dispatched anyway for exactly the reason
 * the number is small: five hand-written constraints would drift silently. The
 * child list here is NOT written in this file. It is DISCOVERED from the
 * executed schema by the same rule the gate's own work list is built from, so a
 * sixth `tf_chats_*` table appearing in a later schema is constrained by the next
 * migrate rather than needing a human to remember it.
 *
 * MEASURED on 2026-10-04 (`/tmp/fk8-probe.php` style reflection over the
 * executed dial on sqlite):
 *
 *     tf_chats root, PK (account_id, id)
 *     tf_chats_access_hash             (account_id, id) -> tf_chats   legs [account_id, id]
 *     tf_chats_admin_rights            (account_id, id) -> tf_chats   legs [account_id, id]
 *     tf_chats_default_banned_rights    (account_id, id) -> tf_chats   legs [account_id, id]
 *     tf_chats_migrated_to              (account_id, id) -> tf_chats   legs [account_id, id]
 *     tf_chats_photo                    (account_id, id) -> tf_chats   legs [account_id, id]
 *
 * 5 relations, 0 polymorphic, 0 unconstrained, and none of the five already
 * carried a constraint to `tf_chats`. So 5 added.
 *
 * ## THE JOIN COLUMNS ARE NOT WRITTEN IN THIS FILE — THEY ARE DERIVED
 *
 * The same rule as `2026_10_04_000800`, and the same reason: a hand-written
 * join-column list is a SECOND COPY of the schema and it drifts. Every leg here
 * comes out of `MirrorRelationPlan::singleParent()`, which intersects the
 * PARENT'S primary key with the child's real columns. `grep -n 'foreign('` below
 * this line finds ONE call, inside `chatLink()`.
 *
 * The legs being the parent's own key is what makes the ALTER legal on the
 * target engine: a foreign key must reference a UNIQUE index, and referencing
 * the child's full key would be a non-unique column set, refused by MySQL 8.4
 * with error 6125. Here the child's key EQUALS the parent's key
 * `(account_id, id)`, so parent-key and child-key coincide and there is no
 * superset to trim — but the derivation still runs rather than being assumed,
 * because a future `tf_chats_*` table with a strict-superset key must be handled
 * by the same intersection.
 *
 * ## ON DELETE CASCADE IS DERIVED, NOT CHOSEN PER TABLE
 *
 * One rule for the whole family, in one place, for the same reason as the
 * messages family: a chat's child facts (`photo`, `admin_rights`,
 * `default_banned_rights`, `migrated_to`, `access_hash`) have no life outside
 * the chat row — they are decomposed from the parent by
 * `MirrorFactDecomposer::decomposeChildren()` and `SafeDelete` prunes a chat by
 * its own coordinates. A chat row that is re-delivered with a shorter field
 * vector must take its old children with it, or the dropped ones are orphaned
 * under a re-placed parent. `chatLink()` carries the rule once; five call sites
 * cannot disagree about it.
 *
 * ## ON `tf_chats_access_hash` — THE COLUMN NAME IS FINE, ITS VALUE IS NOT
 *
 * `tf_chats_access_hash.access_hash` is credential-SHAPED: on Telegram an
 * access hash is part of what authorises a peer lookup. This migration never
 * reads, writes, logs or migrates a VALUE — it declares a structural constraint
 * over `(account_id, id)` and never names the payload column in any DDL, name,
 * message or assertion. The gates below are equally careful: where a value is
 * required they use an obviously-fake integer, and nothing anywhere in this
 * family reads `.env`. The constraint does not widen any attack surface; it
 * NARROWS the set of rows that can exist, which is why it is safe to add.
 *
 * ## ADDITIVE, AND NO SHIPPED MIGRATION IS EDITED
 *
 * The create migration these tables live in is in tag v1.3.x and is therefore
 * NOT editable in place — an in-place edit would leave every install that has
 * already run it unconstrained forever. The add happens HERE, a new file, the
 * same convention as `2026_10_04_000100`, `…_000200`, `…_000700`, `…_000800`.
 *
 * ## WHY sqlite IS NOT THE GATE
 *
 * sqlite has no `ALTER TABLE … ADD CONSTRAINT`; Laravel's sqlite grammar
 * achieves the add by rebuilding the table. What sqlite cannot do is
 * `dropForeign()` BY NAME, and it records neither constraint names nor column
 * signedness — so `down()` REFUSES on a driver that cannot express ownership,
 * exactly as `2026_10_04_000700` and `2026_10_04_000800` do, rather than either
 * dropping another migration's constraints or reporting a rollback that changed
 * nothing.
 *
 * The gate that proves this migration works on the TARGET engine is the MySQL
 * lane (`tests/MySql/TfChatsFamilyFkOnMysqlTest`), not this file's own sqlite
 * exercise. sqlite cannot raise 3780 (signedness), 6125 (non-unique parent key),
 * 1059 (constraint name over 64 characters) or 1170 (TEXT in a PRIMARY KEY) at
 * all, and three of those four would have shipped green here.
 *
 * ## THE COST, STATED RATHER THAN DISCOVERED
 *
 * Each `ALTER TABLE … ADD CONSTRAINT` is a separate, non-atomic rebuild on
 * InnoDB. `2026_10_04_000700` measured 265 of them at a 23.35 s floor on empty
 * tables; these 5 are the same shape and cost a fraction of it. The mirror is
 * nearly empty right now, which is the cheapest window that will ever exist.
 */
return new class extends Migration
{
    /** The family ROOT. Descendants are discovered, never listed. */
    private const FAMILY_ROOT = 'tf_chats';

    /** The family PREFIX — a descendant of the root keeps its name. */
    private const FAMILY_PREFIX = 'tf_chats_';

    /**
     * The suffix every constraint this file creates carries.
     *
     * Length, MEASURED not chosen: MySQL caps an identifier at exactly 64
     * characters (probed one character at a time on 8.4.11; 65 is refused with
     * `ERROR 1059 … Identifier name is too long`) and sqlite caps it at none.
     * The longest table name in THIS family is `tf_chats_default_banned_rights`
     * at 31 characters, so the budget for a suffix is 33. `_chat_parent_fk` is
     * 14, and the longest name this file can produce is 45.
     *
     * A name that is too long passes every sqlite test and fails on the first
     * real ALTER — which is exactly how the first spelling of the tenant suffix,
     * `_account_tenant_fk` (18) against a 49-character table, shipped green in
     * the suite and was refused the first real migration.
     *
     * Uniqueness: it must NOT collide with `_acc_tenant_fk`
     * (`2026_10_04_000700`) or with `_msg_parent_fk` (`2026_10_04_000800`).
     */
    private const CONSTRAINT_SUFFIX = '_chat_parent_fk';

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
                self::chatLink($blueprint, $relation);
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
                    .'2026_10_04_000700_add_tenant_foreign_keys_to_mirror_tables.php (_acc_tenant_fk). '
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
     * Every constraint this file creates goes through here, so the on-delete
     * rule and the leg list are derived from a single place rather than
     * transcribed into N call sites. Mirrors `linkChild()` /
     * `participantLink()` / `channelUpdateLink()` / `differenceLink()` /
     * `factKey()` in the shipped create migrations and `messageLink()` in
     * `2026_10_04_000800`, which is why those shapes exist at all.
     *
     * @param  array{child:string, parent:string, columns:list<string>, reason:string}  $relation
     */
    private static function chatLink(Blueprint $table, array $relation): void
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
     *  - the edges come from `MirrorRelationPlan`, i.e. from REFLECTION over
     *    the executed DDL plus the catalog's declared edges — the same
     *    derivation the gate's work list is built from, so the two cannot
     *    disagree;
     *  - membership is "the parent is the family root or one of its
     *    descendants", decided by NAME PREFIX on a table list read off the
     *    engine. Nothing here knows which migration file created what, so a
     *    `tf_chats_*` table arriving in a different file is still found.
     *
     * @return list<array{child:string, parent:string, columns:list<string>, reason:string}>
     */
    private function familyRelations(): array
    {
        $connection = Schema::getConnection();
        $tables = $this->tableNames($connection);

        // A family member is a table whose name extends the root's — the same
        // longest-prefix ancestry rule `bin/generate-relation-graph.php` and
        // `MirrorRelationPlan` document, applied one level down: the parent of
        // a family relation is inside the family, so the parent set is the
        // family ROOT plus every descendant discovered above it.
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

            $relations[] = $relation;
        }

        return $relations;
    }

    /**
     * The bare table names on this connection, prefix- and schema-stripped.
     *
     * `getTableListing()` returns SCHEMA-QUALIFIED, PREFIXED names
     * ("main.PFX_tf_chats") while `hasTable()`/`getForeignKeys()` want the BARE
     * name; both halves of that normalisation are measured, not assumed —
     * `tests/Store/ShippedStoreSchema` documents the same shape.
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
