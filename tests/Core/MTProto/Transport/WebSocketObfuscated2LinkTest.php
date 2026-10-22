<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core\MTProto\Transport;

use MeRezaRezaei\Teleframe\Core\MTProto\Transport\Obfuscated2Cipher;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\Obfuscated2Codec;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\WebSocketFrameCodec;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\WebSocketObfuscated2Link;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\WebSocketRelayServer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Loopback WebSocketObfuscated2Link tests — NO external network, no real
 * DCs, no credentials.
 *
 * A forked child plays the WS/obfuscated2 terminator: RFC6455 handshake,
 * obfuscated2 header over the WS byte stream, then it builds ONE
 * WebSocketObfuscated2Link (cipher from the parsed header + lead) and echoes
 * whatever readPlain() yields straight back with writePlain(). The parent
 * plays the browser client (handshake, header + encrypted payload inside
 * masked binary messages — header and payload sent in ONE write so the
 * encrypted payload arrives as the link's $lead) and decodes the re-encrypted,
 * re-framed reply. Bounded and deterministic (deadline-guarded reads) so a
 * stuck child can never hang CI.
 */
final class WebSocketObfuscated2LinkTest extends TestCase
{
    private const DEADLINE_S = 1.2;

    private const POLL_INTERVAL_US = 1000;

    public function test_link_roundtrips_plaintext_through_the_loopback_echo(): void
    {
        if (! function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl_fork required for the forked link child');
        }

        $header = Obfuscated2CodecTest::clientHeader(5, 'abridged');
        $plain = 'link-roundtrip-payload-0123456789-ABCDEFGH';
        [$probeRelay, $probeTest] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        $server = $this->listen();
        $port = $this->listeningPort($server);

        $client = null;
        $pid = pcntl_fork();
        if ($pid === -1) {
            self::markTestSkipped('pcntl_fork failed');
        }

        if ($pid === 0) {
            fclose($probeTest);
            $this->linkEchoServer($server, $probeRelay, false);
            self::childExit(0);
        }

        fclose($probeRelay);
        try {
            $client = stream_socket_client("tcp://127.0.0.1:{$port}", $errno, $errstr, 2.0);
            self::assertIsResource($client, "client connect failed: {$errstr} ({$errno})");

            $this->performHandshake($client);
            $clientCipher = Obfuscated2Cipher::fromHeader($header);

            // Header + one encrypted payload, both complete WS messages in a
            // single write: the terminator collects the 64-byte header and the
            // encrypted payload arrives as the link's $lead.
            $frame = $clientCipher->xcrypt($plain, true);
            $this->writeAll($client,
                WebSocketFrameCodec::frame(WebSocketFrameCodec::OPCODE_BINARY, $header, mask: true)
                .WebSocketFrameCodec::frame(WebSocketFrameCodec::OPCODE_BINARY, $frame, mask: true));

            $deadline = microtime(true) + self::DEADLINE_S;
            self::assertSame(
                $plain,
                $this->readOneEncryptedDataFrame($client, $clientCipher, $deadline),
                'echoed plaintext must survive the decrypt → encrypt round trip',
            );
            self::assertSame('', $this->readProbe($probeTest, $deadline), 'echo server must not report an error');
        } finally {
            if (is_resource($client)) {
                fclose($client);
            }
            fclose($probeTest);
            fclose($server);
            $this->reapChild($pid);
        }
    }

    public function test_link_readplain_returns_null_after_a_ws_close_frame(): void
    {
        if (! function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl_fork required for the forked link child');
        }

        $header = Obfuscated2CodecTest::clientHeader(5, 'abridged');
        $plain = 'close-frame-probe';
        [$probeRelay, $probeTest] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        $server = $this->listen();
        $port = $this->listeningPort($server);

        $client = null;
        $pid = pcntl_fork();
        if ($pid === -1) {
            self::markTestSkipped('pcntl_fork failed');
        }

        if ($pid === 0) {
            fclose($probeTest);
            // The echo server runs, then the SECOND readPlain must return null
            // once the parent sends a WS close frame.
            $this->linkEchoServer($server, $probeRelay, true);
            self::childExit(0);
        }

        fclose($probeRelay);
        try {
            $client = stream_socket_client("tcp://127.0.0.1:{$port}", $errno, $errstr, 2.0);
            self::assertIsResource($client, "client connect failed: {$errstr} ({$errno})");

            $this->performHandshake($client);
            $clientCipher = Obfuscated2Cipher::fromHeader($header);

            $frame = $clientCipher->xcrypt($plain, true);
            $this->writeAll($client,
                WebSocketFrameCodec::frame(WebSocketFrameCodec::OPCODE_BINARY, $header, mask: true)
                .WebSocketFrameCodec::frame(WebSocketFrameCodec::OPCODE_BINARY, $frame, mask: true));

            $deadline = microtime(true) + self::DEADLINE_S;
            self::assertSame($plain, $this->readOneEncryptedDataFrame($client, $clientCipher, $deadline));

            // RFC6455 close handshake initiator: one masked close frame.
            $this->writeAll($client, WebSocketFrameCodec::frame(WebSocketFrameCodec::OPCODE_CLOSE, '', mask: true));

            self::assertSame('N', $this->readProbe($probeTest, $deadline), 'readPlain must return null on a close frame');
            self::assertSame('', $this->readProbe($probeTest, $deadline), 'child must not signal an error');
        } finally {
            if (is_resource($client)) {
                fclose($client);
            }
            fclose($probeTest);
            fclose($server);
            $this->reapChild($pid);
        }
    }

    public function test_answerer_seam_serves_the_link_in_place_of_the_byte_proxy(): void
    {
        if (! function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl_fork required for the forked relay child');
        }

        $header = Obfuscated2CodecTest::clientHeader(5, 'intermediate');
        $plain = 'seam-echo-frame';
        [$probeRelay, $probeTest] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        $relay = new WebSocketRelayServer(
            '127.0.0.1',
            0,
            static function (int $dcId, string $protocol) use ($probeRelay) {
                @fwrite($probeRelay, 'R'); // must never fire on the answerer path

                return null;
            },
            null,
            static function ($client, Obfuscated2Cipher $cipher, string $lead, int $dcId, string $protocol) use ($probeRelay) {
                // The silhouette of VaultMtprotoAnswerer: one link, echo, done.
                @fwrite($probeRelay, $dcId === 5 && $protocol === 'intermediate' ? 'd' : '?');

                $link = new WebSocketObfuscated2Link($client, $cipher, $lead);
                $plain = $link->readPlain(5);
                if ($plain === null || $plain === '') {
                    @fwrite($probeRelay, 'C');

                    return;
                }
                $link->writePlain($plain);
                $link->close();
            },
        );

        $server = $relay->listen();
        $port = $this->listeningPort($server);

        $client = null;
        $pid = pcntl_fork();
        if ($pid === -1) {
            self::markTestSkipped('pcntl_fork failed');
        }

        if ($pid === 0) {
            fclose($probeTest);
            $relay->serveOnce($server);
            self::childExit();
        }

        fclose($probeRelay);
        try {
            $client = stream_socket_client("tcp://127.0.0.1:{$port}", $errno, $errstr, 2.0);
            self::assertIsResource($client, "client connect failed: {$errstr} ({$errno})");

            $this->performHandshake($client);
            $clientCipher = Obfuscated2Cipher::fromHeader($header);

            $frame = $clientCipher->xcrypt($plain, true);
            $this->writeAll($client,
                WebSocketFrameCodec::frame(WebSocketFrameCodec::OPCODE_BINARY, $header, mask: true)
                .WebSocketFrameCodec::frame(WebSocketFrameCodec::OPCODE_BINARY, $frame, mask: true));

            $deadline = microtime(true) + self::DEADLINE_S;
            self::assertSame($plain, $this->readOneEncryptedDataFrame($client, $clientCipher, $deadline));

            $probe = $this->readProbe($probeTest, $deadline);
            self::assertStringContainsString('d', $probe, 'answerer must be reached with the parsed dc id + protocol');
            self::assertStringNotContainsString('R', $probe, 'answerer path must never dial the upstream resolver');
        } finally {
            if (is_resource($client)) {
                fclose($client);
            }
            fclose($probeTest);
            fclose($server);
            $this->reapChild($pid);
        }
    }

    /**
     * The forked terminator: accept, handshake, collect the obfuscated2
     * header, build one WebSocketObfuscated2Link and echo one readPlain()
     * chunk with writePlain(). With $alsoExpectClose set, a second readPlain()
     * must return null when the peer sends a close frame — the result is
     * reported on $probeRelay ('N' = null seen, 'C' = first read failed).
     *
     * Runs inside the forked child: errors lower the exit path via
     * $probeRelay + a non-zero child exit, never PHPUnit assertions.
     *
     * @param  resource  $server
     * @param  resource  $probeRelay
     */
    private function linkEchoServer($server, $probeRelay, bool $alsoExpectClose): void
    {
        $conn = @stream_socket_accept($server, 30);
        if (! is_resource($conn)) {
            return;
        }

        $stream = $this->collectHeader($conn);
        if ($stream === null || strlen($stream) < Obfuscated2Codec::HEADER_SIZE) {
            @fwrite($probeRelay, 'C');

            return;
        }

        $cipher = Obfuscated2Cipher::fromHeader(substr($stream, 0, Obfuscated2Codec::HEADER_SIZE));
        $link = new WebSocketObfuscated2Link($conn, $cipher, substr($stream, Obfuscated2Codec::HEADER_SIZE));

        $first = $link->readPlain(5);
        if ($first === null || $first === '') {
            @fwrite($probeRelay, 'C');
            $link->close();

            return;
        }
        $link->writePlain($first);

        if ($alsoExpectClose) {
            $second = $link->readPlain(5);
            @fwrite($probeRelay, $second === null ? 'N' : 'X');
        }

        $link->close();
    }

    /**
     * Accepts the child's connection, runs the RFC6455 server handshake and
     * collects the obfuscated2 byte stream until at least 64 decoded bytes.
     *
     * @param  resource  $conn
     */
    private function collectHeader($conn): ?string
    {
        $request = '';
        $deadline = microtime(true) + self::DEADLINE_S;
        while (! str_contains($request, "\r\n\r\n")) {
            if (microtime(true) >= $deadline) {
                return null;
            }
            $chunk = @fread($conn, 8192);
            if ($chunk === false || $chunk === '') {
                usleep(self::POLL_INTERVAL_US);

                continue;
            }
            $request .= $chunk;
        }

        $handshake = WebSocketFrameCodec::parseHandshake($request);
        if ($handshake === null) {
            return null;
        }
        $this->writeAll($conn, WebSocketFrameCodec::handshakeResponse($handshake['key'], $handshake['subprotocol']));

        $carry = '';
        $stream = '';
        while (strlen($stream) < Obfuscated2Codec::HEADER_SIZE) {
            if (microtime(true) >= $deadline) {
                return $stream;
            }
            $chunk = @fread($conn, 8192);
            if ($chunk === false || $chunk === '') {
                usleep(self::POLL_INTERVAL_US);

                continue;
            }
            $carry .= $chunk;
            $stream .= $this->drainDataFrames($carry);
        }

        return $stream;
    }

    /**
     * @param  string  $carry  partial-frame wire bytes (in/out)
     */
    private function drainDataFrames(string &$carry): string
    {
        $out = '';
        for (; ;) {
            $frame = WebSocketFrameCodec::parseFrame($carry);
            if ($frame === null) {
                return $out;
            }
            $carry = substr($carry, $this->frameLength($frame));
            $opcode = $frame['opcode'];

            if (
                $opcode === WebSocketFrameCodec::OPCODE_BINARY
                || $opcode === WebSocketFrameCodec::OPCODE_CONTINUATION
            ) {
                $out .= $frame['payload'];
            }
        }
    }

    /** @param array{opcode: int, payload: string, masked: bool} $frame */
    private function frameLength(array $frame): int
    {
        $len = strlen($frame['payload']);
        $header = 2;
        if ($len >= 126 && $len <= 0xFFFF) {
            $header += 2;
        } elseif ($len > 0xFFFF) {
            $header += 8;
        }

        return $header + strlen($frame['payload']) + ($frame['masked'] ? 4 : 0);
    }

    /** @return resource */
    private function listen()
    {
        $server = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        self::assertIsResource($server, "link listen failed: {$errstr} ({$errno})");

        return $server;
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

    /** @param resource $socket */
    private function performHandshake($socket): void
    {
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
                throw new RuntimeException('WebSocketObfuscated2LinkTest: handshake response timed out');
            }
            $chunk = @fread($socket, 8192);
            if ($chunk === false || $chunk === '') {
                usleep(self::POLL_INTERVAL_US);

                continue;
            }
            $response .= $chunk;
        }

        self::assertStringStartsWith('HTTP/1.1 101', $response, 'terminator must answer 101 Switching Protocols');
        self::assertStringContainsString(
            WebSocketFrameCodec::acceptKey($key),
            $response,
            'terminator must echo the computed Sec-WebSocket-Accept',
        );
        self::assertStringContainsString(
            'Sec-WebSocket-Protocol: binary',
            $response,
            'terminator must echo the negotiated binary subprotocol',
        );
    }

    /**
     * Reads the single re-encrypted, re-framed WS binary message the link
     * wrote and decrypts it with the client's from-server pair (offset 0).
     *
     * @param  resource  $socket
     */
    private function readOneEncryptedDataFrame($socket, Obfuscated2Cipher $cipher, float $deadline): string
    {
        @stream_set_blocking($socket, false);
        $wire = '';
        $frame = null;
        while ($frame === null) {
            if (microtime(true) >= $deadline) {
                throw new RuntimeException('WebSocketObfuscated2LinkTest: reply frame timed out');
            }
            $chunk = @fread($socket, 8192);
            if ($chunk !== false && $chunk !== '') {
                $wire .= $chunk;
                $frame = WebSocketFrameCodec::parseFrame($wire);

                continue;
            }
            usleep(self::POLL_INTERVAL_US);
        }

        self::assertFalse($frame['masked'], 'link frames must NOT be masked');
        self::assertSame(WebSocketFrameCodec::OPCODE_BINARY, $frame['opcode']);

        return $cipher->xcrypt($frame['payload'], false);
    }

    /** @param resource $socket */
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
                throw new RuntimeException('WebSocketObfuscated2LinkTest: write failed at offset '.$written);
            }
            $written += $n;
        }
    }

    private function reapChild(int $pid): void
    {
        $deadline = microtime(true) + 1.0;
        while (microtime(true) < $deadline) {
            $status = null;
            $reaped = pcntl_waitpid($pid, $status, WNOHANG);
            if ($reaped === $pid) {
                self::assertSame(0, pcntl_wexitstatus($status) ?? -1, 'link child must exit cleanly');

                return;
            }
            if ($reaped === -1) {
                return;
            }
            usleep(self::POLL_INTERVAL_US * 5);
        }

        @posix_kill($pid, SIGKILL);
        pcntl_waitpid($pid, $status);

        self::fail('link child did not exit within the deadline');
    }

    private static function childExit(int $code = 0): never
    {
        if (extension_loaded('FFI')) {
            try {
                $libc = \FFI::cdef('void _exit(int status);', 'libc.so.6');
                $libc->_exit($code);
            } catch (\Throwable) {
                // FFI disabled or this is not a glibc host — exit normally.
            }
        }

        exit($code);
    }
}
