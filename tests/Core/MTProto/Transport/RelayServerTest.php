<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core\MTProto\Transport;

use MeRezaRezaei\Teleframe\Core\MTProto\Transport\Obfuscated2Cipher;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\RelayServer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Loopback relay test — NO external network, no real DCs, no credentials.
 *
 * A forked child runs the relay (single serveOnce) against the real listen
 * socket but points its upstream resolver at loop ends of a UNIX socket pair;
 * the parent plays BOTH the obfuscated2 client and the fake upstream peer:
 * it encrypts an intermediate-framed plaintext, asserts the decrypted frame
 * arrives verbatim on the upstream leg, writes a framed reply back, and
 * asserts the client receives it back correctly re-encrypted. Bounded and
 * deterministic (deadline-guarded reads) so a stuck child can never hang CI.
 */
final class RelayServerTest extends TestCase
{
    private const DEADLINE_S = 1.2;

    private const POLL_INTERVAL_US = 1000;

    public function test_relay_roundtrips_a_header_with_no_dc_id_via_the_default_dc(): void
    {
        if (! function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl_fork required for the forked relay child');
        }

        // The vendored Telegram Web client writes NO dc id — bytes 60-62 of
        // the header stay random, decoding out of the accepted range — so
        // the relay must route via its configured default DC.
        $header = $this->syntheticHeader(0xFEDC, 'intermediate');
        $payload = 'default-dc-ping';

        [$upstreamRelay, $upstreamTest] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        $relay = new RelayServer(
            '127.0.0.1',
            0,
            static function (int $dcId, string $protocol) use ($upstreamRelay) {
                return $dcId === 2 && $protocol === 'intermediate' ? $upstreamRelay : null;
            },
            2,
        );

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

            $frame = pack('V', strlen($payload)).$payload;
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

    public function test_relay_roundtrips_an_intermediate_frame_through_the_loopback(): void
    {
        if (! function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl_fork required for the forked relay child');
        }

        $header = $this->syntheticHeader(5, 'intermediate');
        $payload = 'relay-ping-frame-0123456789abcdef-ABCDEFGH';
        $reply = 'relay-pong-frame-9876543210';

        [$upstreamRelay, $upstreamTest] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        $relay = new RelayServer('127.0.0.1', 0, static function (int $dcId, string $protocol) use ($upstreamRelay) {
            return $dcId === 5 && $protocol === 'intermediate' ? $upstreamRelay : null;
        });

        $listen = $relay->listen();
        $port = $this->listeningPort($listen);

        $client = null;
        $pid = pcntl_fork();
        if ($pid === -1) {
            self::markTestSkipped('pcntl_fork failed');
        }

        if ($pid === 0) {
            // Child: terminate the client. Runs serveOnce to completion (the
            // parent closes the client at the end), then exits without ever
            // touching PHPUnit.
            fclose($upstreamTest);
            $relay->serveOnce($listen);
            self::childExit();
        }

        fclose($upstreamRelay);
        try {
            $client = stream_socket_client("tcp://127.0.0.1:{$port}", $errno, $errstr, 2.0);
            self::assertIsResource($client, "client connect failed: {$errstr} ({$errno})");

            $clientCipher = Obfuscated2Cipher::fromHeader($header);

            // Client → relay → upstream: encrypted intermediate frame.
            $frame = pack('V', strlen($payload)).$payload;
            $this->writeAll($client, $header);
            $this->writeAll($client, $clientCipher->xcrypt($frame, true));

            // The fake upstream receives the DECRYPTED frame, verbatim.
            $deadline = microtime(true) + self::DEADLINE_S;
            self::assertSame($payload, $this->readIntermediateFrame($upstreamTest, $deadline));

            // Upstream → relay → client: framed plaintext reply, re-encrypted.
            $replyFrame = pack('V', strlen($reply)).$reply;
            $this->writeAll($upstreamTest, $replyFrame);

            [$replyLen, $replyBody] = $this->readEncryptedIntermediateFrame($client, $clientCipher, $deadline);
            self::assertSame(strlen($reply), $replyLen);
            self::assertSame($reply, $replyBody);
        } finally {
            if (is_resource($client)) {
                fclose($client);
            }
            fclose($upstreamTest);
            fclose($listen);
            $this->reapChild($pid);
        }
    }

    public function test_invalid_header_is_dropped_without_touching_the_upstream(): void
    {
        if (! function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl_fork required for the forked relay child');
        }

        // The resolver would be invoked if (and only if) the header parsed;
        // it signals by writing a byte on the upstream-probe end.
        [$probeRelay, $probeTest] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        $relay = new RelayServer('127.0.0.1', 0, static function () use ($probeRelay) {
            @fwrite($probeRelay, "\x01");
            @fclose($probeRelay);

            return null;
        });

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

            // Flip a bit inside the encrypted tail — the decrypted tag
            // becomes garbage, which is what a non-obfuscated2 connection
            // (or corruption) looks like.
            $bogus = $this->syntheticHeader(5, 'intermediate');
            $bogus[56] = $bogus[56] ^ "\xFF";
            $this->writeAll($client, $bogus);

            // The relay must close the client without ever calling the resolver.
            $deadline = microtime(true) + self::DEADLINE_S;
            while (microtime(true) < $deadline) {
                @stream_set_blocking($client, false);
                $byte = fread($client, 1);
                @stream_set_blocking($client, true);
                if ($byte === false) {
                    break;
                }
                if ($byte === '') {
                    self::assertSame('', $this->readProbe($probeTest, $deadline), 'upstream resolver must never run for a bad header');

                    return;
                }
                usleep(self::POLL_INTERVAL_US);
            }

            self::fail('relay did not drop the invalid-header client');
        } finally {
            if (is_resource($client)) {
                fclose($client);
            }
            fclose($probeTest);
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
     * Reads the ENCRYPTED frame the relay returned to the client and decrypts
     * it in place (4 encrypted length bytes, then the encrypted payload).
     *
     * @param  resource  $socket
     * @return array{int, string}
     */
    private function readEncryptedIntermediateFrame($socket, Obfuscated2Cipher $cipher, float $deadline): array
    {
        $length = unpack('V', $cipher->xcrypt($this->readExact($socket, 4, $deadline), false))[1];

        return [$length, $cipher->xcrypt($this->readExact($socket, $length, $deadline), false)];
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
                throw new RuntimeException('RelayServerTest: timed out reading '.$length.' bytes from the socket');
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
                throw new RuntimeException('RelayServerTest: write failed at offset '.$written);
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
     * Terminates the forked relay child WITHOUT PHP's shutdown sequence.
     *
     * This environment's PHP build is multi-threaded (a bundled extension
     * keeps a background thread holding private futexes). A child forked from
     * such a process deadlocks forever in a futex inherited locked when
     * exit() runs PHP's shutdown, so pcntl_waitpid never reaps it. The child
     * has finished its work at this point, so only the raw exit status
     * matters: bypass PHP shutdown with libc _exit(0) via FFI when available,
     * falling back to a plain exit(0) on builds where FFI is not usable.
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

    /**
     * Builds a client-shaped 64-byte obfuscated2 header (GramJS initHeader
     * semantics): nonce at 0-8, raw key window at 8-56, tag+dcId+padding
     * encrypted under the from-client keystream at offset 56.
     */
    private function syntheticHeader(int $dcId, string $protocol): string
    {
        return Obfuscated2CodecTest::clientHeader($dcId, $protocol);
    }
}
