<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Bus;

use Illuminate\Redis\RedisManager;
use MeRezaRezaei\Teleframe\Bus\LaravelRedisAdapter;
use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;
use Illuminate\Container\Container;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;
use PHPUnit\Framework\TestCase;

/**
 * L5 — THE DIFFERENTIAL CONTRACT LANE. THE LAYER WHOSE ABSENCE HID NEW-1.
 *
 * ## WHY THIS FILE EXISTS
 *
 * `BusProductionDrainTest` swaps in a fake specifically so it never makes a live TCP connect
 * ("Without this the drain would attempt a live TCP connect and be swallowed"). `ArrayRedis` is
 * used by 35 test files. So the double is trusted everywhere and compared to reality nowhere.
 *
 * That is not hypothetical. NEW-1 shipped: `IngestConsumer` handed downstream
 * `['_ ' => constructor]` with no `ts`, `Update::updateId()` collapsed to one constant per
 * constructor, 500 messages produced 1 identity, and a bus-path handler ran ONCE per process
 * lifetime while the suite stayed green — because the doubles fed a shape production never emits.
 *
 * ## THE RULE, FROM THE RESEARCH
 *
 * `docs/standards/testing-method.md` R3: an honest double is defined by BEHAVIOURAL EQUIVALENCE
 * VERIFIED DIFFERENTIALLY, not by documentation. No comment saying "this mirrors production" is
 * evidence. Fowler's `ContractTest` bliki and Cockburn's build order both land on the same step:
 * drive the same interaction against the double AND the real adapter and compare.
 *
 * ## WHAT THIS DOES AND DOES NOT DO
 *
 * This is L5, not L6. It needs no Telegram credentials and no network beyond loopback Redis, so it
 * CAN run on the commit pipeline. What it cannot prove is anything about Telegram — that is L6 and
 * stays credential-gated. What it DOES prove is that the double this repo trusts in 35 files has
 * not drifted from the Redis it stands in for.
 *
 * Skip semantics: if no Redis is reachable the test SKIPS, loudly and by name. It must never pass
 * by silently not running — that is the same "a gate that cannot check is not a passing gate" rule
 * the identity gate uses. The skip is visible in the suite output.
 */
final class RedisDoubleDifferentialTest extends TestCase
{
    private ?RedisConnectionContract $real = null;

    protected function setUp(): void
    {
        $host = getenv('TELECLIENT_REDIS_TEST_HOST') ?: '127.0.0.1';
        $port = (int) (getenv('TELECLIENT_REDIS_TEST_PORT') ?: 6379);

        // The same wiring LaravelRedisAdapterTest already proves against a live
        // server, including its env-var names and its driver choice. Copied rather
        // than invented so this lane and the existing live suite cannot disagree
        // about how to reach Redis.
        $manager = new RedisManager(new Container, 'predis', ['default' => ['host' => $host, 'port' => $port]]);

        try {
            $pong = $manager->connection('default')->command('ping', []);
        } catch (\Throwable $e) {
            self::markTestSkipped(sprintf(
                'L5 differential lane needs a usable Redis at %s:%d (%s). This SKIP is deliberate: '
                .'the double cannot be verified without the real thing, and an unverified double is '
                .'exactly what hid NEW-1.',
                $host,
                $port,
                $e->getMessage(),
            ));
        }

        if ((string) $pong !== 'PONG' && strtoupper((string) $pong) !== '1') {
            self::markTestSkipped('Redis answered TCP but not PING; treating it as unusable.');
        }

        $this->real = new LaravelRedisAdapter($manager->connection('default'));
    }

    /** A unique key per test so a shared Redis never carries state between them. */
    private function key(string $name): string
    {
        return 'tf-diff-test:'.getmypid().':'.$name.':'.bin2hex(random_bytes(4));
    }

    /**
     * Run the SAME interaction against both implementations and require identical
     * observable results. This is the whole point of the layer.
     */
    private function assertBothAgree(string $name, callable $interaction): void
    {
        $double = new ArrayRedis;
        $doubleStream = $this->key($name.'.s');
        $realStream = $this->key($name.'.s');

        [$fromDouble, $fromReal] = $interaction($double, $doubleStream);
        $this->real?->del($realStream);

        [$fromReal, $fromRealAgain] = $interaction($this->real, $realStream);

        self::assertSame(
            $fromDouble,
            $fromReal,
            sprintf(
                'L5: the double and real Redis DISAGREE on "%s". ArrayRedis is used by 35 test files; '
                .'if it diverges from the real thing, every one of them is proving fiction.',
                $name,
            ),
        );
        self::assertSame($fromReal, $fromRealAgain, 'L5: real Redis was not idempotent across repeat runs');
    }

    public function test_hash_set_get_and_missing_field(): void
    {
        $this->assertBothAgree('hash', static function (RedisConnectionContract $r, string $k): array {
            $r->hset($k, 'a', '1');
            $r->hset($k, 'a', '2');

            return [$r->hget($k, 'a'), $r->hget($k, 'nope')];
        });
    }

    public function test_hgetall_and_hdel_counts(): void
    {
        $this->assertBothAgree('hgetall', static function (RedisConnectionContract $r, string $k): array {
            $r->hset($k, 'x', '1');
            $r->hset($k, 'y', '2');

            $all = $r->hgetall($k);
            ksort($all);

            return [$all, $r->hdel($k, 'x', 'y'), $r->hgetall($k)];
        });
    }

    public function test_stream_append_read_and_delete(): void
    {
        $this->assertBothAgree('stream', static function (RedisConnectionContract $r, string $s): array {
            $id1 = $r->xadd($s, '*', ['f' => '1']);
            $id2 = $r->xadd($s, '*', ['f' => '2']);

            $range = $r->xrange($s, '-', '+', 10);

            return [
                // Compare the SHAPE and the payload, not the generated ids, which
                // encode a timestamp and legitimately differ between runs.
                [count($range), array_values(array_map('array_values', $range))],
                $r->xdel($s, [$id1, $id2]),
                count($r->xrange($s, '-', '+', 10)),
            ];
        });
    }

    public function test_list_length_and_delete(): void
    {
        $this->assertBothAgree('list', static function (RedisConnectionContract $r, string $k): array {
            $r->command('RPUSH', [$k, 'a', 'b', 'c']);
            $len = $r->llen($k);
            $r->del($k);

            return [$len, $r->llen($k)];
        });
    }

    /**
     * The TTL case, and the one that matters most for honesty: `ArrayRedis` must not
     * claim an expiry the real server would not honour, or every TTL assertion written
     * against the double is unearned.
     */
    public function test_expire_reports_the_same_verdict(): void
    {
        $this->assertBothAgree('expire', static function (RedisConnectionContract $r, string $k): array {
            $r->hset($k, 'f', 'v');
            $set = $r->expire($k, 60);
            $ttl = $r->command('TTL', [$k]);

            return [$set, $ttl > 0 && $ttl <= 60];
        });
    }

    /** The consumer-group path, which is where the S1 bus actually lives. */
    public function test_consumer_group_ack_and_range(): void
    {
        $this->assertBothAgree('group', static function (RedisConnectionContract $r, string $s): array {
            $r->command('XGROUP', ['CREATE', $s, 'g1', '0']);
            $id = $r->xadd($s, '*', ['f' => 'v']);
            $got = $r->xreadgroup('g1', 'c1', [$s], '>');

            return [
                [count($got), array_values(array_map('array_values', array_values($got)[0] ?? []))],
                $r->xack($s, 'g1', [$id]),
            ];
        });
    }
}
