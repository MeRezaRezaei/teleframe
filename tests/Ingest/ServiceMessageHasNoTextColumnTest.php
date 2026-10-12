<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessage;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessageService;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;

/**
 * A `messageService` must not try to write a `message` COLUMN — the two
 * messages surfaces are different arities and only one of them has text.
 *
 * ## The defect
 *
 * `UpdateIngestor::writeMessage()` built its curated `$fill` with
 * `'message' => (string) ($payload['message'] ?? '')` UNCONDITIONALLY, while
 * routing `messageService` to `tf_messages_service` via `MESSAGE_TABLES`. The
 * shipped DDL for that table has 13 columns and no `message`; `tf_messages`
 * has 22 and does. So every `messageService` raised
 * `table tf_messages_service has no column named message` — and the code
 * contradicted its own next statement, whose comment says exactly this.
 *
 * ## Why the severity is the transaction, not the message
 *
 * `ingest()` wraps the write in `DB::transaction`, and
 * `updateNewMessage.message` is `message | messageEmpty | messageService`
 * (src/Schema/schema/sources/TL_telegram_v227.tl). So the raise did not merely
 * skip one un-mirrored message: on the nested route it took the enclosing
 * `tf_updates` fact row and all 17 `tf_updates_*` children down with it. The
 * update became not merely un-mirrored but UN-INGESTIBLE. `BackfillCommand`
 * and the public `Teleclient::ingest()` / `Teleframe::ingest()` all funnel
 * through `writeMessage`, so all three shipped entry points were dead for this
 * constructor.
 *
 * ## Why this test measures the SHIPPED DDL
 *
 * Nothing here transcribes a column list. The ingest arms run against
 * `IngestTestCase`, which migrates the shipped migration DIRECTORY via
 * `UpdateIngestor::migrationPaths()`, and the premise arm boots
 * `ShippedStoreSchema::capsuleFor()` over the same shipped files. A test that
 * re-typed the 13 service columns would be a second copy of the DDL, and a
 * second copy is exactly how this defect's sibling bugs shipped.
 *
 * Provenance: src/Teleframe/Ingest/UpdateIngestor.php::writeMessage (the
 * `$fill['message']` assignment, now behind the same `$table !==
 * TF_MESSAGES_SERVICE` test the search index already used);
 * src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php:418
 * (tf_messages_service, 13 columns, no `message`) and :440-492 (its five
 * children, including tf_messages_service_from_id).
 */
final class ServiceMessageHasNoTextColumnTest extends IngestTestCase
{
    private const ACCOUNT = 7;

    private const SERVICE_ID = 4243;

    private const TEXT_ID = 4242;

    private const CHANNEL_ID = 1737473577;

    private const USER_ID = 501558149;

    private const SERVICE_TABLE = 'tf_messages_service';

    private const MESSAGES_MIGRATION = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php';

    /**
     * Tenant 7 (`ACCOUNT`) — the only one.
     *
     * The service/text arms ingest under `self::ACCOUNT` into `tf_messages` and
     * `tf_messages_service`, and the premise arm re-runs `setUp()` to read the
     * shipped DDL over the same tables. `account_id` on all of them is the
     * caller's TELEGRAM user id — `MirrorIngesterFactory.php:160` writes it
     * straight in — and the FK targets `telegram_accounts.user_id`, not the
     * vault row's own `id`. Both message ids (`SERVICE_ID` 4243, `TEXT_ID` 4242)
     * belong to this one tenant, so one declaration covers the file.
     *
     * Production always plants the parent first:
     * `VaultAddAccountCommand.php:80` writes `user_id` at account-add time.
     *
     * @return list<int>
     */
    protected function ingestTenantIds(): array
    {
        return [7];
    }

    /**
     * A real `messageService` — the shape the operator's history actually
     * carries: a peer service action (a read receipt) plus the three child
     * kinds this table's catalog declares and the writer must therefore reach
     * (from_id, reply_to, ttl_period).
     *
     * It deliberately does NOT carry a `message` string. It must not need to:
     * the column is absent, so a payload that omits the field and a payload
     * that carries it must both write the same 13 columns. The
     * `_withMessageHeader` variant exists precisely to prove that — the
     * payload's shape is not what decides the fill.
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
            // out | reply_to | ttl_period  (explicit keys — teleframe truth)
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
     * The same service message, with a `message` header riding along.
     *
     * A service message's TL body is its ACTION, but nothing stops a caller
     * (or a difference stream) handing the writer a `message` key. The fill
     * must still be 13 columns wide, because the TABLE has 13 — which is
     * precisely why keying the fill off the payload is wrong and keying it
     * off the table is right.
     *
     * @return array<string, mixed>
     */
    private static function servicePayloadWithMessageHeader(int $id = self::SERVICE_ID): array
    {
        return [...self::servicePayload($id), 'message' => 'a header that is not a column'];
    }

    /**
     * @return array<string, mixed>
     */
    private static function textPayload(int $id = self::TEXT_ID): array
    {
        return [
            '_' => 'message',
            'id' => $id,
            'from_id' => ['_' => 'peerUser', 'user_id' => self::USER_ID],
            'peer_id' => ['_' => 'peerChannel', 'channel_id' => self::CHANNEL_ID],
            'date' => 1724852400,
            'message' => 'Check https://t.me/teleframe from @Reza',
        ];
    }

    /**
     * The premise, read off the shipped DDL rather than asserted from memory:
     * the two messages surfaces are different arities, and only the text one
     * has a `message` column.
     *
     * If a future migration adds `message` to tf_messages_service, this arm
     * goes red and tells the maintainer the gate below has been narrowed to
     * a condition that is no longer load-bearing — which is the correct
     * moment to decide, not to discover silently.
     */
    public function test_the_shipped_ddl_gives_the_two_surfaces_different_arities(): void
    {
        $container = Container::getInstance();
        $capsule = ShippedStoreSchema::capsuleFor(self::MESSAGES_MIGRATION);

        try {
            $schema = $capsule->getConnection()->getSchemaBuilder();

            self::assertFalse(
                $schema->hasColumn(self::SERVICE_TABLE, 'message'),
                'the shipped tf_messages_service now HAS a message column — the guard in writeMessage() is no longer load-bearing and this test must be re-derived, not left passing',
            );

            self::assertTrue(
                $schema->hasColumn('tf_messages', 'message'),
                'the shipped tf_messages lost its `message` column — the 22-column surface this gate protects has been changed underneath it',
            );
        } finally {
            ShippedStoreSchema::restoreFacadeApplication($container);
        }
    }

    /**
     * 1 + 2: a real `messageService` does not throw, the root is a
     * TfMessageService, and the catalog child walk is REACHED.
     *
     * The child assertion is the load-bearing one. Before the fix the raise
     * happened at the parent upsert, so `writeChildrenFromCatalog` never ran
     * and `tf_messages_service_from_id` stayed empty — which is also the
     * tf_messages_service half of an earlier cycle's Goal B that was left
     * named-but-unstarted.
     */
    public function test_a_service_message_ingests_and_reaches_its_catalog_children(): void
    {
        $root = (new UpdateIngestor)->ingest(self::servicePayload(), self::ACCOUNT);

        self::assertInstanceOf(
            TfMessageService::class,
            $root,
            'a messageService must hydrate as TfMessageService — the ctor→table routing in MESSAGE_TABLES and the model choice in writeMessage() disagree',
        );
        self::assertSame('messageService', $root->getAttribute('constructor'));
        self::assertSame(self::SERVICE_ID, (int) $root->getAttribute('id'));

        self::assertSame(
            1,
            $this->countRows(self::SERVICE_TABLE.'_from_id'),
            'tf_messages_service_from_id is empty — writeChildrenFromCatalog was never reached for the service surface, so the parent upsert raised first. This is the Goal-B child half that was named but never started.',
        );
    }

    /**
     * The falsifying case that pins WHY the key is gated: a service payload
     * that DOES carry a `message` header still writes a 13-column row.
     *
     * This is the arm that distinguishes "the fix keys off the table" from
     * "the fix happens to work because this fixture omits the field". Under
     * the pre-fix code this payload is exactly as fatal as the one above.
     */
    public function test_a_service_message_carrying_a_message_header_still_writes_the_row(): void
    {
        $root = (new UpdateIngestor)->ingest(self::servicePayloadWithMessageHeader(), self::ACCOUNT);

        self::assertInstanceOf(TfMessageService::class, $root);

        // Read it back the way the shipped surface is meant to be read: by
        // naming the columns. A `select *` here would be an arity error, which
        // is the same disease the migration header warns about.
        $db = DB::connection();
        $row = $db->table(self::SERVICE_TABLE)
            ->where('account_id', self::ACCOUNT)
            ->where('id', self::SERVICE_ID)
            ->first();

        self::assertNotNull($row, 'the service row did not land');
        self::assertObjectNotHasProperty(
            'message',
            $row,
            'the stored service row grew a `message` attribute — the table has 13 columns and this value has nowhere to live',
        );
        self::assertSame(1, $this->countRows(self::SERVICE_TABLE.'_from_id'));
    }

    /**
     * 3: the 22-column text surface must not regress. The `message` key moved
     * behind a condition; this proves the condition is false for tf_messages.
     */
    public function test_a_plain_message_still_carries_its_text(): void
    {
        $root = (new UpdateIngestor)->ingest(self::textPayload(), self::ACCOUNT);

        self::assertInstanceOf(TfMessage::class, $root);
        self::assertSame(
            'Check https://t.me/teleframe from @Reza',
            $root->getAttribute('message'),
            'tf_messages lost its text — the `message` key was moved behind a condition that is wrongly true for the text surface',
        );
        self::assertSame(
            1,
            $this->countRows('tf_messages_from_id'),
            'the text surface lost its from_id child — the catalog walk regressed for tf_messages',
        );
    }

    /**
     * 4: the property that makes the defect SEVERE, which the suite was blind
     * to — a service message on the NESTED route must not roll back its
     * enclosing tf_updates fact.
     *
     * `updateNewMessage.message` is `message | messageEmpty | messageService`,
     * so this is a real wire shape, not a synthetic one. Before the fix the
     * nested `writeMessage` raised inside the `DB::transaction` that
     * `ingest()` opens, taking the tf_updates row and its 17 children with
     * it: the update was not merely un-mirrored, it was un-ingestible. The
     * fact row is therefore asserted to EXIST here, not merely to be
     * attempted.
     */
    public function test_a_nested_service_message_does_not_roll_back_its_update_fact(): void
    {
        $ingestor = new UpdateIngestor;

        $root = $ingestor->ingest([
            '_' => 'updateNewMessage',
            'message' => self::servicePayload(),
            'pts' => 1349,
            'pts_count' => 1,
        ], self::ACCOUNT);

        self::assertNotNull($root, 'the enclosing update was lost — a nested service message raised and took its transaction with it');
        self::assertSame('updateNewMessage', $root->getAttribute('constructor'));

        $db = DB::connection();

        self::assertSame(
            1,
            $db->table('tf_updates')->where('account_id', self::ACCOUNT)->count(),
            'the tf_updates fact row was rolled back by a nested service message — the update is un-ingestible, not merely un-mirrored',
        );
        self::assertSame(
            1,
            $db->table('tf_updates_pts')->where('account_id', self::ACCOUNT)->count(),
            'the tf_updates_pts child was rolled back too — the whole enclosing fact set went with the message',
        );
        self::assertSame(
            1,
            $this->countRows(self::SERVICE_TABLE),
            'the nested service message itself did not land — the guard is in place but the service surface stopped storing',
        );
    }

    /**
     * Both constructors, one after the other, in the same tenant — the mixed
     * traffic a real account produces, and the shape that proves the two
     * surfaces coexist rather than one having displaced the other.
     */
    public function test_both_surfaces_coexist_in_one_tenant(): void
    {
        $ingestor = new UpdateIngestor;
        $ingestor->ingest(self::textPayload(), self::ACCOUNT);
        $service = $ingestor->ingest(self::servicePayload(), self::ACCOUNT);

        self::assertInstanceOf(TfMessageService::class, $service);
        self::assertSame(1, $this->countRows('tf_messages'));
        self::assertSame(1, $this->countRows(self::SERVICE_TABLE));
        self::assertSame(1, $this->countRows('tf_messages_from_id'));
        self::assertSame(1, $this->countRows(self::SERVICE_TABLE.'_from_id'));
    }

    /**
     * @return list<string>
     */
    private function shippedMigrationPaths(): array
    {
        $paths = glob(__DIR__.'/../../src/Laravel/Migrations/*.php') ?: [];
        sort($paths);

        self::assertNotSame([], $paths, 'no shipped migrations found — this test would measure an empty schema');

        return array_values($paths);
    }

    private function countRows(string $table): int
    {
        $db = DB::connection();

        self::assertTrue(
            $db->getSchemaBuilder()->hasTable($table),
            "table {$table} does not exist in the migrated surface — the assertion below would pass vacuously",
        );

        return $db->table($table)->where('account_id', self::ACCOUNT)->count();
    }
}
