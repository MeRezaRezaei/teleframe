<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Ingest\UpdateRouter;
use MeRezaRezaei\Teleframe\Laravel\Models\UpdateRoutingRule;

/**
 * Phase C re-baseline of the legacy RouteIdempotency test family.
 *
 * The legacy request-level dedup (RouteIdempotency + generated tl_route_*
 * tables: mark/seen roundtrips, deterministic keyFor) was purged with the
 * curated migration: there is NO response-token table, and ingestResponse
 * re-ingests unconditionally (content upserts on the curated composite
 * keys are the only idempotency — proven in IngestResponseTest).
 *
 * The routing truth that replaced it is the per-peer settings table
 * tg_update_routing (UpdateRoutingRule + UpdateRouter): one rule per
 * (account_id, peer_type, peer_id) UNIQUE triple deciding act_on vs
 * store_only. The idempotency guarantees move to that surface — rule
 * upserts are stable, peers/tenants never collide, and the store_only
 * default is the loop-prevention default the verbatim mandates.
 *
 * This file pins the IDEMPOTENCY / scoping guarantees of that surface;
 * mode()/classify() behavior details live in UpdateRouterTest.
 */
final class RouteIdempotencyTest extends IngestTestCase
{
    private const ACCOUNT = 7;

    private const OTHER_ACCOUNT = 8;

    private const CHANNEL_ID = 1737473577;

    private function router(): UpdateRouter
    {
        return new UpdateRouter(DB::connection());
    }

    public function test_legacy_route_tables_are_purged(): void
    {
        // The curated dial migrates tf_* + tg_update_routing; the generated
        // tl_route_* / tf_routes request-token surface does not exist.
        self::assertTrue(Schema::hasTable('tg_update_routing'));
        self::assertFalse(Schema::hasTable('tl_route_messages_get_history'));
        self::assertFalse(Schema::hasTable('tl_route_updates_get_difference'));
        self::assertFalse(Schema::hasTable('tf_routes'));
    }

    public function test_rule_upsert_is_idempotent_on_the_unique_peer_triple(): void
    {
        $values = [
            'account_id' => self::ACCOUNT,
            'peer_type' => 3,
            'peer_id' => self::CHANNEL_ID,
            'mode' => UpdateRoutingRule::MODE_STORE_ONLY,
            'priority' => 0,
        ];

        DB::table('tg_update_routing')->updateOrInsert(
            ['account_id' => $values['account_id'], 'peer_type' => $values['peer_type'], 'peer_id' => $values['peer_id']],
            $values,
        );
        DB::table('tg_update_routing')->updateOrInsert(
            ['account_id' => $values['account_id'], 'peer_type' => $values['peer_type'], 'peer_id' => $values['peer_id']],
            [...$values, 'mode' => UpdateRoutingRule::MODE_ACT_ON],
        );

        $row = DB::table('tg_update_routing')->sole();
        self::assertSame(UpdateRoutingRule::MODE_ACT_ON, $row->mode, 're-upsert flips the mode in place');
        self::assertSame(1, DB::table('tg_update_routing')->count(), 'unique (account_id, peer_type, peer_id) — no second rule row');
    }

    public function test_distinct_peers_get_distinct_rules(): void
    {
        $router = $this->router();

        DB::table('tg_update_routing')->insert([
            ['account_id' => self::ACCOUNT, 'peer_type' => 3, 'peer_id' => 55, 'mode' => UpdateRoutingRule::MODE_ACT_ON, 'priority' => 0],
            ['account_id' => self::ACCOUNT, 'peer_type' => 3, 'peer_id' => 66, 'mode' => UpdateRoutingRule::MODE_STORE_ONLY, 'priority' => 0],
        ]);

        self::assertSame(2, DB::table('tg_update_routing')->count());
        self::assertSame(UpdateRoutingRule::MODE_ACT_ON, $router->mode(self::ACCOUNT, 3, 55), 'peer 55 acts');
        self::assertSame(UpdateRoutingRule::MODE_STORE_ONLY, $router->mode(self::ACCOUNT, 3, 66), 'peer 66 stores');
        self::assertSame(UpdateRoutingRule::MODE_STORE_ONLY, $router->mode(self::ACCOUNT, 3, 77), 'unlisted peer never acts');
    }

    public function test_routing_rules_are_tenant_scoped(): void
    {
        $router = $this->router();

        DB::table('tg_update_routing')->insert([
            ['account_id' => self::ACCOUNT, 'peer_type' => 3, 'peer_id' => self::CHANNEL_ID, 'mode' => UpdateRoutingRule::MODE_ACT_ON, 'priority' => 0],
            ['account_id' => self::OTHER_ACCOUNT, 'peer_type' => 3, 'peer_id' => self::CHANNEL_ID, 'mode' => UpdateRoutingRule::MODE_STORE_ONLY, 'priority' => 0],
        ]);

        self::assertSame(2, DB::table('tg_update_routing')->count(), 'same peer under two accounts = two rules');

        self::assertSame(UpdateRoutingRule::MODE_ACT_ON, $router->mode(self::ACCOUNT, 3, self::CHANNEL_ID), 'account 7 opts in');
        self::assertSame(UpdateRoutingRule::MODE_STORE_ONLY, $router->mode(self::OTHER_ACCOUNT, 3, self::CHANNEL_ID), 'account 8 stays silent');
    }

    public function test_unmarked_peer_defaults_to_store_only(): void
    {
        $router = $this->router();

        // Explicitly-marked differences: no rule → explicitMode() null,
        // mode()/classify() agree on the verbatim store_only default.
        self::assertNull($router->explicitMode(self::ACCOUNT, 3, self::CHANNEL_ID));
        self::assertSame(UpdateRoutingRule::MODE_STORE_ONLY, $router->mode(self::ACCOUNT, 3, self::CHANNEL_ID));
        self::assertSame(
            UpdateRoutingRule::MODE_STORE_ONLY,
            $router->classify(self::ACCOUNT, ['channel_id' => self::CHANNEL_ID]),
            'no rule → store the fact, never act (loop prevention)',
        );
    }

    public function test_act_on_mark_flips_the_classify_decision(): void
    {
        DB::table('tg_update_routing')->insert([
            'account_id' => self::ACCOUNT,
            'peer_type' => 3,
            'peer_id' => self::CHANNEL_ID,
            'mode' => UpdateRoutingRule::MODE_ACT_ON,
            'priority' => 0,
        ]);

        $router = $this->router();
        self::assertSame(
            UpdateRoutingRule::MODE_ACT_ON,
            $router->classify(self::ACCOUNT, ['channel_id' => self::CHANNEL_ID]),
        );
        self::assertSame(UpdateRoutingRule::MODE_ACT_ON, $router->explicitMode(self::ACCOUNT, 3, self::CHANNEL_ID));

        // The marked channel's decision does not leak to neighbor peers.
        self::assertSame(UpdateRoutingRule::MODE_STORE_ONLY, $router->classify(self::ACCOUNT, ['channel_id' => self::CHANNEL_ID + 1]));
    }

    public function test_store_only_mark_is_distinct_from_no_rule(): void
    {
        DB::table('tg_update_routing')->insert([
            'account_id' => self::ACCOUNT,
            'peer_type' => 3,
            'peer_id' => self::CHANNEL_ID,
            'mode' => UpdateRoutingRule::MODE_STORE_ONLY,
            'priority' => 0,
        ]);

        $router = $this->router();
        self::assertSame(UpdateRoutingRule::MODE_STORE_ONLY, $router->classify(self::ACCOUNT, ['channel_id' => self::CHANNEL_ID]));
        self::assertSame(UpdateRoutingRule::MODE_STORE_ONLY, $router->explicitMode(self::ACCOUNT, 3, self::CHANNEL_ID), 'explicit store_only, not the null default');
        self::assertSame(UpdateRoutingRule::MODE_STORE_ONLY, $router->mode(self::ACCOUNT, 3, self::CHANNEL_ID));
    }
}
