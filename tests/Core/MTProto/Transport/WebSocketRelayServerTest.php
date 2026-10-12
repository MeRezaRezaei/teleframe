<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core\MTProto\Transport;

use MeRezaRezaei\Teleframe\Core\MTProto\Transport\Obfuscated2Cipher;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\WebSocketFrameCodec;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\WebSocketRelayServer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Loopback WebSocket relay test — NO external network, no real DCs, no
 * credentials.
 *
 * A forked child runs the WS terminal (single serveOnce) against the real
 * listen socket but points its upstream resolver at one end of a UNIX
 * socket pair; the parent plays BOTH the browser client (RFC6455 handshake,
 * then obfuscated2 header + intermediate-framed plaintext inside masked WS
 * binary messages) and the fake upstream peer. It asserts the decrypted
 * frame arrives verbatim on the upstream leg, writes a framed reply back,
 * and asserts the client receives it correctly re-encrypted and re-framed
 * as an unmasked WS binary message. Bounded and deterministic
 * (deadline-guarded reads) so a stuck child can never hang CI.
 */
final class WebSocketRelayServerTest extends TestCase
{
    private const DEADLINE_S = 1.2;

    public function test_ws_relay_roundtrips_an_intermediate_frame_through_the_loopback(): void
    {
        if (! function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl_fork required for the forked relay child');
        }

        $header = Obfuscated2CodecTest::clientHeader(5, 'intermediate');
        $payload = 'ws-relay-ping-frame-0123456789abcdef-ABCDEFGH';
        $reply = 'ws-relay-pong-frame-9876543210';

        [$upstreamRelay, $upstreamTest] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        $relay = new WebSocketRelayServer(
            '127.0.0.1',
            0,
            static function (int $dcId, string $protocol) use ($upstreamRelay) {
                return $dcId === 5 && $protocol === 'intermediate' ? $upstreamRelay : null;
            },
        );

        $listen = $relay->listen();
        $port = $this->listeningPort($listen);

        $client = null;
        $pid = pcntl_fork();
        if ($pid === -1) {
            self::markTestSkipped('pcntl_fork failed');
        }

        if ($pid === 0) {
            // Child: terminate the WS client. Runs serveOnce to completion
            // (the parent closes the client at the end), then exits without
            // ever touching PHPUnit.
            fclose($upstreamTest);
            $relay->serveOnce($listen);
            self::childExit();
        }

        fclose($upstreamRelay);
        try {
            $client = stream_socket_client("tcp://127.0.0.1:{$port}", $errno, $errstr, 2.0);
            self::assertIsResource($client, "client connect failed: {$errstr} ({$errno})");

            $this->performHandshake($client);
            $clientCipher = Obfuscated2Cipher::fromHeader($header);

            // Client → relay → upstream: the 64-byte obfuscated2 header and
            // one encrypted intermediate frame (length-prefixed plaintext),
            // each as a MASKED WS binary message (RFC6455 client framing).
            $frame = pack('V', strlen($payload)).$payload;
            $this->sendBinary($client, $header);
            $this->sendBinary($client, $clientCipher->xcrypt($frame, true));

            // The fake upstream receives the DECRYPTED frame, verbatim.
            $deadline = microtime(true) + self::DEADLINE_S;
            self::assertSame($payload, $this->readIntermediateFrame($upstreamTest, $deadline));

            // Upstream → relay → client: framed plaintext reply, re-encrypted
            // and re-framed as an unmasked WS binary message.
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

    public function test_ws_relay_routes_a_header_with_no_dc_id_via_the_default_dc(): void
    {
        if (! function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl_fork required for the forked relay child');
        }

        // The vendored Telegram Web client writes NO dc id — bytes 60-62 of
        // the header stay random, decoding out of the accepted range — so
        // the terminal must route via its configured default DC (same as
        // RelayServer, now over WebSocket).
        $header = Obfuscated2CodecTest::clientHeader(0xFECB, 'intermediate');
        $payload = 'ws-default-dc-ping';

        [$upstreamRelay, $upstreamTest] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        $relay = new WebSocketRelayServer(
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

            $this->performHandshake($client);
            $clientCipher = Obfuscated2Cipher::fromHeader($header);

            $frame = pack('V', strlen($payload)).$payload;
            $this->sendBinary($client, $header);
            $this->sendBinary($client, $clientCipher->xcrypt($frame, true));

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

    public function test_invalid_ws_handshake_is_dropped_without_touching_the_upstream(): void
    {
        if (! function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl_fork required for the forked relay child');
        }

        // The resolver would be invoked if (and only if) the handshake
        // succeeded AND the header parsed; it signals by writing a byte.
        [$probeRelay, $probeTest] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        $relay = new WebSocketRelayServer('127.0.0.1', 0, static function () use ($probeRelay) {
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

            // A plain HTTP GET without websocket upgrade headers is not a WS
            // client — the terminal must drop it without a resolver call.
            $this->writeAll($client, "GET / HTTP/1.1\r\nHost: example.com\r\nConnection: close\r\n\r\n");

            $deadline = microtime(true) + self::DEADLINE_S;
            while (microtime(true) < $deadline) {
                @stream_set_blocking($client, false);
                $byte = fread($client, 1);
                $closed = $byte === false || $byte === '';
                @stream_set_blocking($client, true);
                if ($byte !== false && $byte !== '') {
                    continue;
                }
                if ($closed) {
                    self::assertSame('', $this->readProbe($probeTest, $deadline), 'upstream resolver must never run for a bad handshake');

                    return;
                }
                usleep(1000);
            }

            self::fail('relay did not drop the invalid-handshake client');
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
     * Performs the RFC6455 client side of the handshake against the relay.
     *
     * @param  resource  $socket
     */
    private function performHandshake($socket): void
    {
        // GramJS-shape: offers the `binary` subprotocol MTProto's WebSocket
        // transport requires and the server must echo.
        $key = base64_encode(random_bytes(16));
        $this->writeAll($socket, "GET /apiws HTTP/1.1\r\n"
            ."Host: 127.0.0.1\r\n"
            ."Upgrade: websocket\r\n"
            ."Connection: Upgrade\r\n"
            ."Sec-WebSocket-Key: {$key}\r\n"
            ."Sec-WebSocket-Protocol: binary\r\n"
            ."Sec-WebSocket-Version: 13\r\n"
            ."\r\n");

        $response = '';
        $deadline = microtime(true) + 2.0;
        while (! str_contains($response, "\r\n\r\n")) {
            if (microtime(true) >= $deadline) {
                throw new RuntimeException('WebSocketRelayServerTest: handshake response timed out');
            }
            $chunk = @fread($socket, 8192);
            if ($chunk === false || $chunk === '') {
                usleep(1000);

                continue;
            }
            $response .= $chunk;
        }

        self::assertStringStartsWith('HTTP/1.1 101', $response, 'relay must answer 101 Switching Protocols');
        self::assertStringContainsString(
            WebSocketFrameCodec::acceptKey($key),
            $response,
            'relay must echo the computed Sec-WebSocket-Accept',
        );
        self::assertStringContainsString(
            'Sec-WebSocket-Protocol: binary',
            $response,
            'relay must echo the negotiated binary subprotocol',
        );
    }

    /**
     * Sends $bytes as one masked WS binary message (RFC6455 client framing).
     *
     * @param  resource  $socket
     */
    private function sendBinary($socket, string $bytes): void
    {
        $this->writeAll($socket, WebSocketFrameCodec::frame(WebSocketFrameCodec::OPCODE_BINARY, $bytes, mask: true));
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
     * Reads the ENCRYPTED, WS-framed reply the relay returned to the client
     * (one unmasked binary message wrapping the encrypted intermediate
     * frame), decrypts it in place, and returns the plaintext length + body.
     *
     * @param  resource  $socket
     * @return array{int, string}
     */
    private function readEncryptedIntermediateFrame($socket, Obfuscated2Cipher $cipher, float $deadline): array
    {
        $wire = '';
        $frame = null;
        while ($frame === null) {
            if (microtime(true) >= $deadline) {
                throw new RuntimeException('WebSocketRelayServerTest: WS frame timed out');
            }
            $chunk = @fread($socket, 8192);
            if ($chunk === false || $chunk === '') {
                usleep(1000);

                continue;
            }
            $wire .= $chunk;
            $frame = WebSocketFrameCodec::parseFrame($wire);
        }

        self::assertFalse($frame['masked'], 'server frames must NOT be masked');
        self::assertSame(WebSocketFrameCodec::OPCODE_BINARY, $frame['opcode']);

        $encrypted = $frame['payload'];
        $prefix = $cipher->xcrypt(substr($encrypted, 0, 4), false);
        $length = unpack('V', $prefix)[1];

        return [$length, $cipher->xcrypt(substr($encrypted, 4), false)];
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
                throw new RuntimeException('WebSocketRelayServerTest: timed out reading '.$length.' bytes from the socket');
            }
            $chunk = @fread($socket, $length - strlen($buffer));
            if ($chunk === false || $chunk === '') {
                usleep(1000);

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
            usleep(1000);
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
                throw new RuntimeException('WebSocketRelayServerTest: write failed at offset '.$written);
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
            usleep(5000);
        }

        @posix_kill($pid, SIGKILL);
        pcntl_waitpid($pid, $status);

        self::fail('relay child did not exit within the deadline');
    }

    /**
     * Terminates the forked relay child WITHOUT PHP's shutdown sequence
     * (see RelayServerTest::childExit for why).
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
