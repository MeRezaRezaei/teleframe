<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;

/**
 * LANE `SVC-FK` — `tf_messages_service` is a SIBLING of `tf_messages`, not a
 * child, and the dial had it FK'd as a child. Every `messageService` was
 * therefore SILENTLY DROPPED in production: `inserted: 0`, row gone.
 *
 * ## THE DEFECT, MEASURED BEFORE THE FIX IN THIS LANE (SQLite, FK enforcement ON)
 *
 * Command, this file, whole class, single process:
 *
 * ```
 * timeout -k 5 900 vendor/bin/phpunit --no-coverage \
 *     tests/Ingest/ServiceMessageIsNotAChildOfMessagesTest.php
 * ```
 *
 * RED, on the tree as it stood — the FK `tf_messages_service_msg_parent_fk`
 * present and `MirrorRelationPlan` still deriving the relation. The `inserted`
 * arm below reported **0** and the two row-count arms reported **0**, while the
 * control arm reported the raw engine's own refusal:
 *
 * ```
 * REFUSED -> QueryException: SQLSTATE[23000]: Integrity constraint violation:
 * 19 FOREIGN KEY constraint failed
 * tf_messages rows: 0
 * tf_messages_service rows: 0
 * ```
 *
 * So the whole message is gone — parent AND every decomposed child. The batch
 * reports `inserted: 0`, which the operator named as the failure signal.
 *
 * ## WHY IT WAS SILENT, MECHANICALLY
 *
 * `MirrorFactWriter::write()` catches `QueryException` and files the FOREIGN KEY
 * arm as an `fkClues` string rather than raising — deliberately, because it is
 * the only enforcement available for the polymorphic peer pair. The row is LOST;
 * nothing raises. That silence is correct as a MECHANISM and wrong as an
 * OUTCOME, and the outcome was caused here by a constraint that was never true.
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
 * `messageEmpty`, not a subtype of either. It has its own `id` and its own
 * `peer_id`; it references no other message. Its `reply_to:MessageReplyHeader`
 * carries a `reply_to_msg_id` INTEGER plus a decomposed peer pair, NOT an
 * in-band reference to a `tf_messages` row, which is exactly why the shipped
 * `tf_messages_service_reply_to` child is DECOMPOSED rather than FK'd and why
 * `MirrorRelationPlan` reports 8 relations as polymorphic (MEASURED) instead of
 * constraining them.
 *
 * `MirrorCtorSplitter` states the same thing in code: its docblock says *"Spec
 * §4 decision B: Message and MessageService are disjoint constructor variants
 * and must not share one table with nullable columns"*, and it SPLITS
 * `messageService` OUT of the merged `tf_messages` catalog entry into its own
 * `tf_messages_service` entry, removing it from `tf_messages['ctors']`. A table
 * produced by splitting a row OUT OF another is a sibling of it. Nothing about
 * it descends from the row it was split from.
 *
 * And the write path agrees, in the one place that decides where a message goes:
 * `UpdateIngestor::writeMessage()`
 * (`src/Teleframe/Ingest/UpdateIngestor.php`) computes
 * `$table = self::MESSAGE_TABLES[$ctor] ?? 'tf_messages'` where
 * `MESSAGE_TABLES = ['messageService' => 'tf_messages_service']` — a route that
 * is EXCLUSIVE, so a `tf_messages` row is never written for this constructor.
 * The FK had no parent to resolve to, by construction, on every message of the
 * surface.
 *
 * ## WHY THE SERVICE SURFACE'S OWN CHILDREN ARE UNAFFECTED — the discriminator
 *
 * `tf_messages_service_from_id`, `_saved_peer_id`, `_reply_to`, `_reactions`,
 * `_ttl_period` and `tf_messages_service_action` ARE genuine children: they are
 * decomposed from a `tf_messages_service` row that the SAME payload writes, so
 * their parent always resolves. `MirrorRelationPlan::reflect()` picks the
 * LONGEST surviving table-name prefix whose primary key covers the table's, so
 * those six already resolve to `tf_messages_service` and NOT to `tf_messages`.
 * A fix that unlinked the whole family would leave them unconstrained; that half
 * is a gate below, not a hope.
 *
 * ## THE DECISION — DROP THE CONSTRAINT, DO NOT FABRICATE THE PARENT
 *
 * Two fixes were available. Writing a `tf_messages` anchor row for every
 * `messageService` would make the FK hold, and it is the wrong direction because
 * it FABRICATES DATA: a `tf_messages` row asserts that a `message`/`messageEmpty`
 * of that id exists in that peer. No such message exists — a service message IS
 * the message; there is no content message behind it. The fabricated row would be
 * indistinguishable from a real one to every downstream reader, and
 * `MessagesUnion` — which reads exactly this pair — would return a phantom
 * content message for every read receipt in the account's history.
 *
 * A dropped service message is MERELY ABSENT, which the operator can see and a
 * backfill can repair. A fabricated row is a LIE the rest of the system then
 * trusts. The repo already holds that rule in two places: `MirrorFactWriter`'s
 * "never a fabricated key" docblock, and the gate
 * `test_a_null_pk_column_never_becomes_a_fabricated_key`
 * (`tests/Schema/Mirror/FabricatedMediaKeyTest.php`).
 *
 * So: **A — remove a constraint that was FALSE.** It was not a safeguard that
 * got too strict; it was a claim about the schema that the wire contradicts.
 */
final class ServiceMessageIsNotAChildOfMessagesTest extends IngestTestCase
{
    private const ACCOUNT = 7;

    private const SERVICE_ID = 4243;

    private const CHANNEL_ID = 1737473577;

    private const USER_ID = 501558149;

    private const SERVICE_TABLE = 'tf_messages_service';

    private const MESSAGES_TABLE = 'tf_messages';

    /** No `telegram_accounts` row carries these — the orphan is planted by ABSENCE. */
    private const ORPHAN_ACCOUNT = 999000001;

    private const ORPHAN_ACCOUNT_RAW = 999000002;

    /**
     * The only tenant this file writes under.
     *
     * `account_id` on every `tf_*` table is the caller's TELEGRAM user id
     * (`MirrorIngesterFactory` writes it straight in), and the tenant FK targets
     * `telegram_accounts.user_id`. Production always plants the parent first —
     * `VaultAddAccountCommand` writes `user_id` at account-add time — so a test
     * that ingests under an unplanted id describes a state the ingest surface
     * cannot reach, which is why declaring the id is a call rather than a
     * blanket auto-seed.
     *
     * @return list<int>
     */
    protected function ingestTenantIds(): array
    {
        return [self::ACCOUNT];
    }

    /**
     * A real `messageService#7a800e0a` — a read-marked service action in a
     * channel, carrying the three decomposed child kinds this table's catalog
     * declares and the writer must therefore reach.
     *
     * @return array<string, mixed>
     */
    private static function servicePayload(int $id = self::SERVICE_ID): array
    {
        return [
            '_' => 'messageService',
            'id' => $id,
            'from_id' => ['_' => 'peerUser', 'user_id' => self::USER_ID],
            'peer_id' => ['_' => 'peerChannel', 'channel_id' => self::CHANNEL_ID],
            'date' => 1724852400,
            'flags' => (1 << 1) | (1 << 3) | (1 << 19),
            'out' => true,
            'action' => [
                '_' => 'messagesReadContents',
                'flags' => 0,
                'peer' => ['_' => 'peerUser', 'user_id' => self::USER_ID],
            ],
            'reply_to' => [
                '_' => 'messageReplyHeader',
                'reply_to_msg_id' => 1186,
                'reply_to_peer_id' => ['_' => 'peerUser', 'user_id' => self::USER_ID],
            ],
            'ttl_period' => 604800,
        ];
    }

    /**
     * The row shape a single `messageService` write places, with `$accountId`
     * overridable so one helper serves both the landing arm and the orphan arm.
     *
     * @return array<string, mixed>
     */
    private static function serviceRow(int $accountId, int $id = self::SERVICE_ID): array
    {
        return [
            'account_id' => $accountId,
            'id' => $id,
            'peer_type' => 3,
            'peer_id' => self::CHANNEL_ID,
            'constructor' => 'messageService',
            'date' => 1724852400,
            'out' => true,
        ];
    }

    /**
     * The live table listing, with any driver schema qualifier stripped.
     *
     * SQLite reports attached schemas as `main.tf_messages`; the bare table name
     * is what a query builder and a foreign-key listing take, and comparing a
     * qualified name against a bare one silently finds nothing.
     *
     * @return list<string>
     */
    private function liveTables(): array
    {
        $tables = [];
        foreach (DB::connection()->getSchemaBuilder()->getTableListing() as $listed) {
            $tables[] = str_contains($listed, '.')
                ? substr($listed, strrpos($listed, '.') + 1)
                : $listed;
        }

        return $tables;
    }

    private function countRows(string $table, ?int $accountId = null): int
    {
        $query = DB::connection()->table($table);

        if ($accountId !== null) {
            $query->where('account_id', $accountId);
        }

        return $query->count();
    }

    /**
     * THE OPERATOR'S CRITERION IN ONE ASSERTION — the row lands, the FK bucket is
     * empty, and NOTHING landed anywhere else.
     *
     * `inserted >= 1` rather than `=== 1`: `inserted` counts STATEMENTS placed
     * (`MirrorFactWriter::write()`'s own note), so a batch that also places the
     * decomposed children reports more than one. The point is that the number is
     * not ZERO, and pinning the exact count would re-break the day the catalog
     * grows a child — which is a change in the payload, not a defect.
     *
     * The empty-bucket half is what makes this an INTEGRITY test rather than a
     * storage test: `fkClues` is where the caught refusal lives, so a non-empty
     * bucket here would say the row landed only because something else stopped
     * enforcing.
     *
     * @return array{inserted: int, fkClues: list<string>, unmappedClues: list<string>, valueClues: list<string>}
     */
    public function test_a_service_message_lands_in_its_own_table_with_an_empty_fk_bucket(): void
    {
        $report = (new MirrorFactWriter)->write(DB::connection(), [[
            'table' => self::SERVICE_TABLE,
            'row' => self::serviceRow(self::ACCOUNT),
        ]]);

        self::assertGreaterThanOrEqual(
            1,
            $report['inserted'],
            'a messageService wrote NO row: tf_messages_service is a SIBLING of tf_messages, not a child, '
            .'so the FK that pointed it at a parent row that is never written has to be gone. '
            .'"inserted: 0" with a message in hand is the operator\'s named failure signal.',
        );
        self::assertSame(
            [],
            $report['fkClues'],
            'the row landed but the writer caught a relational fault doing it — that bucket is where a '
            .'silently-dropped row hides, and an empty one is the condition worth asserting.',
        );

        self::assertSame(
            1,
            $this->countRows(self::SERVICE_TABLE, self::ACCOUNT),
            'the service row must actually be HELD, not merely reported as placed',
        );

        // The fabrication half. A `tf_messages` row here would be a claim that a
        // content message of that id exists in that peer. None does — see the
        // class docblock.
        self::assertSame(
            0,
            $this->countRows(self::MESSAGES_TABLE, self::ACCOUNT),
            'a tf_messages row was written for a messageService. That is a FABRICATED parent, invented to '
            .'satisfy a constraint that was never true. MessagesUnion reads exactly this pair, so the '
            .'phantom would read back as a real content message.',
        );
    }

    /**
     * THE FULL INGEST PATH — the constructor is not merely placeable by the
     * writer, it is STORED by `UpdateIngestor::ingest()`, and it reaches its own
     * decomposed children.
     *
     * The child assertion is the load-bearing half: `writeMessage()` writes the
     * parent with a bare `updateOrInsert`, then hands the child rows to
     * `MirrorFactWriter`. A parent that is refused loses its children with it, so
     * an empty child table is the shape the defect had.
     */
    public function test_the_ingest_path_stores_the_service_message_and_its_children(): void
    {
        $root = (new UpdateIngestor)->ingest(self::servicePayload(), self::ACCOUNT);

        self::assertNotNull(
            $root,
            'the ingest returned nothing for a messageService — the row was refused and swallowed',
        );
        self::assertSame('messageService', (string) $root->getAttribute('constructor'));
        self::assertSame(self::SERVICE_ID, (int) $root->getAttribute('id'));

        self::assertSame(
            1,
            $this->countRows(self::SERVICE_TABLE, self::ACCOUNT),
            'the service row did not land',
        );
        self::assertSame(
            1,
            $this->countRows(self::SERVICE_TABLE.'_from_id', self::ACCOUNT),
            'tf_messages_service_from_id is empty — the parent was refused, so the decomposed child had '
            .'no parent to resolve to and went with it',
        );
        self::assertSame(
            0,
            $this->countRows(self::MESSAGES_TABLE, self::ACCOUNT),
            'a tf_messages row exists for a service message — the fabrication the class docblock rules out',
        );
    }

    /**
     * IDEMPOTENCE — the SAME payload delivered repeatedly must hold ONE row.
     *
     * "It lands the second time" alone is satisfied by an append-only writer, and
     * "it holds one row" alone by a writer that simply refuses the second, so
     * this delivers THREE times: the first must land and the next two must change
     * nothing. Re-delivery is not synthetic — a re-issued pts range and a re-run
     * backfill both re-deliver a message the mirror may already hold.
     */
    public function test_redelivering_the_same_service_message_holds_exactly_one_row(): void
    {
        $ingestor = new UpdateIngestor;

        $ingestor->ingest(self::servicePayload(), self::ACCOUNT);

        self::assertSame(
            1,
            $this->countRows(self::SERVICE_TABLE, self::ACCOUNT),
            'precondition: the first delivery landed exactly one row',
        );

        $ingestor->ingest(self::servicePayload(), self::ACCOUNT);
        $ingestor->ingest(self::servicePayload(), self::ACCOUNT);

        self::assertSame(
            1,
            $this->countRows(self::SERVICE_TABLE, self::ACCOUNT),
            'a re-delivered messageService duplicated its row — the write key lost the peer halves, or the '
            .'second delivery raised and the third is asserting on a table the second emptied',
        );
        self::assertSame(
            1,
            $this->countRows(self::SERVICE_TABLE.'_from_id', self::ACCOUNT),
            'the re-delivery duplicated a decomposed CHILD row while the parent stayed at one — the parent '
            .'upsert is keyed, so a child that is not would still be a silent double-count',
        );
    }

    /**
     * THE CONTROL HALF — the tenant FK is still ENFORCED on this very path, in
     * BOTH of its two forms.
     *
     * Without this, "it lands now" is unfalsifiable: dropping the one false
     * constraint and dropping every constraint at once produce the same green.
     * So the same writer, the same table, the same row shape, under an
     * `account_id` with no `telegram_accounts` parent, must still be REFUSED —
     * and the refusal must arrive as a CLUE rather than as a raise, because that
     * catch is the only enforcement available for the polymorphic peer pair.
     *
     * Two forms, because the two surfaces are different code: the writer's catch
     * (which must still catch, or a tenant fault becomes an un-ingestible
     * update) and the engine itself (which must still refuse, or nothing
     * enforces anything on this table).
     */
    public function test_the_tenant_foreign_key_is_still_enforced_on_this_same_path(): void
    {
        $db = DB::connection();

        $report = (new MirrorFactWriter)->write($db, [[
            'table' => self::SERVICE_TABLE,
            'row' => self::serviceRow(self::ORPHAN_ACCOUNT),
        ]]);

        self::assertSame(
            0,
            $report['inserted'],
            'an orphaned tf_messages_service row was PLACED. The fix removed ONE false constraint; if it '
            .'removed the tenant constraint too, every row on this table would now be unconstrained and '
            .'nothing downstream would notice.',
        );
        self::assertCount(
            1,
            $report['fkClues'],
            'the orphan must be refused AND the refusal must still be CAUGHT into fkClues. The catch is '
            .'the only enforcement available for the polymorphic peer pair; removing it would trade a '
            .'silent drop for an un-ingestible update.',
        );
        self::assertSame(
            0,
            $this->countRows(self::SERVICE_TABLE, self::ORPHAN_ACCOUNT),
            'the refused row is HELD — a clue that fired on a row which landed anyway is not enforcement',
        );

        // And the engine, with nothing catching.
        $raised = null;

        try {
            $db->table(self::SERVICE_TABLE)->insert(self::serviceRow(self::ORPHAN_ACCOUNT_RAW));
        } catch (QueryException $e) {
            $raised = $e;
        }

        self::assertInstanceOf(
            QueryException::class,
            $raised,
            'the engine ACCEPTED an orphaned tf_messages_service row on a connection whose PRAGMA '
            .'foreign_keys is asserted ON by this harness. See tests/Ingest/IngestFkEnforcementIsOnTest.php.',
        );
        self::assertStringContainsStringIgnoringCase(
            'foreign key',
            $raised->getMessage(),
            'the refusal came from something other than the tenant FK',
        );
    }

    /**
     * THE SIBLING'S OWN CHILDREN ARE STILL CONSTRAINED.
     *
     * This is the half a "just delete the FK" fix gets wrong. Six tables
     * genuinely descend from `tf_messages_service`, and their constraint is a
     * real one: the parent row is written by the same payload, so it always
     * resolves. If the fix had unlinked the whole family, these would lose
     * enforcement and this arm would go red.
     *
     * Derived from the live schema, never transcribed: the children are the
     * tables whose name extends `tf_messages_service`, read off the engine's own
     * table listing.
     */
    public function test_the_service_surfaces_own_children_keep_their_constraint(): void
    {
        $children = [];
        foreach ($this->liveTables() as $table) {
            if (str_starts_with($table, self::SERVICE_TABLE.'_')) {
                $children[] = $table;
            }
        }

        self::assertNotSame(
            [],
            $children,
            'no table descends from tf_messages_service any more — either the DDL changed underneath this '
            .'gate or the name-prefix discovery it uses stopped working. An empty set makes every '
            .'assertion below vacuous.',
        );

        foreach ($children as $child) {
            $targets = [];
            foreach (DB::connection()->getSchemaBuilder()->getForeignKeys($child) as $foreignKey) {
                $targets[] = (string) ($foreignKey['foreign_table'] ?? '');
            }

            self::assertContains(
                self::SERVICE_TABLE,
                $targets,
                $child.' lost its foreign key to '.self::SERVICE_TABLE.'. That constraint was TRUE — the '
                .'service row is written by the same payload — so removing it is not the fix, it is a '
                .'second silent gap. This child\'s FK targets were: '
                .($targets === [] ? '<none>' : implode(', ', $targets)).'.',
            );
        }
    }

    /**
     * THE FALSE LINK IS GONE, read off the engine's own FK listing — so this
     * cannot be satisfied by a comment, a constant, or a docblock.
     */
    public function test_no_constraint_still_claims_a_service_row_descends_from_a_message_row(): void
    {
        foreach (DB::connection()->getSchemaBuilder()->getForeignKeys(self::SERVICE_TABLE) as $foreignKey) {
            self::assertNotSame(
                self::MESSAGES_TABLE,
                $foreignKey['foreign_table'] ?? null,
                'tf_messages_service still carries a foreign key to tf_messages. messageService is a '
                .'MEMBER of the Message union — a peer of message, not a descendant — so this constraint '
                .'refuses every service message and the row is lost with inserted: 0.',
            );
        }
    }

    /**
     * The family tables this file counts rows in are DISCOVERABLE, not
     * transcribed — so an arm above that reads an empty table is reading a real
     * one rather than passing on a typo.
     */
    public function test_the_messages_family_is_discoverable_from_the_live_schema(): void
    {
        foreach ([self::MESSAGES_TABLE, self::SERVICE_TABLE, self::SERVICE_TABLE.'_from_id'] as $table) {
            self::assertContains(
                $table,
                $this->liveTables(),
                $table.' is not in the migrated schema, so the row-count arms in this file are counting '
                .'against nothing',
            );
        }
    }
}
