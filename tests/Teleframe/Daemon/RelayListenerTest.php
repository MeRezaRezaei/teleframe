<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Teleframe\Daemon;

use MeRezaRezaei\Teleframe\Core\MTProto\Transport\RelayServer;
use MeRezaRezaei\Teleframe\Daemon\RelayListener;
use PHPUnit\Framework\TestCase;

/**
 * RelayListener lifecycle: binds the socket once, honours stop() promptly,
 * and run() exits when stopped — deterministic, no fork, no network.
 */
final class RelayListenerTest extends TestCase
{
    public function test_run_pass_surfaces_an_early_stop(): void
    {
        $listener = new RelayListener(new RelayServer('127.0.0.1', 0));

        $listener->stop();

        self::assertFalse($listener->runPass());
    }

    public function test_run_pass_reports_keep_alive_without_stop(): void
    {
        $listener = new RelayListener(new RelayServer('127.0.0.1', 0));

        // Binds the socket once and returns without needing a client.
        self::assertTrue($listener->runPass(0.01));

        $socket = $listener->listenSocket();
        self::assertIsResource($socket);
        self::assertNotFalse(stream_socket_get_name($socket, false));
    }

    public function test_listen_socket_is_bound_once(): void
    {
        $listener = new RelayListener(new RelayServer('127.0.0.1', 0));

        $first = $listener->listenSocket();
        $second = $listener->listenSocket();

        self::assertSame($first, $second, 'the listen socket must be bound exactly once');
    }

    public function test_run_exits_promptly_once_stopped(): void
    {
        $listener = new RelayListener(new RelayServer('127.0.0.1', 0));

        $listener->stop();
        $started = microtime(true);
        $listener->run(0.02);
        $elapsed = microtime(true) - $started;

        self::assertLessThan(1.0, $elapsed, 'run() must exit promptly after stop()');
    }
}
