<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Handler\Middleware\EchoEliminator;
use MeRezaRezaei\Teleframe\Ingest\SelfOriginatedClassifier;
use MeRezaRezaei\Teleframe\Ingest\UpdateRouter;
use MeRezaRezaei\Teleframe\Laravel\Models\UpdateRoutingRule;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use Orchestra\Testbench\TestCase as TestbenchTestCase;
use Psr\SimpleCache\CacheInterface;

/**
 * Self-origination via the send-time registry — the verbatim's group-2
 * reflection detection done with reliable evidence. Telethon's docs flag
 * the `out` flag as unreliable in broadcast channels, so a send-registry
 * match (we literally just sent it) is the authoritative signal.
 */
final class SelfOriginatedRegistryTest extends TestbenchTestCase
{
    private array $store = [];

    private SelfOriginatedClassifier $classifier;

    protected function setUp(): void
    {
        parent::setUp();
        $sends = new class($this->store) implements CacheInterface
        {
            public function __construct(private array &$store) {}

            public function get($key, $default = null): mixed
            {
                return $this->store[$key] ?? $default;
            }

            public function set($key, $value, $ttl = null): bool
            {
                $this->store[$key] = $value;

                return true;
            }

            public function delete($key): bool
            {
                unset($this->store[$key]);

                return true;
            }

            public function clear(): bool
            {
                $this->store = [];

                return true;
            }

            public function getMultiple($keys, $default = null): iterable
            {
                foreach ($keys as $key) {
                    yield $key => $this->store[$key] ?? $default;
                }
            }

            public function setMultiple($values, $ttl = null): bool
            {
                foreach ($values as $key => $value) {
                    $this->store[$key] = $value;
                }

                return true;
            }

            public function deleteMultiple($keys): bool
            {
                foreach ($keys as $key) {
                    unset($this->store[$key]);
                }

                return true;
            }

            public function has($key): bool
            {
                return isset($this->store[$key]);
            }
        };

        $this->classifier = new SelfOriginatedClassifier(
            new UpdateRouter(DB::connection()),
            $sends,
        );
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

    public function test_registry_match_is_self_originated_even_without_out_flag(): void
    {
        $this->store[EchoEliminator::KEY.':42:987654321'] = [
            'random_id' => 987654321,
            'sent_at' => time(),
        ];

        $payload = [
            '_' => 'message',
            'random_id' => 987654321,
            'out' => false, // broadcast channel: out is unreliable — ignore it
            'peer_id' => ['_type' => 2, '_id' => 900],
        ];

        self::assertSame(
            UpdateRoutingRule::MODE_STORE_ONLY,
            $this->classifier->classify(42, $payload),
            'send-registry match → reflection of our behaviour → store_only (loop prevention)'
        );
    }

    public function test_msg_id_registry_match_also_counts(): void
    {
        $this->store[EchoEliminator::KEY.':42:555'] = [
            'msg_id' => 555,
            'sent_at' => time(),
        ];

        $payload = [
            '_' => 'message',
            'msg_id' => 555,
            'out' => false,
            'peer_id' => ['_type' => 2, '_id' => 900],
        ];

        self::assertSame(
            UpdateRoutingRule::MODE_STORE_ONLY,
            $this->classifier->classify(42, $payload)
        );
    }

    public function test_no_registry_and_out_false_routes_by_peer_settings(): void
    {
        $payload = [
            '_' => 'message',
            'random_id' => 777,
            'msg_id' => 888,
            'out' => false,
            'peer_id' => ['_type' => 2, '_id' => 901],
        ];

        self::assertSame(
            UpdateRoutingRule::MODE_STORE_ONLY,
            $this->classifier->classify(42, $payload),
            'clean update (no registry, out=false) → peer settings default store_only'
        );
    }

    public function test_registry_match_does_not_override_explicit_act_on_escape_hatch(): void
    {
        $this->store[EchoEliminator::KEY.':42:123'] = [
            'random_id' => 123,
            'sent_at' => time(),
        ];

        $this->artisan('migrate', [
            '--path' => dirname(__DIR__, 2).'/src/Laravel/Migrations/2026_09_14_000001_create_tg_update_routing_table.php',
            '--realpath' => true,
        ])->assertExitCode(0);
        DB::table('tg_update_routing')->insert([
            'account_id' => 42,
            'peer_type' => 2,
            'peer_id' => 900,
            'mode' => UpdateRoutingRule::MODE_ACT_ON,
        ]);

        $payload = [
            '_' => 'message',
            'random_id' => 123,
            'out' => false,
            'peer_id' => ['_type' => 2, '_id' => 900],
        ];

        self::assertSame(
            UpdateRoutingRule::MODE_ACT_ON,
            $this->classifier->classify(42, $payload),
            'escape hatch: chain-effect peers stay act_on even for our own sends'
        );
    }
}
