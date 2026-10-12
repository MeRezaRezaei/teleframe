#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Cycle-5 item 3 LIVE gate harness — the "Redis two" hot-reload proof,
 * headless and plain-PHP (no Laravel app bootstrap; production deps only:
 * laravel/framework Capsule + illuminate redis + predis — the same stack
 * the daemon builds its bus from, see DaemonCommand::buildWorker).
 *
 * Drives the SAME assertion loop as tests/Ingest/HotReloadLiveTest.php:
 * seed act_on → router answers act_on → flip the DB row to store_only via
 * the model (the provider-registered RoutingSettingsObserver refreshes the
 * live tg:routing:rules:{account} hash and re-publishes tg:bus:reload) →
 * the SAME router instance answers store_only → delete the DB row (a fresh
 * DB read would rat out: explicitMode→null / mode→default) and the router
 * still answers store_only from the hash. Exit 0 = PASS.
 *
 * Required env:
 *   TF_REDIS_TWO_DSN      Redis address (default tcp://127.0.0.1:6379).
 *                         With auth:  redis://:PASSWORD@127.0.0.1:6379
 *                                                          (or tcp://...)
 *   TF_REDIS_TWO_DB       Optional sqlite file for the ISOLATED source-of-
 *                         truth DB (default: a temp file, deleted at exit).
 *                         The real app DB is off-limits — routing rules are
 *                         source-of-truth rows, the live proof owns a fresh
 *                         one so it can never touch production rows.
 *   TF_REDIS_TWO_ACCOUNT  Dedicated account namespace (default 999999001) —
 *                         only the tg:routing:rules:{account} hash is ever
 *                         written/deleted, never a real account's.
 *
 * Usage (from repo root):
 *   TF_REDIS_TWO_DSN="redis://:pass@127.0.0.1:6379" \
 *       php bin/live-hot-reload-proof.php
 *
 * The DB is sqlite (ext-pdo_sqlite); Redis reachability is checked first
 * (ping) and a non-zero exit names the failing step otherwise.
 */
error_reporting(E_ALL);
require __DIR__.'/../vendor/autoload.php';

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Events\Dispatcher as EventDispatcher;
use Illuminate\Redis\RedisManager;
use MeRezaRezaei\Teleframe\Bus\LaravelRedisAdapter;
use MeRezaRezaei\Teleframe\Ingest\RoutingSettingsObserver;
use MeRezaRezaei\Teleframe\Ingest\UpdateRouter;
use MeRezaRezaei\Teleframe\Ingest\UpdateRoutingCache;
use MeRezaRezaei\Teleframe\Laravel\Models\UpdateRoutingRule;
use Predis\Response\Status;

const DEFAULT_DSN = 'tcp://127.0.0.1:6379';
const ACCOUNT_ID = 999999001;
const PEER_TYPE = 3; // channel (PeerShapeTool enum: 1 user, 2 chat, 3 channel)
const PEER_ID = 42424242;
const HASH_KEY = UpdateRoutingCache::KEY.':999999001';
const FIELD = '3:42424242';

$dsn = (string) (getenv('TF_REDIS_TWO_DSN') ?: DEFAULT_DSN);
$accountId = (int) (getenv('TF_REDIS_TWO_ACCOUNT') ?: (string) ACCOUNT_ID);
$dbPath = getenv('TF_REDIS_TWO_DB') ?: '';

$failed = false;
$pass = static function (string $label, callable $check) use (&$failed): void {
    try {
        $ok = $check();
    } catch (Throwable $e) {
        $failed = true;
        printf("  ✗ %s — FAIL %s: %s\n", $label, $e::class, $e->getMessage());

        return;
    }
    if (! $ok) {
        $failed = true;
        printf("  ✗ %s — FAIL (assertion false)\n", $label);

        return;
    }
    printf("  ✓ %s\n", $label);
};

printf("== Redis-two live hot-reload proof — account %d, redis %s\n", $accountId, redactDsn($dsn));

// 1. Isolated source-of-truth DB (never the app's).
if (! extension_loaded('pdo_sqlite')) {
    fwrite(STDERR, "FAIL: the harness needs ext-pdo_sqlite (or pre-create a TF_REDIS_TWO_DB your driver can open)\n");
    exit(1);
}

$cleanupDb = false;
if ($dbPath === '' || ! is_string($dbPath)) {
    $dbPath = (string) tempnam(sys_get_temp_dir(), 'teleframe-hot-reload-');
    $cleanupDb = true;
}

$container = new Container;
$capsule = new Capsule($container);
$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => $dbPath,
    'prefix' => '',
]);

$dispatcher = new EventDispatcher($container);
$capsule->setEventDispatcher($dispatcher);
$capsule->setAsGlobal();
$capsule->bootEloquent();

$pass('isolated sqlite source-of-truth ready ('.$dbPath.')', static function () use ($capsule): bool {
    $capsule->schema()->create('tg_update_routing', static function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('account_id');
        $table->unsignedTinyInteger('peer_type');
        $table->unsignedBigInteger('peer_id');
        $table->string('mode', 32)->default('act_on');
        $table->unsignedTinyInteger('priority')->default(0);
        $table->timestamps();
        $table->unique(['account_id', 'peer_type', 'peer_id']);
        $table->index(['account_id', 'mode']);
    });

    return $capsule->schema()->hasTable('tg_update_routing');
});

// 2. Live Redis through the production adapter (predis under illuminate).
$bus = null;
$pass('live Redis reachable at '.redactDsn($dsn), static function () use (&$bus, $container, $dsn): bool {
    $manager = new RedisManager($container, 'predis', [
        'default' => redisConfig($dsn),
    ]);
    $connection = $manager->connection();
    $pong = $connection->command('ping', []);
    if ($pong instanceof Status) {
        $pong = $pong->getPayload();
    }

    $bus = [$manager, new LaravelRedisAdapter($connection)];

    return (string) $pong === 'PONG';
});

if ($failed || $bus === null) {
    echo "\n== REDIS-TWO LIVE HOT-RELOAD PROOF FAILED (no live Redis) ==\n";
    exit(1);
}

[$redis, $adapter] = $bus;

// The seam under test: cache → observer (registered on the model lifecycle,
// like TeleframeServiceProvider::boot) → router (cache-first / DB fallback).
$db = $capsule->getConnection();
$cache = new UpdateRoutingCache($adapter, $db);
$observer = new RoutingSettingsObserver($cache, $dispatcher);
$container->instance(RoutingSettingsObserver::class, $observer);
UpdateRoutingRule::observe(RoutingSettingsObserver::class);
$router = new UpdateRouter($db, $cache);

// Idempotent re-runs: start clean.
$adapter->del(HASH_KEY);

// 3. Seed act_on via the model — the observer writes the live hash.
$pass('seeded act_on rule via UpdateRoutingRule (observer → live hash)', static function () use ($adapter, $accountId): bool {
    UpdateRoutingRule::query()
        ->where('account_id', $accountId)
        ->where('peer_type', PEER_TYPE)
        ->where('peer_id', PEER_ID)
        ->delete();
    UpdateRoutingRule::create([
        'account_id' => $accountId,
        'peer_type' => PEER_TYPE,
        'peer_id' => PEER_ID,
        'mode' => UpdateRoutingRule::MODE_ACT_ON,
        'priority' => 0,
    ]);

    return $adapter->hget(HASH_KEY, FIELD) === UpdateRoutingRule::MODE_ACT_ON;
});

$pass('router answers act_on (mode + explicitMode)', static function () use ($router, $accountId): bool {
    return $router->mode($accountId, PEER_TYPE, PEER_ID) === UpdateRoutingRule::MODE_ACT_ON
        && $router->explicitMode($accountId, PEER_TYPE, PEER_ID) === UpdateRoutingRule::MODE_ACT_ON;
});

// 4. The OPERATOR flips to store_only via the model — same gesture as the
//    app's observe()/ignore() toggle; the observer re-refreshes the hash.
$pass('flipped DB row to store_only via the model (observer → live hash)', static function () use ($adapter, $accountId): bool {
    $rule = UpdateRoutingRule::query()
        ->where('account_id', $accountId)
        ->where('peer_type', PEER_TYPE)
        ->where('peer_id', PEER_ID)
        ->first();
    if ($rule === null || ! $rule->update(['mode' => UpdateRoutingRule::MODE_STORE_ONLY])) {
        return false;
    }

    return $adapter->hget(HASH_KEY, FIELD) === UpdateRoutingRule::MODE_STORE_ONLY;
});

$pass('the SAME router instance now answers store_only', static function () use ($router, $accountId): bool {
    return $router->mode($accountId, PEER_TYPE, PEER_ID) === UpdateRoutingRule::MODE_STORE_ONLY
        && $router->explicitMode($accountId, PEER_TYPE, PEER_ID) === UpdateRoutingRule::MODE_STORE_ONLY;
});

// 5. The hash is the answer, not a fresh DB read: delete the DB row (query
//    builder — bypasses the observer so the hash stays warm) and ask again.
$pass('answer comes from the live hash: DB row deleted, router still store_only', static function () use ($adapter, $db, $accountId, $router): bool {
    $db->table('tg_update_routing')
        ->where('account_id', $accountId)
        ->where('peer_type', PEER_TYPE)
        ->where('peer_id', PEER_ID)
        ->delete();

    // explicitMode discriminates: a DB fallback would return null now.
    return $router->explicitMode($accountId, PEER_TYPE, PEER_ID) === UpdateRoutingRule::MODE_STORE_ONLY
        && $router->mode($accountId, PEER_TYPE, PEER_ID) === UpdateRoutingRule::MODE_STORE_ONLY
        && $adapter->hget(HASH_KEY, FIELD) === UpdateRoutingRule::MODE_STORE_ONLY;
});

// 6. Cleanup: never pollute a real hash; drop the isolated DB.
$adapter->del(HASH_KEY);
$db->table('tg_update_routing')->where('account_id', $accountId)->delete();
if ($cleanupDb) {
    @unlink($dbPath);
}

echo $failed
    ? "\n== REDIS-TWO LIVE HOT-RELOAD PROOF FAILED ==\n"
    : "\n== REDIS-TWO LIVE HOT-RELOAD PROOF PASSED (zero restart, event stream switched) ==\n";

exit($failed ? 1 : 0);

/**
 * Parse a Redis DSN into a predis/illuminate connection config.
 * Accepts tcp://host:port, redis://[:pass@]host:port, or plain host[:port].
 * Parsed here (not via illuminate's URL parser) so the gate behaves the
 * same with or without a password.
 *
 * @return array<string, string|int|float>
 */
function redisConfig(string $dsn): array
{
    $config = ['scheme' => 'tcp', 'host' => '127.0.0.1', 'port' => 6379, 'timeout' => 5.0];

    if (str_contains($dsn, '://')) {
        $u = parse_url($dsn);
        if (! is_array($u) || ($u['host'] ?? '') === '') {
            throw new RuntimeException("unparsable Redis DSN: {$dsn}");
        }
        $config['scheme'] = strtolower((string) ($u['scheme'] ?? 'tcp'));
        $config['host'] = (string) $u['host'];
        if (isset($u['port'])) {
            $config['port'] = (int) $u['port'];
        }
        if (isset($u['pass'])) {
            $config['password'] = (string) $u['pass'];
        } elseif (($u['user'] ?? '') !== '') {
            $config['password'] = (string) $u['user'];
        }
    } else {
        $bits = explode(':', $dsn, 2);
        $config['host'] = $bits[0] === '' ? '127.0.0.1' : $bits[0];
        if (isset($bits[1])) {
            $config['port'] = (int) $bits[1];
        }
    }

    return $config;
}

/**
 * DSN with any user-info password removed for display — the harness never
 * prints credentials into a recordable stream (session-string hygiene, W-15).
 * parse_url-based; the plain `host[:port]` form carries no password.
 */
function redactDsn(string $dsn): string
{
    if (! str_contains($dsn, '://')) {
        return $dsn;
    }

    $u = parse_url($dsn);
    if (! is_array($u) || ($u['host'] ?? '') === '') {
        return '<unparsable-dsn>';
    }

    $scheme = strtolower((string) ($u['scheme'] ?? 'tcp'));
    $user = (string) ($u['user'] ?? '');
    $host = (string) $u['host'];
    $port = isset($u['port']) ? ':'.(int) $u['port'] : '';
    $path = (string) ($u['path'] ?? '');

    return sprintf('%s://%s%s%s%s', $scheme, $user !== '' ? $user.'@' : '', $host, $port, $path);
}
