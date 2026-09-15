<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessage;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUpdate;

/**
 * Phase C contract proof — UpdateIngestor re-pointed at the curated dial.
 *
 * The legacy write path (constructor_id FK, tl_data JSONB, extracted legacy
 * columns) is gone; ingesting the wire shape must now decompose into the
 * hand-authored curated surface (tf_messages / tf_updates + children) and
 * emit the UpdateStored event with the hydrated curated model.
 *
 * Peer naming note: the brief's draft asserted `peer_id_type`/`peer_id_id`
 * on the tf_messages row — that is the generated-surface naming. The CURATED
 * dial (src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php
 * + the TfMessage cast map) declares the inline peer halves as `peer_type` /
 * `peer_id`, and that is the surface `migrateIngestSurface()` migrates. Code
 * wins: the row assertions here use the curated column names.
 */
final class UpdateIngestorContractTest extends IngestTestCase
{
    private const ACCOUNT = 42;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('telegram_accounts')->insert([
            'id' => self::ACCOUNT,
            'label' => 'contract-'.self::ACCOUNT,
            'type' => 'user',
            'dc_id' => 2,
        ]);
    }

    public function test_migrate_ingest_surface_migrates_the_curated_dial(): void
    {
        self::assertTrue(Schema::hasTable('tf_messages'));
        self::assertTrue(Schema::hasTable('tf_messages_service'));
        self::assertTrue(Schema::hasTable('tf_updates'));
        self::assertTrue(Schema::hasTable('tf_updates_pts'));
        self::assertTrue(Schema::hasTable('tf_updates_message'));

        self::assertSame('peer_type', Schema::getColumnListing('tf_messages')[2] ?? null);
    }

    public function test_message_ingest_writes_one_curated_row_and_emits_update_stored(): void
    {
        $captured = [];
        $ingestor = new UpdateIngestor(events: self::capturingDispatcher($captured));

        $root = $ingestor->ingest(self::messagePayload(), self::ACCOUNT);

        self::assertSame(1, DB::table('tf_messages')->where('account_id', self::ACCOUNT)->count(), 'one row per ingest');

        $row = DB::table('tf_messages')->where('account_id', self::ACCOUNT)->where('id', 5)->first();
        self::assertNotNull($row);
        self::assertSame('message', $row->constructor);
        self::assertSame(3, (int) $row->peer_type, 'peerChannel normalizes to peer_type 3');
        self::assertSame(900, (int) $row->peer_id);
        self::assertSame('proof', $row->message);
        self::assertSame(0, (int) $row->out);

        self::assertInstanceOf(TfMessage::class, $root);
        self::assertCount(1, $captured, 'one UpdateStored per ingest');
        self::assertInstanceOf(UpdateStored::class, $captured[0]);
        self::assertSame(self::ACCOUNT, $captured[0]->accountId);
        self::assertInstanceOf(TfMessage::class, $captured[0]->model, 'event carries the hydrated curated model');
        $model = $captured[0]->model;
        self::assertSame(5, (int) $model->getAttribute('id'));
        self::assertSame(3, (int) $model->getAttribute('peer_type'));
        self::assertSame(900, (int) $model->getAttribute('peer_id'));
        self::assertSame('proof', $model->getAttribute('message'));
        self::assertFalse($model->getAttribute('out'));
    }

    public function test_reingest_of_same_message_is_upsert_stable(): void
    {
        $captured = [];
        $ingestor = new UpdateIngestor(events: self::capturingDispatcher($captured));

        $ingestor->ingest(self::messagePayload(), self::ACCOUNT);
        $ingestor->ingest(self::messagePayload(), self::ACCOUNT);

        self::assertSame(1, DB::table('tf_messages')->where('account_id', self::ACCOUNT)->count(), 'upsert-stable on (account_id, id)');
        $row = DB::table('tf_messages')->where('account_id', self::ACCOUNT)->where('id', 5)->first();
        self::assertSame('proof', $row->message);
        self::assertSame(3, (int) $row->peer_type);

        self::assertCount(2, $captured, 'still one UpdateStored event per ingest');
        self::assertInstanceOf(UpdateStored::class, $captured[1]);
        self::assertSame(5, (int) $captured[1]->model->getAttribute('id'));
    }

    public function test_update_ctor_writes_updates_row_with_children_and_sibling_message(): void
    {
        $captured = [];
        $ingestor = new UpdateIngestor(events: self::capturingDispatcher($captured));

        $root = $ingestor->ingest([
            '_' => 'updateNewMessage',
            'message' => self::messagePayload(),
            'pts' => 1400,
            'pts_count' => 1,
        ], self::ACCOUNT);

        $update = DB::table('tf_updates')->where('account_id', self::ACCOUNT)->first();
        self::assertNotNull($update);
        self::assertSame(1, DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count());
        self::assertSame(0, (int) $update->seq);
        self::assertSame(0, (int) $update->position);
        self::assertSame('updateNewMessage', $update->constructor);

        self::assertSame(1400, (int) DB::table('tf_updates_pts')->where('account_id', self::ACCOUNT)->value('pts'), 'pts child');
        self::assertSame(1, (int) DB::table('tf_updates_pts_count')->where('account_id', self::ACCOUNT)->value('pts_count'), 'pts_count child');
        $linked = DB::table('tf_updates_message')->where('account_id', self::ACCOUNT)->first();
        self::assertNotNull($linked, 'message routing child');
        self::assertSame(3, (int) $linked->peer_type);
        self::assertSame(900, (int) $linked->peer_id);
        self::assertSame(5, (int) $linked->message_id);

        self::assertSame(1, DB::table('tf_messages')->where('account_id', self::ACCOUNT)->count(), 'nested message lands in the messages mirror (sibling domain)');
        self::assertSame(5, (int) DB::table('tf_messages')->where('account_id', self::ACCOUNT)->value('id'));

        self::assertInstanceOf(TfUpdate::class, $root);
        self::assertCount(1, $captured);
        self::assertInstanceOf(TfUpdate::class, $captured[0]->model);
        self::assertSame(0, (int) $captured[0]->model->getAttribute('seq'));
        self::assertSame('updateNewMessage', $captured[0]->model->getAttribute('constructor'));
    }

    public function test_canonical_peer_pair_ingests_like_the_wire_ctor_object(): void
    {
        $payload = self::messagePayload();
        $payload['peer_id'] = ['_type' => 3, '_id' => 900];

        (new UpdateIngestor)->ingest($payload, self::ACCOUNT);

        $row = DB::table('tf_messages')->where('account_id', self::ACCOUNT)->where('id', 5)->first();
        self::assertSame(3, (int) $row->peer_type, 'PeerShapeTool normalizes the canonical pair too');
        self::assertSame(900, (int) $row->peer_id);
    }

    public function test_unknown_ctor_writes_nothing_and_does_not_throw(): void
    {
        $ingestor = new UpdateIngestor;

        $root = $ingestor->ingest(['_' => 'randomConstructor', 'x' => 1], self::ACCOUNT);

        self::assertNull($root);
        self::assertSame(0, DB::table('tf_messages')->where('account_id', self::ACCOUNT)->count());
        self::assertSame(0, DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count());
    }

    /**
     * @param  list<mixed>  $captured
     */
    private static function capturingDispatcher(array &$captured): Dispatcher
    {
        return new class($captured) implements Dispatcher
        {
            public function __construct(private array &$captured) {}

            public function dispatch($event, $payload = [], $halt = false): array
            {
                $this->captured[] = $event;

                return [];
            }

            public function listen($events, $listener = null) {}

            public function hasListeners($eventName): bool
            {
                return false;
            }

            public function subscribe($subscriber) {}

            public function until($event, $payload = []) {}

            public function forget($event) {}

            public function forgetPushed() {}

            public function push($event, $payload = []) {}

            public function flush($event) {}
        };
    }

    /**
     * @return array<string, mixed>
     */
    private static function messagePayload(): array
    {
        return [
            '_' => 'message',
            'id' => 5,
            'peer_id' => ['_' => 'peerChannel', 'channel_id' => 900],
            'date' => 1750000000,
            'message' => 'proof',
            'out' => false,
        ];
    }
}
