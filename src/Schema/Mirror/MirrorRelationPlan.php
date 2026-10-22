<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Mirror;

use Illuminate\Database\Connection;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\SchemaArtifacts;

/**
 * Which declared mirror relations can carry a REAL foreign key today.
 *
 * ## WHY IT REFLECTS INSTEAD OF READING THE CATALOG
 *
 * A catalog entry says a child descends from a parent. It does not say the
 * child's actual DDL has the columns to express that, nor that the parent's
 * key is unique enough to be referenced. Those are facts about the SCHEMA, and
 * `bin/generate-relation-graph.php` already exists to measure schema facts by
 * executing the dial and reflecting — its docblock explains at length why a
 * text search cannot see constraints emitted by shared Blueprint helpers, nor
 * primary keys, nor composite column sets.
 *
 * So this class does the same thing for relations: boot nothing, but READ the
 * live schema through the connection it is handed, and classify from the live
 * columns plus the catalog's declarations.
 *
 * ## WHY IT DOES NOT READ THE GRAPH'S `link` COLUMN
 *
 * `docs/schema/mirror-relation-graph.md` publishes `soft 0` on the tree that
 * carries the 265 tenant FKs, which reads like "no unenforced relation
 * remains". It is an ARTEFACT. `bin/generate-relation-graph.php`'s
 * `relationFor()` returns ONE relation per table and takes the FIRST foreign
 * key it finds — which, after Phase 0, is the tenant FK to
 * `telegram_accounts` on all 265 of them, so the `soft` branch is dead code
 * on this tree and 217 tables' real intra-mirror edges are never examined.
 *
 * MEASURED, 2026-10-04, this tree, executed shipped DDL on SQLite:
 *
 *   MIRROR nonTenantFK=48 tenantOnlyFK=217 noFK=1
 *
 * So this class applies the graph's own documented prefix + primary-key-cover
 * rule AFTER ignoring the tenant FK. The graph generator is not modified here;
 * it is a generated artefact with its own owner, and the correction to how its
 * `soft` number must be read lives in the gate's docblock.
 *
 * `tf_accounts` is the one mirror table with no constraint at all, and it is
 * NOT an obligation: it has no `account_id` column (it is the DC/registration
 * table: id, label, dc_id, port, kind, session_ref, …), so it structurally
 * cannot carry a tenant FK.
 *
 * ## WHY `MirrorCatalog` IS STILL A REQUIRED ARGUMENT
 *
 * The DDL knows every table; the catalog knows which tables are DECLARED and
 * which arrive as a nested source. `MirrorCatalog::nestedSources()` is a
 * REACHABILITY declaration — "this object only ever arrives inside that field"
 * — not a parent-child FK edge, so folding it into the FK population would be
 * a category error; it is surfaced on the relation instead. See
 * `.brain/decisions/D-017-relation-population-215-and-soft-zero-artefact.md`
 * (D-017.2), which measures the mismatch against the plan's sketch: the plan
 * iterates `nestedSources()` as a `parentCtor => sources` map of arrays with
 * `'tl'`/`'tf'` keys, and the real API is a flat `list<MirrorNestedSource>` of
 * objects — 32 of them, against 266 `tf_*` tables in the DDL.
 *
 * ## THE THREE POPULATIONS
 *
 *  - singleParent: one concrete parent, the child's columns exist, the parent's
 *    key is unique. A real FK works today. These are the obligations.
 *  - polymorphic: the legs carry a `{base}_type` + `{base}_id` sibling pair and
 *    do NOT carry the parent's own `id`, so they resolve across several tables
 *    and one FK cannot branch. A `tf_peers` registry is the structural fix.
 *  - unconstrained: the parent's key columns do not survive the check against
 *    the child's real columns. This is a KEYING gap, not a constraint gap.
 *
 * ## MEASURED — 2026-10-04, this tree, SQLite, executed shipped DDL
 *
 *   timeout -k 10 600 vendor/bin/phpunit --no-coverage \
 *       tests/Schema/Mirror/EveryFkableRelationHasAConstraintTest.php
 *
 * | measure | count |
 * |---|---:|
 * | `tf_*` mirror tables | 266 |
 * | declared intra-mirror edges derived from the executed DDL | 215 |
 * | `singleParent` — FK-able today | 207 |
 * | `polymorphic` — a `{base}_type` discriminator blocks one FK | 8 |
 * | `unconstrained` — a KEYING gap, not a constraint gap | 0 |
 * | single-parent relations carrying NO constraint | 165 |
 *
 * Task 4.2 lands those 165. `tf_peers` does not exist yet (`isset = n` on this
 * tree) — it is Task 4.3, and its absence is why the 8 polymorphic relations
 * are unenforced rather than un-derivable.
 *
 * ## THE RED-STEP COUNT WAS 175, AND IT WAS WRONG — WHY IT IS 165 NOW
 *
 * The RED step of Task 4.1 measured 175 before this class existed, deriving the
 * leg set from the CHILD's primary key. Ten of those relations have a child
 * primary key that is a strict SUPERSET of the parent's key:
 *
 *   tf_attach_menu_bots_icons      PK (account_id, bot_id, position)  parent PK (account_id, bot_id)
 *   tf_attach_menu_bots_peer_types PK (account_id, bot_id, position)  parent PK (account_id, bot_id)
 *   tf_bot_infos_commands          PK (account_id, id, position)      parent PK (account_id, id)
 *
 * A FOREIGN KEY over the child's full key would reference
 * `(account_id, bot_id, position)` in a parent keyed `(account_id, bot_id)` —
 * a NON-UNIQUE column set. MySQL rejects that with error 6125 ("parent key not
 * unique"), which is one of the four failures this plan's whole MySQL track
 * exists to catch. Had Task 4.2 been sized from 175, it would have written ten
 * constraints the target engine refuses.
 *
 * The legs are therefore the PARENT'S KEY — what a foreign key must reference —
 * intersected with the child's real columns, checked against the child rather
 * than assumed. The extra `position` is not lost: it is what makes the relation
 * 1:N, and it stays in the child's key. See D-017.6.
 *
 * ## A POSITIONED CHILD'S OWN INDEX IS NOT ITS PARENT'S KEY — D-017.7
 *
 * A plain intersection is wrong wherever a child carries BOTH a column named
 * like one of its parent's key columns AND a `parent_`-prefixed reference to
 * it. It cannot tell them apart, so the distinction has to come from the NAME,
 * and it matters: the unqualified column on such a child is its own index at
 * that role, and joining it would produce a constraint that REFUSES VALID DATA
 * rather than one that is merely missing.
 *
 * MEASURED, 2026-10-05, this tree at HEAD `cda787a9`, SQLite, shipped DDL:
 *
 * ```
 * nohup timeout -k 10 900 vendor/bin/phpunit --no-coverage \
 *     tests/Schema/Mirror/EveryFkableRelationHasAConstraintTest.php > /tmp/rel-before.log 2>&1 &
 * grep -oE "tf_[a-z0-9_]+\([^)]*\) → tf_[a-z0-9_]+" /tmp/rel-before.log | sort -u \
 *     > /tmp/relations-before.txt
 * wc -l /tmp/relations-before.txt   → 129
 * ```
 *
 * and the same run against the fixed source:
 *
 * ```
 * wc -l /tmp/relations-after.txt    → 128
 * diff /tmp/relations-before.txt /tmp/relations-after.txt
 * > tf_messages_reply_markup_rows_buttons(account_id,peer_type,peer_id,id,position) → tf_messages_reply_markup_rows
 * < (nothing)
 * ```
 *
 * | | count |
 * |---|---:|
 * | single-parent relations carrying NO constraint, before | 129 |
 * | the one false positive among them | 1 |
 * | single-parent relations carrying NO constraint, after | 128 |
 * | relations that APPEARED in the diff | **0** |
 *
 * The disappearing line is `tf_messages_reply_markup_rows_buttons(account_id,peer_type,peer_id,id,position)
 * → tf_messages_reply_markup_rows` — the legs are now
 * `account_id,peer_type,peer_id,id,parent_position`, which is exactly what
 * `tf_reply_markup_buttons_row_fk` has shipped since `2026_09_14`. The relation
 * itself is NOT removed: it stays a `singleParent` relation with those legs, and
 * it drops out of the gate's MISSING list because it is no longer missing. A
 * narrower population is a SMALLER obligation, so the count of DERIVED relations
 * is unchanged and only the count of genuinely-unconstrained ones falls.
 *
 * **Why the count moved by one and not by ten.** The D-017.6 superset case above
 * (`tf_attach_menu_bots_icons`, `tf_attach_menu_bots_peer_types`,
 * `tf_bot_infos_commands`) is the OPPOSITE shape: those parents' keys carry NO
 * `position` at all, so there is nothing to prefer and nothing changes. MEASURED
 * that `parent_`-prefixed columns are rare in the shipped dialect —
 * `grep -rnE "\('parent_[a-z_]+'" src/Laravel/Migrations/*.php` returns ONE
 * column, `parent_position` — so the preference's blast radius is bounded by
 * what the executed DDL declares, not by anything transcribed here. See
 * `legsOntoChild()` and D-017.7.
 *
 * **The 165 above is not the 129 here.** 165 was measured on the 2026-10-04 tree;
 * 129 is measured on this one, after eight FK families landed real constraints
 * underneath. Neither number was edited into the other — the earlier figure is
 * left standing with its own date, which is D-010's rule: a superseded
 * measurement is recorded where it was true, not quietly rewritten.
 */
final class MirrorRelationPlan
{
    /**
     * The prefix a child column carries when it names its PARENT's copy of a
     * column rather than its own.
     *
     * A naming convention, not a table list: it is read out of the executed DDL
     * like every other column name here, so a child that does not use the
     * convention is simply never affected by it. MEASURED on the shipped
     * dialect — `timeout -k 5 25 grep -rnE "\\('parent_[a-z_]+'" src/Laravel/Migrations/*.php` →
     * ONE column, `parent_position` on `tf_messages_reply_markup_rows_buttons`,
     * introduced by `2026_09_14_200010` and declared as a parent reference by
     * `TfMessageReplyMarkupRows::buttons()` ("`parent_position` is the column
     * that says which row a button hangs under … a button's own `position` is
     * only its index inside the row and must never be joined here").
     */
    private const PARENT_PREFIX = 'parent_';

    /**
     * @param  list<array{child:string,parent:string,columns:list<string>,reason:string}>  $singleParent
     * @param  list<array{child:string,parent:string,columns:list<string>,reason:string}>  $polymorphic
     * @param  list<array{child:string,parent:string,columns:list<string>,reason:string}>  $unconstrained
     * @param  list<array{child:string,parent:string,columns:list<string>,reason:string}>  $sibling
     */
    private function __construct(
        private readonly array $singleParent,
        private readonly array $polymorphic,
        private readonly array $unconstrained,
        private readonly array $sibling,
    ) {}

    /**
     * Reflect the live schema and classify every declared intra-mirror edge.
     *
     * @param  MirrorCatalog|null  $catalog  when null, the DDL is the whole
     *                                       population — useful for a caller
     *                                       that has no catalog to hand. The
     *                                       gate passes the real one. A null does
     *                                       NOT mean the sibling rule below is
     *                                       skipped: it is loaded from the
     *                                       committed artifact instead. See
     *                                       `resolvedCatalog()`.
     */
    public static function fromConnection(Connection $db, ?MirrorCatalog $catalog = null): self
    {
        $reflection = self::reflect($db);
        $catalog ??= self::resolvedCatalog();

        // The tables the CATALOG routes a top-level TL constructor to. Each of
        // these is a SUBJECT ROW: a fact in its own right, written straight from
        // a decoded payload node. `siblingAmong()` explains why two subject rows
        // can never be one another's parent.
        $subjectRows = [];
        foreach ($catalog->tableNames() as $tfName) {
            $entry = $catalog->table($tfName);

            if ($entry->ctors !== []) {
                $subjectRows[$tfName] = $entry->ctors;
            }
        }

        $singleParent = [];
        $polymorphic = [];
        $unconstrained = [];
        $sibling = [];

        foreach ($reflection['parents'] as $child => $parent) {
            if ($child === $parent) {
                continue;
            }

            $childColumns = $reflection['columns'][$child] ?? [];
            if ($childColumns === []) {
                continue;
            }

            // The catalog does NOT gate the population. MEASURED: the catalog
            // holds 37 tables against 266 `tf_*` tables in the shipped DDL, so
            // restricting edges to catalog-declared tables collapses the
            // population from 215 to 1 — a narrower population is a SMALLER
            // obligation, which is the failure mode this gate exists to prevent,
            // not a refinement of it. The catalog is the curated dial; the DDL
            // is where the other ~229 tables live and are reached. So the
            // catalog ANNOTATES below and REFUSES one class of edge; it never
            // filters the population. See `.brain/decisions/D-017-...md` D-017.5.
            $nested = self::nestedSourceDeclaring($catalog, $child);

            $parentColumns = $reflection['columns'][$parent] ?? [];
            $parentKey = $reflection['primaryKey'][$parent] ?? [];

            // A FOREIGN KEY must reference a key, so the parent's key columns
            // are the candidate legs — not every column the parent happens to
            // have. Falling back to the full intersection keeps a genuinely
            // joinable edge visible instead of dropping it on the floor.
            $legs = self::legsOntoChild($parentKey, $childColumns);
            $source = 'parent key';

            if ($legs === []) {
                $legs = self::legsOntoChild($parentColumns, $childColumns);
                $source = 'parent columns';
            }

            // Recorded when a `parent_`-prefixed sibling replaced a leg, so the
            // relation's own prose says which column carries the parent's value
            // instead of leaving it to be re-derived.
            $substituted = array_values(array_filter(
                $legs,
                static fn (string $leg): bool => str_starts_with($leg, self::PARENT_PREFIX),
            ));

            $via = $substituted === []
                ? ''
                : ' The parent\'s `'.implode('`, `', $substituted).'` is reached through the child\'s own '
                    .'`'.implode('`, `', array_map(
                        static fn (string $leg): string => substr($leg, strlen(self::PARENT_PREFIX)),
                        $substituted,
                    )).'` sibling, because a child that carries a `parent_`-prefixed reference to a '
                    .'column names its PARENT\'s copy of it there — its unqualified copy of the same name '
                    .'is its own index at that role, not the parent\'s. See D-017.7 in '
                    .'.brain/decisions/D-017-relation-population-215-and-soft-zero-artefact.md.';

            $siblingOf = self::siblingAmong($child, $parent, $subjectRows);

            if ($siblingOf !== null) {
                $sibling[] = [
                    'child' => $child,
                    'parent' => $parent,
                    'columns' => $legs,
                    'reason' => 'both ends are SUBJECT ROWS: the catalog routes the top-level TL constructor(s) '
                        .implode('`, `', $subjectRows[$child]).'` to `'.$child.'` and `'
                        .implode('`, `', $subjectRows[$parent]).'` to `'.$parent.'`, so each is written straight '
                        .'from its own decoded payload node and neither is decomposed from the other. A table '
                        .'produced by SPLITTING one constructor out of another is a SIBLING of it, not a child: '
                        .'`MirrorCtorSplitter` moves `'.$siblingOf.'` out of `'.$parent
                        .'[\'ctors\']` into a table of its own, and no part of the write path writes a `'
                        .$parent.'` row for a `'.$siblingOf.'` payload — `UpdateIngestor::writeMessage()` routes '
                        .'that constructor to `'.$child.'` and nowhere else. The two tables are keyed identically '
                        .'('.implode(',', $reflection['primaryKey'][$parent] ?? []).'), which is exactly why a '
                        .'NAME-PREFIX rule reads this edge as parent-to-child and why CONSTRAINING it refuses '
                        .'every row of this surface: the parent row does not exist to be resolved to. Refusing '
                        .'the edge is the only honest reading — fabricating the parent row instead would invent '
                        .'mirror data for a message that does not exist. See '
                        .'.brain/decisions/D-022-service-message-sibling-not-child.md.',
                ];

                continue;
            }

            if ($legs === []) {
                $unconstrained[] = [
                    'child' => $child,
                    'parent' => $parent,
                    'columns' => [],
                    'reason' => 'no subset of the parent\'s key columns exists on the child, so there is '
                        .'nothing for a foreign key to reference. This is a KEYING gap, not a constraint '
                        .'gap: add the join columns to the child first. Do NOT widen the parent key to '
                        .'make a constraint fit — a widened parent with unwidened children produces '
                        .'children whose `id` resolves to no parent row (verbatim-goals.md Cycle 44).',
                ];

                continue;
            }

            $discriminator = self::discriminatorAmong($legs);

            if ($discriminator !== null && ! in_array('id', $legs, true)) {
                $polymorphic[] = [
                    'child' => $child,
                    'parent' => $parent,
                    'columns' => $legs,
                    'reason' => 'the leg set carries a `'.$discriminator.'_type` discriminator with a '
                        .'`'.$discriminator.'_id` sibling and does not carry the parent\'s own `id`, so '
                        .'`'.$discriminator.'_id` resolves across `tf_users` / `tf_chats` / `tf_channels` '
                        .'on the `'.$discriminator.'_type` int, and a single FOREIGN KEY cannot branch on '
                        .'a type int. The structural fix is a `tf_peers` registry that both arms point at; '
                        .'until it ships this relation is unenforced and a join across it can return '
                        .'nothing. Derived over the '.$source.'.'.$via,
                ];

                continue;
            }

            $singleParent[] = [
                'child' => $child,
                'parent' => $parent,
                'columns' => $legs,
                'reason' => 'one concrete parent, and every leg column exists on the child: a real FOREIGN '
                    .'KEY applies today with no schema change. Derived over the '.$source.'.'.$via
                    .self::reachability($nested),
            ];
        }

        ksort($singleParent);
        ksort($polymorphic);
        ksort($unconstrained);
        ksort($sibling);

        return new self($singleParent, $polymorphic, $unconstrained, $sibling);
    }

    /** @return list<array{child:string,parent:string,columns:list<string>,reason:string}> */
    public function singleParent(): array
    {
        return $this->singleParent;
    }

    /** @return list<array{child:string,parent:string,columns:list<string>,reason:string}> */
    public function polymorphic(): array
    {
        return $this->polymorphic;
    }

    /** @return list<array{child:string,parent:string,columns:list<string>,reason:string}> */
    public function unconstrained(): array
    {
        return $this->unconstrained;
    }

    /**
     * Edges REFUSED because both ends are subject rows — SIBLINGS, not a
     * parent and a child.
     *
     * A refusal is not silence and not an absence: the edge is reported, with
     * the mechanism that refuses it, so a reader can check the claim instead of
     * taking it on trust. `MirrorRelationPlanPrefersParentPositionTest`'s sibling
     * in this directory asserts that a wrongly-refused edge stays reported rather
     * than vanishing, and this population is the same discipline applied here.
     *
     * @return list<array{child:string,parent:string,columns:list<string>,reason:string}>
     */
    public function sibling(): array
    {
        return $this->sibling;
    }

    /** @return array{singleParent:int, polymorphic:int, unconstrained:int, sibling:int} */
    public function counts(): array
    {
        return [
            'singleParent' => count($this->singleParent),
            'polymorphic' => count($this->polymorphic),
            'unconstrained' => count($this->unconstrained),
            'sibling' => count($this->sibling),
        ];
    }

    /**
     * Every table, its columns, its primary key, and the parent it descends
     * from — all read out of the live connection. Nothing here is transcribed.
     *
     * The parent rule is the one `bin/generate-relation-graph.php` documents for
     * its `soft` branch: the LONGEST surviving table-name prefix whose primary
     * key covers this table's. Longest-first, because the closest ancestor is
     * the one a constraint should name.
     *
     * @return array{columns: array<string,list<string>>, primaryKey: array<string,list<string>>, parents: array<string,string>}
     */
    private static function reflect(Connection $db): array
    {
        $tables = self::tableNames($db);
        $tableSet = array_flip($tables);

        $columns = [];
        $primaryKey = [];
        foreach ($tables as $table) {
            $columns[$table] = array_map(
                static fn (array $column): string => (string) $column['name'],
                $db->getSchemaBuilder()->getColumns($table),
            );

            // The key comes from the driver's own INDEX listing, not from the
            // column rows: `getColumns()` returns name/type/nullability and no
            // key position at all, so a primary key read from there would be
            // absent rather than merely imprecise. `getIndexes()` reports the
            // primary index WITH its columns already in key order — which is
            // the order a composite FOREIGN KEY must be declared in, and which
            // is NOT the order the columns sit in the table.
            $key = [];
            foreach ($db->getSchemaBuilder()->getIndexes($table) as $index) {
                if ($index['primary'] === true) {
                    $key = array_map('strval', $index['columns']);

                    break;
                }
            }

            $primaryKey[$table] = $key;
        }

        $parents = [];
        foreach ($tables as $table) {
            $parts = explode('_', $table);

            for ($i = count($parts) - 1; $i >= 1; $i--) {
                $candidate = implode('_', array_slice($parts, 0, $i));

                if (! isset($tableSet[$candidate])) {
                    continue;
                }

                if (array_diff($primaryKey[$candidate], $primaryKey[$table]) !== []) {
                    continue;
                }

                $parents[$table] = $candidate;

                break;
            }
        }

        return ['columns' => $columns, 'primaryKey' => $primaryKey, 'parents' => $parents];
    }

    /**
     * @return list<string>
     */
    private static function tableNames(Connection $db): array
    {
        $driver = $db->getDriverName();

        if ($driver === 'sqlite') {
            $rows = $db->select(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
            );
        } elseif ($driver === 'mysql') {
            $rows = $db->select(
                'SELECT table_name AS name FROM information_schema.tables '
                .'WHERE table_schema = DATABASE() ORDER BY table_name'
            );
        } elseif ($driver === 'pgsql') {
            $rows = $db->select(
                "SELECT tablename AS name FROM pg_catalog.pg_tables WHERE schemaname = 'public' ORDER BY tablename"
            );
        } else {
            throw new \RuntimeException(
                'MirrorRelationPlan reflects the live schema and has no table listing for the `'
                .$driver.'` driver. Add one here rather than falling back to a guessed table list — '
                .'a guessed list is the second copy of the schema that drifts.'
            );
        }

        return array_map(static fn ($row): string => (string) ((array) $row)['name'], $rows);
    }

    /**
     * Does the catalog declare `$table` reachable through a nested source?
     *
     * `nestedSources()` is a flat `list<MirrorNestedSource>` of OBJECTS, not a
     * `parentCtor => sources` map of arrays with `'tl'`/`'tf'` keys — which is
     * what the plan's sketch assumed and what does not exist. See
     * `.brain/decisions/D-017-...md` D-017.2.
     *
     * A nested source is a REACHABILITY declaration ("this object only ever
     * arrives inside that field"), not a parent-child FK edge, so it annotates
     * the relation's reason rather than gating or joining it.
     */
    private static function nestedSourceDeclaring(?MirrorCatalog $catalog, string $table): ?MirrorNestedSource
    {
        if ($catalog === null) {
            return null;
        }

        foreach ($catalog->nestedSources() as $source) {
            if ($source->tfName === $table) {
                return $source;
            }
        }

        return null;
    }

    /**
     * The constructor that makes `$child` a SIBLING of `$parent`, or null when
     * the edge is a real parent/child one.
     *
     * ## WHY TWO SUBJECT ROWS CAN NEVER BE PARENT AND CHILD
     *
     * A SUBJECT ROW is a table the catalog routes a top-level TL constructor to:
     * it is written straight from its own decoded payload node, by the write
     * path's ctor→table routing, and nothing is decomposed out of it to produce
     * another subject row. Two subject rows therefore have two independent
     * origins, and no payload ever produces both — so the edge between them has
     * no parent row to resolve to, on any input, ever.
     *
     * `messageService` and `message` are exactly that shape, and the wire says
     * so. `src/Schema/schema/sources/api.tl:125` declares
     * `messageService#7a800e0a … id:int from_id:flags.8?Peer peer_id:Peer …
     * = Message;` — a MEMBER of the `Message` union, a PEER of `message#7600b9d3`,
     * carrying its own `id` and its own `peer_id` and referencing no other
     * message. `MirrorCtorSplitter` encodes the same fact mechanically: it SPLITS
     * `messageService` out of the merged `tf_messages` catalog entry into a
     * `tf_messages_service` entry of its own and removes it from
     * `tf_messages['ctors']`, which is what makes the two tables peers rather
     * than one the other's child.
     *
     * ## WHY THE NAME-PREFIX RULE ALONE GETS THIS WRONG, AND WHY THAT IS FATAL
     *
     * `reflect()` finds a parent by the LONGEST surviving table-name prefix
     * whose primary key covers the table's. `tf_messages_service` keeps
     * `tf_messages`'s name, and the two tables carry the SAME primary key —
     * `(account_id, peer_type, peer_id, id)`, MEASURED on the executed schema,
     * which is why `2026_10_04_000800` could write a legal-looking constraint
     * over it. So the rule reads a sibling edge as a child edge, and constraining
     * it refuses EVERY row of the surface: the parent row does not exist to be
     * resolved to.
     *
     * That is not a missing constraint, it is a REFUSING one, and the symptom is
     * silent by construction: `MirrorFactWriter::write()` catches the
     * `QueryException` into an `fkClues` string rather than raising, so the row is
     * lost and the batch reports `inserted: 0` — the operator's own named failure
     * signal, firing on ordinary traffic.
     *
     * ## WHY NOT THE OTHER DIRECTION — THE FABRICATION
     *
     * The alternative is to make the constraint TRUE by writing a `tf_messages`
     * anchor row for every `messageService`. That fabricates mirror data: such a
     * row asserts that a `message`/`messageEmpty` of that id exists in that peer,
     * and none does — a service message IS the message, there is no content
     * message behind it. `MessagesUnion` reads exactly this pair, so every read
     * receipt in the account's history would read back as a phantom content
     * message. A dropped service message is merely ABSENT; a fabricated row is a
     * lie the rest of the system then trusts. See
     * `.brain/decisions/D-022-service-message-sibling-not-child.md`.
     *
     * ## MEASURED BLAST RADIUS, ONCE, NOT ASSUMED
     *
     * Every derived relation whose child AND parent are both subject rows, on
     * this tree at HEAD `838025af`, SQLite, executed shipped DDL, read through
     * this class's own `reflect()` and the catalog's own `tableNames()`:
     *
     * ```
     * vendor/bin/phpunit --no-coverage tests/Schema/Mirror/ZProbePlanTest.php
     * RELATIONS WHERE BOTH ENDS ARE CTOR-ROUTED (1):
     *   tf_messages_service(account_id,peer_type,peer_id,id) -> tf_messages
     * COUNTS: {"singleParent":207,"polymorphic":8,"unconstrained":0}
     * ```
     *
     * One of 207. The rule therefore removes exactly the false edge and leaves
     * the other 206 — including the service surface's own six children, which
     * resolve to `tf_messages_service` and not to a subject row — untouched. The
     * gate
     * `tests/Schema/Mirror/TfMessagesServiceIsNotAChildTest.php` re-derives that
     * figure on every run rather than trusting this paragraph.
     *
     * @param  array<string, list<string>>  $subjectRows  tf table → its top-level ctors
     * @return string|null the constructor that was split out of `$parent`, or null
     */
    private static function siblingAmong(string $child, string $parent, array $subjectRows): ?string
    {
        if (! isset($subjectRows[$child], $subjectRows[$parent])) {
            return null;
        }

        foreach ($subjectRows[$child] as $ctor) {
            if (in_array($ctor, $subjectRows[$parent], true)) {
                continue;
            }

            return $ctor;
        }

        return null;
    }

    /**
     * The committed catalog, loaded when the caller handed none.
     *
     * ## WHY A NULL CATALOG MUST NOT SILENTLY DISABLE THE SIBLING RULE
     *
     * Thirteen shipped FK-family migrations call
     * `MirrorRelationPlan::fromConnection($connection)` with no catalog. Under
     * the older contract a null meant "the DDL is the whole population", which
     * was true for the three populations and is no longer true for this one: the
     * sibling rule NEEDS the catalog's ctor→table routing, because the DDL alone
     * cannot tell a subject row from a decomposed child — the two tables here are
     * keyed identically, which is the whole reason the name-prefix rule
     * misreads them.
     *
     * So a null is resolved from the committed artifact instead of disabling the
     * rule. The alternative — honouring the null as "no sibling detection" —
     * would leave every one of those thirteen migrations one regeneration away
     * from writing the false constraint again, which is the defect this
     * population exists to end.
     *
     * MEASURED cost of doing it, so the price is on the record rather than
     * discovered as a migration timeout:
     *
     * ```
     * parse api_full.tl: 0.044s
     * load catalog: 0.004s
     * total: 0.048s
     * ```
     *
     * Loaded once per process, because a migration that runs thirteen family
     * derivations should pay it once rather than thirteen times.
     *
     * A missing artifact THROWS rather than degrading to "no sibling detection",
     * because degrading is the silent re-invention of the bug this closes. The
     * throw names both paths so the message is actionable on its own.
     */
    private static function resolvedCatalog(): MirrorCatalog
    {
        static $catalog = null;

        if ($catalog instanceof MirrorCatalog) {
            return $catalog;
        }

        $jsonPath = self::defaultCatalogPath();
        $tlPath = SchemaArtifacts::source('api_full.tl');

        if (! is_file($jsonPath) || ! is_file($tlPath)) {
            throw new \RuntimeException(
                'MirrorRelationPlan needs the committed catalog to tell a SIBLING subject row from a '
                .'decomposed child, and it is not there: '.$jsonPath.' or '.$tlPath.' is missing. Refusing '
                .'to derive without it, because deriving from the DDL alone re-invents the '
                .'tf_messages_service -> tf_messages relation this class exists to refuse — and a '
                .'constraint that refuses every row of a surface is worse than no constraint at all.',
            );
        }

        return $catalog = MirrorCatalog::load($jsonPath, TlParser::parseFile($tlPath));
    }

    /**
     * The parent's columns, resolved onto the child's OWN columns — preferring
     * a `parent_`-prefixed sibling over the same-named local column.
     *
     * ## WHY THE PREFERENCE EXISTS — A POSITIONED CHILD'S OWN INDEX IS NOT ITS PARENT'S KEY
     *
     * A plain intersection is wrong wherever a child carries BOTH a column named
     * like one of its parent's key columns AND a `parent_`-prefixed reference to
     * it. MEASURED on the shipped dialect, the only such pair is
     * `tf_messages_reply_markup_rows_buttons` under `tf_messages_reply_markup_rows`:
     *
     * | | columns |
     * |---|---|
     * | parent primary key | `account_id, peer_type, peer_id, id, position` |
     * | child carries | the same five, PLUS `parent_position` |
     * | intersection picked | `… , position` — the button's index WITHIN the row |
     * | the row it hangs under | `… , parent_position` |
     *
     * Both are `integer`, both are declared, and the intersection cannot tell
     * them apart — which is why the distinction has to come from the NAME. A
     * `parent_`-prefixed column on a child names its parent's copy of that
     * column by construction, so where a child carries one, it is the leg and
     * the unqualified column is the child's own index at that role.
     *
     * The cost of getting this backwards is not a missing constraint but a
     * constraint that REFUSES VALID DATA. An ordinary one-button-per-row
     * keyboard puts every button at `position` 0 under rows at positions
     * 0,1,2,… — so a FOREIGN KEY over `position` would reject button 1 of row 2
     * because row 2's own `position` is 1. `2026_09_14_299999` already ships the
     * correct constraint, `tf_reply_markup_buttons_row_fk`, over
     * `(account_id,peer_type,peer_id,id,parent_position) → (…,position)`; the
     * derived set disagreed with it, so the anti-rot gate reported a relation as
     * missing that was already correctly constrained. A false positive is worse
     * than silence, because it teaches you to ignore the gate.
     *
     * ## WHAT IT DELIBERATELY DOES NOT DO
     *
     * It does not ADD a leg. Substitution happens only where the plain
     * intersection already found the column, so this cannot invent an
     * obligation or change a relation's classification — it can only redirect a
     * leg the parent key already demanded. The unmeasured gap it leaves is a
     * child that carries `parent_X` and NOT `X`: there the parent key leg is
     * absent from the child either way, and that is the KEYING gap the
     * `unconstrained` population exists to report, not something a leg swap can
     * repair.
     *
     * MEASURED that it changes exactly one relation, on the tree at HEAD
     * `cda787a9` — see the class docblock for the before/after diff.
     *
     * @param  list<string>  $needles  the parent's key, or failing that its columns
     * @param  list<string>  $childColumns  the child's real columns
     * @return list<string>
     */
    private static function legsOntoChild(array $needles, array $childColumns): array
    {
        $set = array_flip($childColumns);
        $legs = [];

        foreach ($needles as $needle) {
            if (str_starts_with($needle, self::PARENT_PREFIX)) {
                continue;
            }

            // The parent's copy, where the child declares one, in the parent's
            // key ORDER — a composite FOREIGN KEY must be declared in that order.
            $leg = isset($set[self::PARENT_PREFIX.$needle]) ? self::PARENT_PREFIX.$needle : $needle;

            if (! isset($set[$leg]) || in_array($leg, $legs, true)) {
                continue;
            }

            $legs[] = $leg;
        }

        return $legs;
    }

    /**
     * The `{base}` of a `{base}_type` + `{base}_id` sibling pair among the legs,
     * or null when the leg set carries no discriminator.
     *
     * Longest base first, so `reply_to` wins over a shorter pair nested inside
     * its own name — the same ordering rule `polymorphicBaseOf()` in
     * `bin/generate-relation-graph.php` documents.
     *
     * @param  list<string>  $legs
     */
    private static function discriminatorAmong(array $legs): ?string
    {
        $set = array_flip($legs);

        $types = $legs;
        usort($types, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        foreach ($types as $column) {
            if (! str_ends_with($column, '_type')) {
                continue;
            }

            $base = substr($column, 0, -strlen('_type'));

            if ($base !== '' && isset($set[$base.'_id'])) {
                return $base;
            }
        }

        return null;
    }

    /**
     * The catalog's reachability note for a relation, as prose.
     *
     * Empty when the catalog says nothing about this table, which is most of
     * them — it holds 37 tables against 266 in the DDL. Kept because when it
     * DOES speak it says something the DDL cannot: that this child only ever
     * arrives inside a named parent field, which is a different fact from the
     * one its key columns prove.
     */
    private static function reachability(?MirrorNestedSource $nested): string
    {
        if ($nested === null) {
            return '';
        }

        return ' The catalog also declares this table reachable only as a nested source ('
            .$nested->describe().'), which is a reachability fact and not a keying one.';
    }

    /**
     * The committed catalog path, for a caller that has none to hand.
     */
    public static function defaultCatalogPath(): string
    {
        return SchemaArtifacts::path('mirror-catalog.json');
    }
}
