<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\RouteIdempotency;
use Symfony\Component\Uid\UuidV5;

/**
 * Plan Task 4: route-table idempotency primitives — mark/seen roundtrip
 * against generated tl_route_* tables, deterministic keyFor derivation,
 * and the update-vs-response payload distinction (update-kind payloads
 * bypass routes and always become instances, per P1 design).
 */
final class RouteIdempotencyTest extends IngestTestCase
{
    private const ACCOUNT = 7;

    private const OTHER_ACCOUNT = 8;

    private const METHOD = 'messages.getHistory';

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', [
            '--force' => true,
            '--realpath' => true,
            '--path' => RouteIdempotency::migrationPaths(),
        ]);
    }

    public function test_route_tables_migrate_off_dial(): void
    {
        self::assertTrue(\Illuminate\Support\Facades\Schema::hasTable('tl_route_' . 'messages_get_history'));
    }

    public function test_mark_then_seen_roundtrip(): void
    {
        $routes = new RouteIdempotency();
        $key = RouteIdempotency::keyFor(self::METHOD, ['peer_id' => 1, 'offset_id' => 0]);

        self::assertFalse($routes->seen(self::METHOD, $key, self::ACCOUNT), 'unmarked route is unseen');

        $tlId = 42;
        $routes->mark(self::METHOD, $key, self::ACCOUNT, $tlId);

        self::assertTrue($routes->seen(self::METHOD, $key, self::ACCOUNT));

        $row = DB::table('tl_route_messages_get_history')->sole();
        self::assertSame($tlId, (int) $row->id, 'route row PK is the stored response instance id');
        self::assertTrue(UuidV5::isValid((string) $row->route_id), 'route_id is the deterministic idempotency uuid');
    }

    public function test_remark_is_a_no_op_row_count_stable(): void
    {
        $routes = new RouteIdempotency();
        $key = RouteIdempotency::keyFor(self::METHOD, ['peer_id' => 1]);

        $routes->mark(self::METHOD, $key, self::ACCOUNT, 42);
        $routes->mark(self::METHOD, $key, self::ACCOUNT, 42);

        self::assertSame(1, DB::table('tl_route_messages_get_history')->count());
        self::assertTrue($routes->seen(self::METHOD, $key, self::ACCOUNT));
    }

    public function test_different_params_derive_different_keys(): void
    {
        $a = RouteIdempotency::keyFor(self::METHOD, ['peer_id' => 1, 'offset_id' => 0]);
        $b = RouteIdempotency::keyFor(self::METHOD, ['peer_id' => 2, 'offset_id' => 0]);

        self::assertNotSame($a, $b);
        self::assertNotSame($a, RouteIdempotency::keyFor('users.getUsers', ['peer_id' => 1, 'offset_id' => 0]), 'method namespaces the key');
    }

    public function test_key_for_is_order_insensitive_and_deterministic(): void
    {
        $a = RouteIdempotency::keyFor(self::METHOD, ['offset_id' => 0, 'peer_id' => 1, 'filter' => ['_'=>'inputPeerChannel','channel_id'=>4,'access_hash'=>9]]);
        $b = RouteIdempotency::keyFor(self::METHOD, ['filter' => ['access_hash'=>9,'channel_id'=>4,'_'=>'inputPeerChannel'], 'peer_id' => 1, 'offset_id' => 0]);

        self::assertSame($a, $b, 'key order (any depth) must not change the key');
        self::assertSame($a, RouteIdempotency::keyFor(self::METHOD, ['offset_id' => 0, 'peer_id' => 1, 'filter' => ['_'=>'inputPeerChannel','channel_id'=>4,'access_hash'=>9]]));
    }

    public function test_routes_are_tenant_scoped(): void
    {
        $routes = new RouteIdempotency();
        $key = RouteIdempotency::keyFor(self::METHOD, ['peer_id' => 1]);

        $routes->mark(self::METHOD, $key, self::ACCOUNT, 42);

        self::assertFalse($routes->seen(self::METHOD, $key, self::OTHER_ACCOUNT), 'same route under another account is unseen');

        $routes->mark(self::METHOD, $key, self::OTHER_ACCOUNT, 43);

        self::assertTrue($routes->seen(self::METHOD, $key, self::OTHER_ACCOUNT));
        self::assertSame(2, DB::table('tl_route_messages_get_history')->count(), 'one row per account');
    }

    /**
     * Update-kind payloads always become instances and bypass routes —
     * the truth table the caller (Task 5 wiring) branches on.
     */
    public function test_is_update_payload_truth_table(): void
    {
        self::assertTrue(RouteIdempotency::isUpdatePayload(['_' => 'updateNewMessage', 'message' => []]));
        self::assertTrue(RouteIdempotency::isUpdatePayload(['_' => 'updateShort', 'update' => []]));
        self::assertTrue(RouteIdempotency::isUpdatePayload(['_' => 'updates', 'updates' => []]), 'updates container is update-kind');
        self::assertTrue(RouteIdempotency::isUpdatePayload(['_' => 'updatesCombined', 'updates' => []]));
        self::assertFalse(RouteIdempotency::isUpdatePayload(['_' => 'messages.messages', 'messages' => []]), 'method response');
        self::assertFalse(RouteIdempotency::isUpdatePayload(['_' => 'messages.channelMessages', 'messages' => []]));
        self::assertFalse(RouteIdempotency::isUpdatePayload(['_' => 'user', 'id' => 1]));
        self::assertFalse(RouteIdempotency::isUpdatePayload(['id' => 1]), 'no constructor name is not update-kind');
    }
}
