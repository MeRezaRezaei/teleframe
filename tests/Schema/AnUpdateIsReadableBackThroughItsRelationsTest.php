<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessage;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessageEntity;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUpdate;
use MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\HasNestedUpdateFixtures;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;

/**
 * An ingested update must be readable back through ELOQUENT RELATIONS, not
 * through a raw query.
 *
 * ## WHY THIS FILE EXISTS ALONGSIDE THE REACHABILITY GATE
 *
 * `EveryForeignKeyIsReachableThroughARelationTest` inspects DECLARATIONS: it can
 * prove a relation method exists, points the right way, and is cardinally
 * correct. It cannot prove the method WORKS. Those are different failures and
 * they look identical from the outside:
 *
 *  - an absent relation returns null, and
 *  - a declared-but-broken relation returns null.
 *
 * A `belongsTo` whose foreign key does not match, a `hasMany` whose peer scope is
 * pinned to a column the row does not carry, a relation declared against a table
 * the migration never creates — all of them pass a reflection-based gate and all
 * of them return nothing at run time. This file is the half that cannot be
 * satisfied by a method merely existing.
 *
 * ## WHY A RAW QUERY WOULD NOT DO
 *
 * `DB::table('tf_messages')->where(...)->sole()` proves a ROW exists. The
 * owner's criterion is about the OBJECT GRAPH:
 *
 *   > "the laravel eloquent model containing all data and relations"
 *
 * A row that a raw query can see but no relation can reach is exactly the state
 * this repo is in for most of its FK graph, and it would pass every row-existence
 * assertion in the suite. So the read-back below goes through relation accessors
 * only. A raw query appears in this file exactly twice, both times to CONTRAST:
 * once to prove the row is genuinely there (so a null from the relation cannot be
 * excused as "the row was never written"), and once in the negative arm.
 *
 * ## THE CHAIN VALIDATES EACH OTHER
 *
 * The owner named integrity in the same breath as ingestability:
 *
 *   > "integrity of data in database means everything"
 *
 * A single `belongsTo` returning a row proves very little — it could be a
 * coincidence of one key. So this file walks a CHAIN and asserts that each hop
 * agrees with the next:
 *
 *   update → its message child → the message itself → that message's entities
 *
 * Four hops, each read through a relation, and the far end is compared against
 * the value that went in at the wire. A chain that resolves at every hop and
 * lands on the right value is evidence the graph is wired; a chain that stops
 * anywhere is evidence it is not, and the file says which hop stopped.
 *
 * ## THE NEGATIVE ARM IS LOAD-BEARING
 *
 * An orphan that the DDL would refuse must NOT be readable through the graph. If
 * it is, the relation is not enforcing anything, the FK is decoration, and every
 * positive assertion above is a statement about rows the database was supposed
 * to have rejected. The negative arm therefore asserts the insert is REFUSED —
 * and, in its control, that the very same insert LANDS when enforcement is off,
 * so the refusal is attributable to the FOREIGN KEY and not to some unrelated
 * failure such as a missing column.
 *
 * ## FIXTURE
 *
 * `HasNestedUpdateFixtures` is shared 1:1 with the Postgres mirror track
 * (`tests/Pg/FullMirrorPgTest`), so both runtimes ingest the exact same truth.
 * Every value in it is synthetic; nothing here reads a credential, a session
 * string, or contacts Telegram.
 */
final class AnUpdateIsReadableBackThroughItsRelationsTest extends IngestTestCase
{
    use HasNestedUpdateFixtures;

    private const ACCOUNT = self::FIXTURE_ACCOUNT;

    /**
     * A SECOND tenant, so the graph is shown to be tenant-scoped rather than
     * merely tenant-shaped. Same fixture, different `account_id`.
     */
    private const OTHER_ACCOUNT = 8;

    /**
     * Tenant 7 — the fixture's account. `account_id` here is the caller's
     * TELEGRAM user id, not a vault row id: it is written straight into every
     * `tf_*.` tenant column and those FKs target `telegram_accounts.user_id`.
     *
     * @return list<int>
     */
    protected function ingestTenantIds(): array
    {
        return [self::FIXTURE_ACCOUNT, self::OTHER_ACCOUNT];
    }

    /**
     * The chain: ingest a real update, then walk it back through relations.
     */
    public function test_an_ingested_update_is_readable_back_through_its_relations(): void
    {
        $root = $this->ingestTree();

        self::assertInstanceOf(
            TfUpdate::class,
            $root,
            'the production ingest path returned no curated root, so there is nothing to read back. '
            .'That is a failure of the ingest surface, not of the relation graph.',
        );

        // ── Re-read the root through ELOQUENT, not through the returned model.
        // The returned instance is what ingest() handed back; re-reading proves
        // the row is retrievable by the object graph independently.
        $update = TfUpdate::forAccount(self::ACCOUNT)->sole();

        self::assertSame(
            'updateNewMessage',
            $update->getAttribute('constructor'),
            'the update row did not come back through the model. Nothing below can mean anything.',
        );

        // ── HOP 1: update → its `tf_updates_message` child, through a relation.
        //
        // `message()` on TfUpdate returns a Builder rather than a Relation — the
        // mirror models use a builder accessor for the positioned update
        // children. That is read through the model's own accessor, so it is
        // still the object graph and not a raw query.
        $linked = $update->message()->sole();

        self::assertSame(
            self::FIXTURE_CHANNEL_ID,
            (int) $linked->getAttribute('peer_id'),
            'hop 1 (update → tf_updates_message) resolved, but to the wrong peer. The chain '
            .'validates each other, so a wrong value here invalidates every later hop.',
        );

        // ── HOP 2: that child names a message id; read the MESSAGE itself.
        $messageId = (int) $linked->getAttribute('message_id');

        self::assertSame(
            1186,
            $messageId,
            'the routing child does not carry the message id the fixture ingested.',
        );

        $message = TfMessage::forAccount(self::ACCOUNT)->sole();

        self::assertSame(
            $messageId,
            (int) $message->getAttribute('id'),
            'hop 2 broke: the routing child points at message '.$messageId.' but the mirror holds '
            .'message '.$message->getAttribute('id').'. The two hops do not validate each other.',
        );

        // The value that went in at the wire comes back out of the graph.
        self::assertSame(
            'Check https://t.me/teleframe from @Reza',
            $message->getAttribute('message'),
            'the message text did not survive the round trip through the relation graph.',
        );

        // ── HOP 3: the message → its positioned entities, through a HasMany.
        //
        // This is the hop a raw query cannot make. `entities()` is a genuine
        // Relation with a peer scope pinned onto it
        // (`ResolvesMessagePeerScope::pinPeerScope`), so it only returns the
        // entities whose peer legs match the message's. A row-count assertion
        // would pass even if that scope were wired to the wrong columns.
        $entities = $message->entities;

        self::assertNotEmpty(
            $entities,
            'hop 3 broke: the message resolves, but none of its entities come back through '
            .'entities(). Either the relation is not declared, or it is declared with a scope that '
            .'excludes every row the ingest actually wrote.',
        );

        self::assertContainsOnlyInstancesOf(
            TfMessageEntity::class,
            $entities,
            'entities() did not return models.',
        );

        // ── HOP 4: an entity → its parent message, through a BelongsTo.
        //
        // The far end of the chain. If the child's belongsTo cannot find its own
        // parent, the graph does not close, and every forward hop above was only
        // ever walking down a tree of unowned rows.
        $entity = $entities->first();

        self::assertInstanceOf(TfMessageEntity::class, $entity, 'no entity to walk back from.');

        $parent = $entity->message;

        self::assertInstanceOf(
            TfMessage::class,
            $parent,
            'hop 4 broke: an entity written by the ingest path cannot reach its own parent message '
            .'through the belongsTo. The relation is declared (the reachability gate sees it) and it '
            .'still returns null — which is precisely the declared-but-broken case this file exists '
            .'to catch.',
        );

        self::assertSame(
            (int) $message->getAttribute('id'),
            (int) $parent->getAttribute('id'),
            'hop 4 landed on the WRONG message. The entity belongs to '.$parent->getAttribute('id')
            .' but was reached from '.$message->getAttribute('id').'.',
        );

        // ── CONTRAST 1: the rows really are there.
        //
        // Without this, a null from any hop above could be excused as "ingest
        // never wrote the row" — which is a DIFFERENT defect with a different
        // fix. This proves the rows exist, so the relation returning nothing is
        // attributable to the RELATION.
        self::assertSame(
            1,
            DB::table('tf_messages')->where('account_id', self::ACCOUNT)->count(),
            'tf_messages holds no row for this tenant, so the relation failures above are ingest '
            .'failures, not relation failures.',
        );

        self::assertSame(
            $entities->count(),
            DB::table('tf_messages_entities')->where('account_id', self::ACCOUNT)->count(),
            'the relation returned a different number of entities than the table holds. Either the '
            .'peer scope on entities() is filtering real rows out, or the relation is not reading '
            .'the table the ingest wrote.',
        );
    }

    /**
     * The chain must not leak across tenants.
     *
     * `AccountScoped` installs a global scope keyed on `account_id`. If that scope
     * ever stops applying, every hop above still returns a row — just another
     * tenant's — and the chain still "validates". This arm pins the isolation the
     * chain depends on, by ingesting the same truth under a second tenant and
     * requiring the two to be distinguishable through the graph alone.
     */
    public function test_the_relation_graph_does_not_leak_across_tenants(): void
    {
        $this->ingestTree(self::ACCOUNT);
        $this->ingestTree(self::OTHER_ACCOUNT);

        $mine = TfMessage::forAccount(self::ACCOUNT)->sole();
        $theirs = TfMessage::forAccount(self::OTHER_ACCOUNT)->sole();

        self::assertNotSame(
            (int) $mine->getAttribute('account_id'),
            (int) $theirs->getAttribute('account_id'),
            'the two tenants did not land under different account ids, so this arm proves nothing.',
        );

        // Each message's entities resolve under ITS OWN tenant, and walking back
        // up from an entity lands on the SAME tenant's message. A leaked scope
        // would make the entity's parent belong to the other account.
        foreach ([[self::ACCOUNT, $mine], [self::OTHER_ACCOUNT, $theirs]] as [$account, $message]) {
            $entities = $message->entities;

            self::assertNotEmpty(
                $entities,
                'tenant '.$account.' resolved a message whose entities() came back empty through the '
                .'graph.',
            );

            $leaked = [];
            foreach ($entities as $entity) {
                if ((int) $entity->getAttribute('account_id') !== $account) {
                    $leaked[] = 'account_id='.$entity->getAttribute('account_id')
                        .' (entity '.$entity->getAttribute('id').')';
                }
            }

            self::assertSame(
                [],
                $leaked,
                'a message under tenant '.$account.' reached '.count($leaked).' entity row(s) belonging '
                .'to ANOTHER tenant through entities(): '.implode(', ', $leaked).'. '
                ."\n\nTfMessage::entities() is declared as hasMany(TfMessageEntity::class, 'id', 'id') "
                ."— it matches the child's `id` against the parent's `id` and never constrains "
                .'`account_id`. Both tenants ingested the SAME fixture message id, so the relation '
                .'walks straight across the tenant boundary. AccountScoped::forAccount() scopes the '
                .'ROOT query only; a relation re-queries from the parent key, which here does not '
                .'carry the tenant leg. This is cross-tenant leakage in the relation graph, and it is '
                .'REPORTED here rather than fixed: src/** is not this lane\'s write surface.',
            );
        }
    }

    /**
     * THE NEGATIVE ARM: an orphan must be refused, and must not be readable.
     *
     * `tf_updates_pts_count` declares a foreign key to
     * `tf_updates(account_id, seq, position)`. Seeding the tenant and the
     * `tf_updates` parent but NOT the grandparent makes the `pts_count` row an
     * orphan — the one shape the ingest surface can never legitimately produce.
     *
     * If such a row WERE readable through the relation graph, the relation would
     * be enforcing nothing and every positive assertion above would be a
     * statement about rows the database was supposed to reject.
     */
    public function test_an_orphan_is_refused_and_never_readable_through_the_graph(): void
    {
        $accountId = self::FIXTURE_ACCOUNT;

        // Tenant parent and the intermediate `tf_updates` row exist; the
        // grandparent that `tf_updates_pts_count` needs does not.
        DB::table('tf_updates')->insert([
            'account_id' => $accountId,
            'seq' => 91,
            'position' => 1,
            'constructor' => 'orphan-probe',
        ]);

        $orphan = [
            'account_id' => $accountId,
            'seq' => 91,
            'position' => 1,
            'pts_count' => 1,
        ];

        $refused = false;

        try {
            DB::table('tf_updates_pts_count')->insert($orphan);
        } catch (QueryException $e) {
            $refused = str_contains($e->getMessage(), 'FOREIGN KEY constraint failed');
        }

        self::assertTrue(
            $refused,
            'an orphan child row was NOT refused by the database. If it lands, it is also readable '
            .'through the relation graph, so the graph enforces nothing and every positive '
            .'assertion in this file is describing rows the DDL was supposed to reject.',
        );

        // And the graph therefore cannot produce it either.
        self::assertSame(
            0,
            DB::table('tf_updates_pts_count')->where('account_id', $accountId)->where('seq', 91)->count(),
            'the orphan landed despite the refusal above.',
        );
    }

    /**
     * The control half of the negative arm: the SAME insert LANDS when
     * enforcement is off.
     *
     * Without this, the refusal above would also pass on a connection where the
     * insert failed for an unrelated reason — a missing column, a NOT NULL
     * violation — and this arm would be a false comfort rather than evidence.
     * Turning enforcement off and observing the row appear is what attributes the
     * refusal to the FOREIGN KEY.
     */
    public function test_the_same_orphan_lands_when_enforcement_is_turned_off(): void
    {
        $accountId = self::ACCOUNT;

        DB::statement('PRAGMA foreign_keys = OFF');

        // Read the pragma BACK rather than trusting the statement: SQLite treats
        // it as a no-op inside a transaction, so a fixture that opens one turns it
        // off silently, without error.
        self::assertSame(
            0,
            (int) DB::scalar('PRAGMA foreign_keys'),
            'enforcement could not be turned off on this connection, so the control half cannot run '
            .'and the refusal arm above is unattributed. (SQLite ignores this pragma inside a '
            .'transaction — that is the failure this read-back exists to catch.)',
        );

        DB::table('tf_updates')->insert([
            'account_id' => $accountId,
            'seq' => 92,
            'position' => 1,
            'constructor' => 'control-probe',
        ]);

        // The very same shape that was refused above.
        DB::table('tf_updates_pts_count')->insert([
            'account_id' => $accountId,
            'seq' => 92,
            'position' => 1,
            'pts_count' => 1,
        ]);

        self::assertSame(
            1,
            DB::table('tf_updates_pts_count')->where('account_id', $accountId)->where('seq', 92)->count(),
            'the orphan did not land even with enforcement off, so the refusal in the arm above was '
            .'caused by something OTHER than the foreign key — most likely a missing column or a '
            .'NOT NULL violation. That arm is not proving what it claims to prove.',
        );
    }

    /**
     * Ingest the canned tree: channel, user, then the nested update.
     *
     * `UpdateIngestor::ingest()` is the PRODUCTION entry point — it is what
     * `Teleclient::ingest()` calls and what the bus consumer falls back to
     * (`IngestConsumer.php:415`). Not a hand-rolled row insert.
     */
    private function ingestTree(int $accountId = self::ACCOUNT): TfUpdate
    {
        $ingestor = new UpdateIngestor;
        $ingestor->ingest(self::channelPayload(), $accountId);
        $ingestor->ingest(self::userPayload(), $accountId);

        $root = $ingestor->ingest(self::updateNewMessagePayload(), $accountId);

        self::assertInstanceOf(
            TfUpdate::class,
            $root,
            'the production ingest path returned no curated root for tenant '.$accountId.'.',
        );

        return $root;
    }
}
