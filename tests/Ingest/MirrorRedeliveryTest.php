<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use MeRezaRezaei\Teleframe\Laravel\MirrorIngesterFactory;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

/**
 * Redelivery on the mirror path — the bus is at-least-once, so the SAME fact
 * arriving twice is ordinary, not exotic. Three shipped producers do it: a
 * crash mid-batch replays the un-acked tail, `XAUTOCLAIM` steals a merely-slow
 * worker's entries after 60s, and `replayDeadLetter()` re-publishes on purpose.
 *
 * Before this goal the writer did a bare `insert` and rethrew UNIQUE, so a
 * redelivery was not a duplicate row — it was a raise, and (because nothing on
 * the mirror path opened a transaction) a HALF-WRITTEN FACT: the parent stayed
 * committed, the child kept its old value, and nothing reported any of it.
 *
 * These three tests are the deliverable, and each drives the REAL entry
 * (`MirrorIngesterFactory::build()`) over the REAL shipped migrations rather
 * than the writer in isolation or a transcribed sqlite schema:
 *  - a re-delivery is the same fact twice;
 *  - an EDIT is a re-delivery of an id with a NEW value, and is the case that
 *    decides upsert over insert-or-ignore (under ignore, the pre-edit text and
 *    the pre-edit `edit_date` would live forever with nothing to correct them);
 *  - atomicity is a property of the ENTRY's transaction, so the third test can
 *    only pass through the entry — the writer alone is not atomic, and asserting
 *    atomicity on the writer would be asserting something untrue.
 *
 * Provenance: the convergence and the boundary are
 * src/Schema/Mirror/MirrorFactWriter.php (`placeRow()`) and
 * src/Laravel/MirrorIngesterFactory.php (`build()`), against the shipped DDL
 * in src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php
 * (composite `(account_id, id)` primaries) and
 * 2026_09_14_000001_create_tg_update_routing_table.php.
 */
final class MirrorRedeliveryTest extends TestbenchTestCase
{
    private const ROUTING_MIGRATION = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_000001_create_tg_update_routing_table.php';

    private const MESSAGES_MIGRATION = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php';

    protected function getPackageProviders($app): array
    {
        return [\MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider::class];
    }

    /**
     * The real bus entry, over the real shipped schema, with the global
     * container restored afterwards so the swap does not leak into other
     * suites.
     *
     * @param  callable(callable(array<string, mixed>, int): void, Capsule): void  $fn
     */
    private function withMirrorEntry(callable $fn): void
    {
        $previous = Container::getInstance();

        try {
            $capsule = ShippedStoreSchema::capsuleFor(
                self::ROUTING_MIGRATION,
                self::MESSAGES_MIGRATION,
            );
            $fn(MirrorIngesterFactory::build(), $capsule);
        } finally {
            ShippedStoreSchema::restoreFacadeApplication($previous);
        }
    }

    /**
     * A `updateNewMessage` — the ordinary delivery of a message fact.
     *
     * @return array<string, mixed>
     */
    private function newMessage(string $text, int $id = 1): array
    {
        return [
            '_' => 'updateNewMessage',
            'message' => [
                '_' => 'message',
                'id' => $id,
                'peer_id' => ['_type' => 2, '_id' => 900],
                'date' => 1726000000,
                'message' => $text,
                'out' => false,
            ],
        ];
    }

    public function test_the_same_fact_delivered_twice_raises_nothing_and_does_not_duplicate(): void
    {
        $this->withMirrorEntry(function (callable $ingest, Capsule $capsule): void {
            $db = $capsule->getConnection();

            $ingest($this->newMessage('hello'), 42);
            self::assertSame(1, $db->table('tf_messages')->where('account_id', 42)->where('id', 1)->count());

            // BEFORE this goal this line raised
            // UniqueConstraintViolationException: SQLSTATE[23000] UNIQUE
            // constraint failed: tf_messages.account_id, tf_messages.id
            $ingest($this->newMessage('hello'), 42);

            self::assertSame(
                1,
                $db->table('tf_messages')->where('account_id', 42)->where('id', 1)->count(),
                'a redelivery converges on the primary key — it does not add a second row',
            );
            self::assertSame('hello', $db->table('tf_messages')->where('id', 1)->value('message'));
        });
    }

    public function test_an_edited_fact_delivered_second_corrects_the_row(): void
    {
        $this->withMirrorEntry(function (callable $ingest, Capsule $capsule): void {
            $db = $capsule->getConnection();

            $ingest($this->newMessage('before the edit'), 42);

            // An edit arrives as a SECOND `message` fact for an id the mirror
            // already holds — that is the case which rules out insert-or-ignore.
            $ingest([
                '_' => 'updateEditMessage',
                'message' => [
                    '_' => 'message',
                    'id' => 1,
                    'peer_id' => ['_type' => 2, '_id' => 900],
                    'date' => 1726000000,
                    'message' => 'after the edit',
                    'edit_date' => 1726000999,
                    'out' => false,
                ],
            ], 42);

            self::assertSame(
                'after the edit',
                $db->table('tf_messages')->where('account_id', 42)->where('id', 1)->value('message'),
                'the row reflects the NEWEST fact Telegram sent, not the first one seen',
            );
            self::assertSame(
                1,
                $db->table('tf_messages')->where('account_id', 42)->where('id', 1)->count(),
                'the edit corrects the row in place; it is not a second message',
            );
            self::assertSame(
                1726000999,
                (int) $db->table('tf_messages_edit_date')->where('account_id', 42)->where('id', 1)->value('edit_date'),
                'the edit_date child is placed, and placed once',
            );
        });
    }

    public function test_a_failure_part_way_through_a_multi_table_fact_commits_nothing(): void
    {
        $this->withMirrorEntry(function (callable $ingest, Capsule $capsule): void {
            $db = $capsule->getConnection();

            // Fault injection on the CHILD table only, so the parent row is
            // written first and the fact genuinely dies half-way through. The
            // trigger is created in this test's own in-memory database; no
            // migration is touched. Its message is not an FK / NOT NULL /
            // unknown-column phrase, so the writer rethrows rather than
            // clueing — which is what a real "the DDL does not have this
            // table" failure looks like.
            $db->statement(
                "CREATE TRIGGER tf_injected_mid_fact_failure BEFORE INSERT ON tf_messages_edit_date"
                ." BEGIN SELECT RAISE(ABORT,'injected mid-fact failure'); END",
            );

            $raised = null;

            try {
                $ingest([
                    '_' => 'updateEditMessage',
                    'message' => [
                        '_' => 'message',
                        'id' => 1,
                        'peer_id' => ['_type' => 2, '_id' => 900],
                        'date' => 1726000000,
                        'message' => 'v',
                        'edit_date' => 1726000999,
                        'out' => false,
                    ],
                ], 42);
            } catch (\Throwable $e) {
                $raised = $e;
            }

            self::assertNotNull($raised, 'the mid-fact failure still propagates — it is not swallowed');
            self::assertSame(
                0,
                $db->table('tf_messages')->where('account_id', 42)->where('id', 1)->count(),
                'the PARENT row written before the failure is rolled back: a fact lands whole or not at all',
            );
            self::assertSame(
                0,
                $db->table('tf_messages_edit_date')->where('account_id', 42)->where('id', 1)->count(),
                'nothing from the failed fact is committed',
            );
        });
    }
}
