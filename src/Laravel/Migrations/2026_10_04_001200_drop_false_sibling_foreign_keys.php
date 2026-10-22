<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorRelationPlan;

/**
 * Drop the constraint that claims `tf_messages_service` DESCENDS FROM
 * `tf_messages`. It does not, and while it existed every `messageService` was
 * silently lost.
 *
 * ## THE PRODUCTION DEFECT, MEASURED IN THIS LANE (`SVC-FK`, SQLite, 2026-10-05)
 *
 * ```
 * timeout -k 5 900 vendor/bin/phpunit --no-coverage \
 *     tests/Ingest/ServiceMessageIsNotAChildOfMessagesTest.php
 * ```
 *
 * RED, on the tree as it stood — the constraint present, FK enforcement ON:
 *
 * | measurement                                              | value |
 * |----------------------------------------------------------|-------|
 * | a real `messageService#7a800e0a` payload written          | 0 rows |
 * | rows in `tf_messages`                                     | 0      |
 * | rows in `tf_messages_service`                             | 0      |
 * | `tf_messages_service_from_id` (its own decomposed child)  | 0      |
 * | the engine's own refusal                                  | `SQLSTATE[23000] … 19 FOREIGN KEY constraint failed` |
 *
 * Not an exception an operator sees: `MirrorFactWriter::write()` catches the
 * `QueryException` and files the FOREIGN KEY arm as an `fkClues` string rather
 * than raising, so the row is LOST and the batch reports `inserted: 0` — the
 * operator's own named failure signal, firing on ordinary traffic. The catch is
 * correct as a MECHANISM (it is the only enforcement available for the
 * polymorphic peer pair); the OUTCOME was wrong because the constraint it fired
 * on was never true.
 *
 * ## WHY THE CONSTRAINT WAS NEVER TRUE — THE WIRE, READ BY HAND
 *
 * `src/Schema/schema/sources/api.tl:125`:
 *
 * ```
 * messageService#7a800e0a flags:# out:flags.1?true mentioned:flags.4?true
 * media_unread:flags.5?true reactions_are_possible:flags.9?true
 * silent:flags.13?true post:flags.14?true legacy:flags.19?true id:int
 * from_id:flags.8?Peer peer_id:Peer saved_peer_id:flags.28?Peer
 * reply_to:flags.3?MessageReplyHeader date:int action:MessageAction
 * reactions:flags.20?MessageReactions ttl_period:flags.25?int = Message;
 * ```
 *
 * `messageService` is a MEMBER of the `Message` UNION — a peer of `message` and
 * `messageEmpty`, not a subtype of either. It carries its OWN `id` and its OWN
 * `peer_id` and references no other message. Its `reply_to:MessageReplyHeader`
 * holds a `reply_to_msg_id` integer plus a decomposed peer pair, not an in-band
 * reference to a `tf_messages` row — which is why the shipped
 * `tf_messages_service_reply_to` child is DECOMPOSED rather than FK'd, and why
 * `MirrorRelationPlan` reports 8 relations as polymorphic (MEASURED) instead of
 * constraining them.
 *
 * `MirrorCtorSplitter` says the same thing in code: its class docblock records
 * *"Spec §4 decision B: Message and MessageService are disjoint constructor
 * variants and must not share one table with nullable columns"*, and it SPLITS
 * `messageService` out of the merged `tf_messages` catalog entry into a
 * `tf_messages_service` entry of its own, removing it from `tf_messages['ctors']`.
 * A table produced by splitting one constructor out of another is a SIBLING of
 * it.
 *
 * And the write path agrees, in the one place that decides where a message goes:
 * `UpdateIngestor::writeMessage()`
 * (`src/Teleframe/Ingest/UpdateIngestor.php`) computes
 * `$table = self::MESSAGE_TABLES[$ctor] ?? 'tf_messages'` with
 * `MESSAGE_TABLES = ['messageService' => 'tf_messages_service']` — an EXCLUSIVE
 * route. No `tf_messages` row is written for this constructor, on any input, ever,
 * so the foreign key had no parent to resolve to from the first message onwards.
 *
 * The two tables are keyed IDENTICALLY — `(account_id, peer_type, peer_id, id)`
 * on both, MEASURED on the executed schema — which is why the constraint was
 * writable at all and why nothing about the DDL itself looked wrong.
 *
 * ## WHY THE CONSTRAINT IS DROPPED AND NOT SATISFIED
 *
 * The other way to make it hold is to write a `tf_messages` anchor row for every
 * `messageService`. That FABRICATES mirror data: such a row asserts that a
 * `message`/`messageEmpty` of that id exists in that peer, and none does — a
 * service message IS the message; there is no content message behind it. The
 * fabricated row would be indistinguishable from a real one to every downstream
 * reader, and `MessagesUnion` — which reads exactly this pair — would return a
 * phantom content message for every read receipt in the account's history.
 *
 * A dropped service message is MERELY ABSENT: visible, and repairable by a
 * re-run of `teleframe:backfill` over the affected pts range. A fabricated row is
 * a LIE the rest of the system then trusts, and no backfill can tell it from the
 * truth. The repo already holds that rule in two places — `MirrorFactWriter`'s
 * "never a fabricated key" docblock, and the gate
 * `test_a_null_pk_column_never_becomes_a_fabricated_key`
 * (`tests/Schema/Mirror/FabricatedMediaKeyTest.php`).
 *
 * So this migration removes a FALSEHOOD, not a safeguard. See
 * `.brain/decisions/D-022-service-message-sibling-not-child.md`.
 *
 * ## WHAT IS DROPPED, AND HOW LITTLE — the part a "just drop the FK" fix misses
 *
 * `tf_messages_service` carries TWO constraints today: the false one to
 * `tf_messages`, and the tenant one to `telegram_accounts` from
 * `2026_10_04_000700`. Only the first goes. MEASURED on this tree, SQLite, after
 * the drop:
 *
 * ```
 * BEFORE: 2 fks: ["tf_messages","telegram_accounts"]
 * DROP BY COLUMNS: ok
 * AFTER:  1 fks: ["telegram_accounts"]
 * COLUMNS: 13 -> account_id,id,peer_type,peer_id,constructor,date,out,mentioned,
 *                 media_unread,reactions_are_possible,silent,post,legacy
 * INDEXES: [{"primary":true,"cols":["account_id","peer_type","peer_id","id"]}]
 * ROW LANDED: 1
 * ORPHAN REFUSED: SQLSTATE[23000]: Integrity constraint violation: 19 FOREIGN
 * ```
 *
 * The 13 columns and the primary key are the shipped ones — the rebuild SQLite
 * performs preserves them — and the tenant FK still refuses an orphan, which is
 * the control half that stops "the row lands now" from meaning "nothing enforces
 * anything here any more".
 *
 * And the service surface's OWN six children keep their constraints. They are
 * genuine children: each is decomposed from a `tf_messages_service` row the SAME
 * payload writes, so their parent always resolves. Unlinking the family instead of
 * the one false edge would have removed six real constraints and left six silent
 * gaps.
 *
 * ## THE RELATION IS DISCOVERED, NOT NAMED
 *
 * The edges come from `MirrorRelationPlan::sibling()` — the population added by
 * the sibling fix in the same change, read off the executed DDL and the
 * committed catalog. `grep -n 'dropForeign('` below finds exactly one call site,
 * inside the one helper.
 *
 * That is the load-bearing part of the durability argument. The alternative —
 * hard-coding `tf_messages_service` — fixes the instance and leaves the CLASS
 * live: `2026_10_04_000800`'s own `REFUSED` list already declines one edge of this
 * shape, for a sibling table, calling it *"a DEFECT in `MirrorRelationPlan`'s leg
 * derivation"* — so over-collecting siblings is a known shape here and not a
 * one-off. Discovering the edge means the next sibling-shaped table is caught by
 * the same migration rather than by the next person who reads a constraint list.
 *
 * ## ADDITIVE, AND NO SHIPPED MIGRATION IS EDITED
 *
 * `git cat-file -e HEAD:src/Laravel/Migrations/2026_10_04_000800_add_tf_messages_family_foreign_keys.php`
 * → exit 0: `000800` is committed, and long since recorded as applied on every
 * install that has run it. Editing it in place would leave all of them carrying
 * the false constraint forever, because the file would never run again. The fix
 * lands HERE, in a new file — the same convention as `2026_10_04_000200`,
 * `…_000700` and `…_001100`.
 *
 * ## FRESH INSTALLS NEED NOTHING FROM THIS FILE
 *
 * With `MirrorRelationPlan`'s sibling rule in place, `000800`'s
 * `familyRelations()` no longer sees the edge at all — it filters
 * `singleParent()`, and the relation is no longer in it — so a fresh install never
 * creates the constraint. MEASURED by executing the whole 54-file dial on this
 * tree: `tf_messages_service` comes out carrying ONE foreign key, the tenant one.
 * This file therefore only does work on an install that already ran `000800`,
 * which is exactly the population that is broken, and it is idempotent for the
 * rest.
 *
 * ## WHY sqlite IS NOT THE GATE
 *
 * SQLite has no `ALTER TABLE … DROP CONSTRAINT`; Laravel's SQLite grammar achieves
 * it by rebuilding the table, which is why the measurement above reads the
 * column list and the primary key back afterwards rather than trusting it. SQLite
 * records no constraint NAMES, so `dropForeign()` by name refuses outright there
 * ("This database driver does not support dropping foreign keys by name") — hence
 * dropping by the leg list on a driver that reports no name and by the declared
 * name on one that does, the same split `2026_10_04_000200` documents.
 *
 * What SQLite cannot express at all — and therefore cannot be the gate for — is
 * InnoDB's `ERROR 1452` on an orphan, `ERROR 3780` on a signedness mismatch,
 * `ERROR 6125` on a non-unique parent key or `ERROR 1059` on a 64-character
 * identifier. The engine that accepts this migration is MySQL, and this lane did
 * not run it there: lane `MYSQL-ONE` holds sole MySQL ownership, and two MySQL
 * lanes on one server is how a previous wave was stopped. The MySQL test is
 * WRITTEN (`tests/MySql/DropFalseSiblingForeignKeyOnMysqlTest.php`) and not run.
 */
return new class extends Migration
{
    /**
     * How many constraints this run dropped.
     *
     * Recorded rather than returned, so a reader can see the loop found none to
     * do on a fresh install rather than wondering. The gate
     * (`tests/Schema/Mirror/TfMessagesServiceIsNotAChildTest.php`) is what proves
     * the drop happened on an install that had the constraint.
     */
    private int $dropped = 0;

    public function up(): void
    {
        foreach ($this->siblingRelations() as $relation) {
            $declared = $this->declaredConstraint($relation);

            if ($declared === null) {
                continue; // nothing to drop — a fresh install, or already repaired
            }

            Schema::table($relation['child'], function (Blueprint $blueprint) use ($declared): void {
                self::dropLink($blueprint, $declared);
            });

            $this->dropped++;
        }
    }

    /**
     * WHY THERE IS NO RESTORE, STATED RATHER THAN LEFT TO BE DISCOVERED
     *
     * `down()` re-adding the constraint would re-break exactly what this
     * migration repairs: every subsequent `messageService` would be refused and
     * lost with `inserted: 0`. So the rollback keeps the corrected schema and says
     * so, rather than reporting a rollback that changed nothing while pretending
     * otherwise.
     *
     * It is not silent, though. If the constraint is somehow STILL there after
     * `up()` ran — which would mean `up()` never reached this table — that is a
     * defect in this file, and it raises rather than rolling back quietly over a
     * schema it does not understand.
     */
    public function down(): void
    {
        $still = [];

        foreach ($this->siblingRelations() as $relation) {
            if ($this->declaredConstraint($relation) !== null) {
                $still[] = sprintf('%s → %s', $relation['child'], $relation['parent']);
            }
        }

        if ($still !== []) {
            throw new RuntimeException(
                'this migration drops the false parent constraint and deliberately does not restore it, '
                .'because restoring it returns the schema to a state where every messageService is refused '
                .'and lost with `inserted: 0`. These sibling relations still carry a constraint: '
                .implode('; ', $still).'. That means up() never reached them, which is a defect in this file '
                .'rather than a state to roll back. Remove the constraints by hand, or re-run up().',
            );
        }
    }

    /**
     * The sibling relations to unlink, read from the executed schema through the
     * derivation that refuses them.
     *
     * @return list<array{child:string, parent:string, columns:list<string>, reason:string}>
     */
    private function siblingRelations(): array
    {
        return MirrorRelationPlan::fromConnection(Schema::getConnection())->sibling();
    }

    /**
     * The constraint this relation actually has declared, or null when it has
     * none.
     *
     * Read from the executed schema rather than from a list or a guessed name, so
     * a re-run is a no-op instead of a duplicate-constraint error, and an install
     * that never had the constraint is left alone. The match is on the PARENT and
     * the exact LEG SET: `tf_messages_service` also carries the tenant FK to
     * `telegram_accounts`, and matching on the table alone would take that too.
     *
     * @param  array{child:string, parent:string, columns:list<string>, reason:string}  $relation
     * @return array<string, mixed>|null
     */
    private function declaredConstraint(array $relation): ?array
    {
        $child = $this->bareTableName($relation['child']);

        if (! Schema::hasTable($child)) {
            return null;
        }

        foreach (Schema::getConnection()->getSchemaBuilder()->getForeignKeys($child) as $foreign) {
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

    /**
     * Drop the constraint that is actually there.
     *
     * The name is read from the schema when the driver reports one (MySQL does)
     * and the leg list is used when it does not (SQLite, where `dropForeign()` by
     * name refuses outright). Guessing the name instead of reading it would
     * compile to `drop foreign key tf_messages_service_account_id_foreign` and
     * raise ERROR 3941 on any install whose constraint was named something else.
     *
     * @param  array<string, mixed>  $declared
     */
    private static function dropLink(Blueprint $blueprint, array $declared): void
    {
        $name = $declared['name'] ?? null;

        if (is_string($name) && $name !== '') {
            $blueprint->dropForeign($name);

            return;
        }

        $blueprint->dropForeign(array_map('strval', $declared['foreign_columns'] ?? []));
    }

    /**
     * The prefix-stripped table name, which is what `hasTable()` and
     * `getForeignKeys()` take.
     *
     * `MirrorRelationPlan` reports the bare name — its own `tableNames()` strips
     * both the schema qualifier and the connection prefix — while the facade here
     * applies the prefix again on some drivers. Stripping it here keeps the two
     * sides from disagreeing on a prefixed connection, and is a no-op on the
     * unprefixed one.
     */
    private function bareTableName(string $table): string
    {
        $bare = str_contains($table, '.')
            ? substr($table, (int) strrpos($table, '.') + 1)
            : $table;

        $prefix = Schema::getConnection()->getTablePrefix();

        if ($prefix !== '' && str_starts_with($bare, $prefix)) {
            $bare = substr($bare, strlen($prefix));
        }

        return $bare;
    }
};
