<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core\MTProto\Transport;

use MeRezaRezaei\Teleframe\Core\MTProto\Transport\Obfuscated2Cipher;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\RelayServer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Raw-TCP relay guard (finding W-10): the tcp leg authenticates nobody, so
 * a non-loopback bind without a shared secret is an anonymous open proxy —
 * and even a loopback relay with a configured secret must demand the
 * pre-header HMAC acceptance token before it proxies a single byte.
 *
 * Cases: non-loopback binds are refused at listen() when no secret is set
 * (pure in-process); a connection without the token is dropped before the
 * upstream resolver runs (forked child, resolver probes via a socket pair);
 * a connection carrying the correct token (RelayServer::acceptToken)
 * reaches the upstream with a round-trip frame. Bounded and deterministic.
 */
final class RelayTcpGuardW10Test extends TestCase
{
    private const DEADLINE_S = 1.2;

    private const POLL_INTERVAL_US = 1000;

    // vendor/ is a symlink to the main tree, so vendor/autoload.php resolves
    // RelayServer/Obfuscated2Cipher to the main tree's unfixed src/. Pin this
    // tree's sources before any test body autoloads the classes.
    public static function setUpBeforeClass(): void
    {
        require dirname(__DIR__, 4).'/tests/Schema/FixesAutoload.php';
    }

    public function test_is_loopback_host_classifies_bind_hosts(): void
    {
        self::assertTrue(RelayServer::isLoopbackHost('127.0.0.1'), '127.0.0.1 is loopback and must be allowed to bind without a secret');
        self::assertTrue(RelayServer::isLoopbackHost('127.8.9.10'), 'the whole 127.0.0.0/8 block is loopback');
        self::assertTrue(RelayServer::isLoopbackHost('localhost'), 'localhost must resolve as loopback');
        self::assertTrue(RelayServer::isLoopbackHost('::1'), 'the IPv6 loopback ::1 must be allowed');
        self::assertFalse(RelayServer::isLoopbackHost('0.0.0.0'), '0.0.0.0 binds EVERY interface and is NOT loopback');
        self::assertFalse(RelayServer::isLoopbackHost('::'), 'the IPv6 wildcard is NOT loopback');
        self::assertFalse(RelayServer::isLoopbackHost('10.0.0.1'), 'a private LAN address is NOT loopback');
        self::assertFalse(RelayServer::isLoopbackHost('203.0.113.7'), 'a routable public address is NOT loopback');
    }

    public function test_non_loopback_bind_without_a_secret_is_refused(): void
    {
        $relay = new RelayServer('0.0.0.0', 8443);

        try {
            $relay->listen();
            self::fail('a secret-less non-loopback bind must be refused');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('without an accept secret', $e->getMessage());
        }
    }

    public function test_loopback_bind_without_a_secret_still_serves(): void
    {
        $relay = new RelayServer('127.0.0.1', 0);
        $listen = $relay->listen();
        self::assertIsResource($listen);
        fclose($listen);
    }

    public function test_accept_token_is_deterministic_and_length_stable(): void
    {
        $token1 = RelayServer::acceptToken('a-secret');
        $token2 = RelayServer::acceptToken('a-secret');
        $other = RelayServer::acceptToken('b-secret');

        self::assertSame($token1, $token2, 'the same secret yields the same token');
        self::assertNotSame($token1, $other, 'different secrets yield different tokens');
        self::assertSame(RelayServer::AUTH_TOKEN_BYTES, strlen($token1));
    }

    public function test_connection_without_the_token_is_dropped_before_upstream(): void
    {
        if (! function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl_fork required for the forked relay child');
        }

        // The resolver would run only after the token passed; it signals by
        // writing a byte on the upstream-probe end.
        [$probeRelay, $probeTest] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        $relay = new RelayServer('127.0.0.1', 0, static function () use ($probeRelay) {
            @fwrite($probeRelay, "\x01");
            @fclose($probeRelay);

            return null;
        }, null, 'relay-accept-secret');

        $listen = $relay->listen();
        $port = $this->listeningPort($listen);

        $client = null;
        $pid = pcntl_fork();
        if ($pid === -1) {
            self::markTestSkipped('pcntl_fork failed');
        }

        if ($pid === 0) {
            fclose($probeTest);
            $relay->serveOnce($listen);
            self::childExit();
        }

        fclose($probeRelay);
        try {
            $client = stream_socket_client("tcp://127.0.0.1:{$port}", $errno, $errstr, 2.0);
            self::assertIsResource($client, "client connect failed: {$errstr} ({$errno})");

            // 64 wrong bytes — an unknown opener, not the acceptance token.
            $this->writeAll($client, str_repeat('X', RelayServer::AUTH_TOKEN_BYTES));

            $deadline = microtime(true) + self::DEADLINE_S;
            while (microtime(true) < $deadline) {
                @stream_set_blocking($client, false);
                $byte = fread($client, 1);
                @stream_set_blocking($client, true);
                if ($byte === false) {
                    break;
                }
                if ($byte === '') {
                    self::assertSame('', $this->readProbe($probeTest, $deadline), 'upstream resolver must never run for a missing token');

                    return;
                }
                usleep(self::POLL_INTERVAL_US);
            }

            self::fail('relay did not drop the token-less client');
        } finally {
            if (is_resource($client)) {
                fclose($client);
            }
            fclose($probeTest);
            fclose($listen);
            $this->reapChild($pid);
        }
    }

    public function test_connection_with_the_correct_token_roundtrips_a_frame(): void
    {
        if (! function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl_fork required for the forked relay child');
        }

        $header = Obfuscated2CodecTest::clientHeader(5, 'intermediate');
        $payload = 'guarded-relay-ping';

        [$upstreamRelay, $upstreamTest] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        $relay = new RelayServer('127.0.0.1', 0, static function (int $dcId, string $protocol) use ($upstreamRelay) {
            return $dcId === 5 && $protocol === 'intermediate' ? $upstreamRelay : null;
        }, null, 'relay-accept-secret');

        $listen = $relay->listen();
        $port = $this->listeningPort($listen);

        $client = null;
        $pid = pcntl_fork();
        if ($pid === -1) {
            self::markTestSkipped('pcntl_fork failed');
        }

        if ($pid === 0) {
            fclose($upstreamTest);
            $relay->serveOnce($listen);
            self::childExit();
        }

        fclose($upstreamRelay);
        try {
            $client = stream_socket_client("tcp://127.0.0.1:{$port}", $errno, $errstr, 2.0);
            self::assertIsResource($client, "client connect failed: {$errstr} ({$errno})");

            $clientCipher = Obfuscated2Cipher::fromHeader($header);

            // The acceptance token precedes the obfuscated2 header verbatim.
            $frame = pack('V', strlen($payload)).$payload;
            $this->writeAll($client, RelayServer::acceptToken('relay-accept-secret'));
            $this->writeAll($client, $header);
            $this->writeAll($client, $clientCipher->xcrypt($frame, true));

            $deadline = microtime(true) + self::DEADLINE_S;
            self::assertSame($payload, $this->readIntermediateFrame($upstreamTest, $deadline));
        } finally {
            if (is_resource($client)) {
                fclose($client);
            }
            fclose($upstreamTest);
            fclose($listen);
            $this->reapChild($pid);
        }
    }

    /** @param resource $server */
    private function listeningPort($server): int
    {
        $addr = stream_socket_get_name($server, false);
        if ($addr === false) {
            self::fail('could not read the listen address');
        }
        [, $port] = explode(':', $addr);

        return (int) $port;
    }

    /**
     * Reads a plain intermediate frame (4-byte LE length + payload) from $socket.
     *
     * @param  resource  $socket
     */
    private function readIntermediateFrame($socket, float $deadline): string
    {
        $length = unpack('V', $this->readExact($socket, 4, $deadline))[1];

        return $this->readExact($socket, $length, $deadline);
    }

    /**
     * @param  resource  $socket
     */
    private function readExact($socket, int $length, float $deadline): string
    {
        @stream_set_blocking($socket, false);
        $buffer = '';
        while (strlen($buffer) < $length) {
            if (microtime(true) >= $deadline) {
                throw new RuntimeException('RelayTcpGuardW10Test: timed out reading '.$length.' bytes from the socket');
            }
            $chunk = @fread($socket, $length - strlen($buffer));
            if ($chunk === false || $chunk === '') {
                usleep(self::POLL_INTERVAL_US);

                continue;
            }
            $buffer .= $chunk;
        }

        return $buffer;
    }

    /**
     * @param  resource  $socket
     */
    private function readProbe($socket, float $deadline): string
    {
        @stream_set_blocking($socket, false);
        $data = '';
        while (microtime(true) < $deadline && strlen($data) < 1) {
            $chunk = @fread($socket, 1);
            if ($chunk !== false && $chunk !== '') {
                $data .= $chunk;

                continue;
            }
            usleep(self::POLL_INTERVAL_US);
        }

        return $data;
    }

    /** @param resource $socket */
    private function writeAll($socket, string $bytes): void
    {
        $total = strlen($bytes);
        $written = 0;
        while ($written < $total) {
            $n = @fwrite($socket, substr($bytes, $written));
            if ($n === false || $n === 0) {
                throw new RuntimeException('RelayTcpGuardW10Test: write failed at offset '.$written);
            }
            $written += $n;
        }
    }

    /**
     * Reaps the relay child, hard-killing it on timeout so CI never hangs.
     */
    private function reapChild(int $pid): void
    {
        $deadline = microtime(true) + 1.0;
        while (microtime(true) < $deadline) {
            $status = null;
            $reaped = pcntl_waitpid($pid, $status, WNOHANG);
            if ($reaped === $pid) {
                self::assertSame(0, pcntl_wexitstatus($status) ?? -1, 'relay child must exit cleanly');

                return;
            }
            if ($reaped === -1) {
                return;
            }
            usleep(self::POLL_INTERVAL_US * 5);
        }

        @posix_kill($pid, SIGKILL);
        pcntl_waitpid($pid, $status);

        self::fail('relay child did not exit within the deadline');
    }

    /**
     * Terminates the forked relay child WITHOUT PHP's shutdown sequence
     * (see RelayServerTest::childExit for the multi-threaded rationale).
     */
    private static function childExit(): never
    {
        if (extension_loaded('FFI')) {
            try {
                $libc = \FFI::cdef('void _exit(int status);', 'libc.so.6');
                $libc->_exit(0);
            } catch (\Throwable) {
                // FFI disabled or this is not a glibc host — exit normally.
            }
        }

        exit(0);
    }
}
