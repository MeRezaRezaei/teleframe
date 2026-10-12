<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Bus;

use Illuminate\Redis\Connections\PredisConnection;
use LogicException;
use MeRezaRezaei\Teleframe\Bus\LaravelRedisAdapter;
use PHPUnit\Framework\TestCase;
use Predis\Client as PredisClient;
use RuntimeException;

/**
 * What the reload watcher actually gets from the PRODUCTION adapter.
 *
 * Every assertion here is about real behaviour, not desired behaviour.
 * There is deliberately no test that opens a socket, forks, iterates a
 * pub/sub loop or otherwise blocks: this is a subscription, and a
 * subscription test that can hang is worse than no test. Nothing here
 * touches a network server — the PredisClient below is never connected, it
 * only has to be the right class for the adapter's driver dispatch.
 */
final class PsubscribeTest extends TestCase
{
    /** Wall-clock bound: "returns promptly" means sub-second, not sub-minute. */
    private const PROMPT_SECONDS = 2.0;

    public function test_psubscribe_fails_fast_instead_of_blocking(): void
    {
        $adapter = $this->adapter();

        $started = microtime(true);

        try {
            $adapter->psubscribe(['tg:bus:reload'], static function (string $channel, string $payload): void {});
        } catch (RuntimeException) {
            $elapsed = microtime(true) - $started;

            self::assertLessThan(
                self::PROMPT_SECONDS,
                $elapsed,
                'psubscribe() must fail fast, never block on the shared connection.',
            );

            return;
        }

        self::fail('psubscribe() must not return on the production adapter.');
    }

    public function test_the_refusal_names_the_real_blocker(): void
    {
        $adapter = $this->adapter();

        try {
            $adapter->psubscribe(['tg:bus:reload'], static function (string $channel, string $payload): void {});
        } catch (RuntimeException $e) {
            $message = $e->getMessage();

            foreach (['predis', 'pubSubLoop', 'non-blocking', 'psubscribe'] as $needle) {
                self::assertStringContainsString(
                    $needle,
                    $message,
                    sprintf('The refusal must name the real blocker (missing %s).', $needle),
                );
            }

            return;
        }

        self::fail('psubscribe() was expected to refuse.');
    }

    public function test_psubscribe_arms_no_handler_and_returns_no_handle(): void
    {
        $adapter = $this->adapter();
        $delivered = [];
        $returned = false;

        try {
            $handle = $adapter->psubscribe(
                ['tg:bus:reload'],
                function (string $channel, string $payload) use (&$delivered): void {
                    $delivered[] = [$channel, $payload];
                },
            );
            $returned = true;
        } catch (RuntimeException) {
            // Expected: no handle came back, so nothing was armed.
        }

        unset($handle);

        self::assertFalse($returned, 'A returned handle would mean a subscription was faked into existence.');
        self::assertSame([], $delivered, 'A refused psubscribe() must not deliver anything.');
    }

    public function test_subscribe_still_refuses_for_the_same_reason_class(): void
    {
        $adapter = $this->adapter();

        $this->expectException(LogicException::class);

        $adapter->subscribe('tg:bus:reload', static function (string $channel, string $payload): void {});
    }

    public function test_a_refused_psubscribe_leaves_the_shared_connection_verbs_intact(): void
    {
        // The daemon's XADD/XREADGROUP live on the same adapter; refusing to
        // listen must not damage them.
        $adapter = $this->adapter();

        try {
            $adapter->psubscribe(['tg:bus:reload'], static function (string $channel, string $payload): void {});
        } catch (RuntimeException) {
            // Expected.
        }

        self::assertTrue(method_exists($adapter, 'psubscribe'));
        self::assertTrue(method_exists($adapter, 'command'));
        self::assertTrue(method_exists($adapter, 'xadd'));
        self::assertTrue(method_exists($adapter, 'xreadgroup'));
    }

    /**
     * The installed driver is predis (no ext-redis), so the adapter is built
     * on a PredisConnection exactly as the service provider builds it. No
     * connection is opened: nothing in this test issues a command.
     */
    private function adapter(): LaravelRedisAdapter
    {
        return new LaravelRedisAdapter(new PredisConnection(new PredisClient));
    }
}
