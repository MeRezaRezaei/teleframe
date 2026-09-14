<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\UpdateRouter;
use MeRezaRezaei\Teleframe\Laravel\Models\UpdateRoutingRule;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

/**
 * UpdateRouter — the verbatim's loop-prevention classification.
 *
 * Owner verbatim 2026-09-14: "when i make a notify channel for one of the
 * apps i should not handle the updates of that channel as new event but
 * only update that only stores the final truth".
 */
final class UpdateRouterTest extends TestbenchTestCase
{
    private UpdateRouter $router;

    protected function setUp(): void
    {
        parent::setUp();
        $this->router = new UpdateRouter(DB::connection());
    }

    protected function getApplicationBasePath()
    {
        return dirname(__DIR__, 2);
    }

    protected function getPackageProviders($app): array
    {
        return [TeleframeServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    private function migrateSettings(): void
    {
        $this->artisan('migrate', [
            '--path' => dirname(__DIR__, 2).'/src/Laravel/Migrations/2026_09_14_000001_create_tg_update_routing_table.php',
            '--realpath' => true,
        ])->assertExitCode(0);
    }

    public function test_unclassified_peer_defaults_to_act_on(): void
    {
        $mode = $this->router->mode(42, 2, 900);
        self::assertSame(UpdateRoutingRule::MODE_ACT_ON, $mode, 'no rule → safe default: act on');
    }

    public function test_store_only_peer_skips_events(): void
    {
        $this->migrateSettings();
        DB::table('tg_update_routing')->insert([
            'account_id' => 42,
            'peer_type' => 2,
            'peer_id' => 900,
            'mode' => UpdateRoutingRule::MODE_STORE_ONLY,
            'priority' => 0,
        ]);

        $mode = $this->router->mode(42, 2, 900);
        self::assertSame(UpdateRoutingRule::MODE_STORE_ONLY, $mode);
    }

    public function test_peer_in_different_account_is_not_affected(): void
    {
        $this->migrateSettings();
        DB::table('tg_update_routing')->insert([
            'account_id' => 42,
            'peer_type' => 2,
            'peer_id' => 900,
            'mode' => UpdateRoutingRule::MODE_STORE_ONLY,
            'priority' => 0,
        ]);

        $mode = $this->router->mode(99, 2, 900);
        self::assertSame(UpdateRoutingRule::MODE_ACT_ON, $mode, 'different account → unaffected');
    }

    public function test_classify_extracts_peer_from_payload(): void
    {
        $payload = ['peer_id' => ['_type' => 2, '_id' => 900]];
        $mode = $this->router->classify(42, $payload);
        self::assertSame(UpdateRoutingRule::MODE_ACT_ON, $mode, 'no rule → act on');
    }

    public function test_classify_with_channel_id_fallback(): void
    {
        $payload = ['channel_id' => 55];
        $mode = $this->router->classify(42, $payload);
        self::assertSame(UpdateRoutingRule::MODE_ACT_ON, $mode, 'channel_id → resolved, no rule → act on');
    }

    public function test_classify_store_only_channel(): void
    {
        $this->migrateSettings();
        DB::table('tg_update_routing')->insert([
            'account_id' => 42,
            'peer_type' => 2,
            'peer_id' => 55,
            'mode' => UpdateRoutingRule::MODE_STORE_ONLY,
        ]);

        $payload = ['channel_id' => 55];
        $mode = $this->router->classify(42, $payload);
        self::assertSame(UpdateRoutingRule::MODE_STORE_ONLY, $mode);
    }
}
