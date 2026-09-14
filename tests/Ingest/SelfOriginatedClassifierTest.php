<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\SelfOriginatedClassifier;
use MeRezaRezaei\Teleframe\Ingest\UpdateRouter;
use MeRezaRezaei\Teleframe\Laravel\Models\UpdateRoutingRule;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

/**
 * SelfOriginatedClassifier — the verbatim's group-2 default: a message we
 * sent (out=true) is a reflection of our behaviour, NOT an app input by
 * default. An explicit per-peer rule is the escape hatch.
 */
final class SelfOriginatedClassifierTest extends TestbenchTestCase
{
    private SelfOriginatedClassifier $classifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classifier = new SelfOriginatedClassifier(new UpdateRouter(DB::connection()));
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

    public function test_own_message_defaults_to_store_only(): void
    {
        $payload = [
            '_' => 'message',
            'out' => true,
            'peer_id' => ['_type' => 2, '_id' => 900],
        ];

        $mode = $this->classifier->classify(42, $payload);

        self::assertSame(
            UpdateRoutingRule::MODE_STORE_ONLY,
            $mode,
            'a message we sent is stored but NOT re-input to the app (loop prevention)'
        );
    }

    public function test_other_people_message_defaults_to_act_on(): void
    {
        $payload = [
            '_' => 'message',
            'out' => false,
            'peer_id' => ['_type' => 2, '_id' => 900],
        ];

        $mode = $this->classifier->classify(42, $payload);

        self::assertSame(
            UpdateRoutingRule::MODE_ACT_ON,
            $mode,
            'facts from other people are out of our control — an app input'
        );
    }

    public function test_explicit_act_on_rule_overrides_own_default(): void
    {
        $this->migrateSettings();
        DB::table('tg_update_routing')->insert([
            'account_id' => 42,
            'peer_type' => 2,
            'peer_id' => 900,
            'mode' => UpdateRoutingRule::MODE_ACT_ON,
        ]);

        $payload = [
            '_' => 'message',
            'out' => true,
            'peer_id' => ['_type' => 2, '_id' => 900],
        ];

        $mode = $this->classifier->classify(42, $payload);

        self::assertSame(
            UpdateRoutingRule::MODE_ACT_ON,
            $mode,
            'escape hatch: the owner explicitly marked this peer act_on — '
            .'"sometimes we need to act on something special"'
        );
    }

    public function test_explicit_store_only_rule_wins_for_others_facts(): void
    {
        $this->migrateSettings();
        DB::table('tg_update_routing')->insert([
            'account_id' => 42,
            'peer_type' => 2,
            'peer_id' => 900,
            'mode' => UpdateRoutingRule::MODE_STORE_ONLY,
        ]);

        $payload = [
            '_' => 'message',
            'out' => false,
            'peer_id' => ['_type' => 2, '_id' => 900],
        ];

        $mode = $this->classifier->classify(42, $payload);

        self::assertSame(
            UpdateRoutingRule::MODE_STORE_ONLY,
            $mode,
            'notify/log channel: others-people facts in a marked channel are store-only'
        );
    }
}
