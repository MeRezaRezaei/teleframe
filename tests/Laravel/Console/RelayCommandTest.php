<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Console;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\Obfuscated2Cipher;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\RelayServer;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\VaultMtprotoAnswerer;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\WebSocketFrameCodec;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\WebSocketRelayServer;
use MeRezaRezaei\Teleframe\Laravel\Console\RelayCommand;
use MeRezaRezaei\Teleframe\Tests\Core\MTProto\Transport\Obfuscated2CodecTest;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;
use phpseclib3\Crypt\RSA;
use RuntimeException;

/**
 * `teleframe:relay` — config-driven obfuscated2 relay terminal.
 *
 * Failure modes are app-level (disabled / no accounts / unmatched account).
 * The happy path is a REAL loopback end-to-end through the command layer: a
 * forked child runs the command (--once, one bounded accept pass); the parent
 * plays the obfuscated2 client AND the fake upstream DC (TCP listener) and
 * asserts the byte round-trip + the child's exit code via a status pipe —
 * reusing the RelayServerTest fork + deadline-guard harness. No external
 * network, no credentials, no real account.
 */
final class RelayCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The provider registers it in real apps; register here too so the
        // testbench kernel is never left without it.
        $this->app->make(Kernel::class)->registerCommand(new RelayCommand);
    }

    private const DEADLINE_S = 2.0;

    public function test_disabled_relay_returns_failure(): void
    {
        config()->set('teleframe.relay', ['enabled' => false]);

        self::assertSame(RelayCommand::FAILURE, Artisan::call('teleframe:relay'));
    }

    public function test_no_accounts_returns_failure(): void
    {
        config()->set('teleframe.relay', ['enabled' => true, 'accounts' => []]);

        self::assertSame(RelayCommand::FAILURE, Artisan::call('teleframe:relay'));
    }

    public function test_account_filter_without_match_returns_failure(): void
    {
        config()->set('teleframe.relay', [
            'enabled' => true,
            'accounts' => [['account_id' => 7, 'dc_id' => 5]],
        ]);

        self::assertSame(RelayCommand::FAILURE, Artisan::call('teleframe:relay', ['--account' => '99']));
    }

    public function test_once_mode_roundtrips_through_the_command_layer(): void
    {
        if (! function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl_fork required for the relay child');
        }

        $listenerPort = $this->freePort();

        $upstream = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        self::assertIsResource($upstream, "upstream bind failed: {$errstr} ({$errno})");
        [, $upstreamPort] = explode(':', stream_socket_get_name($upstream, false));

        config()->set('teleframe.relay', [
            'enabled' => true,
            'listen_host' => '127.0.0.1',
            'accounts' => [[
                'account_id' => 7,
                'dc_id' => 5,
                'port' => $listenerPort,
                'upstream_host' => '127.0.0.1',
                'upstream_port' => (int) $upstreamPort,
            ]],
        ]);

        [$statusRelay, $statusTest] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        $pid = pcntl_fork();
        if ($pid === -1) {
            self::markTestSkipped('pcntl_fork failed');
        }

        if ($pid === 0) {
            // Child: the real command layer — one bounded accept pass.
            fclose($statusTest);
            $exit = Artisan::call('teleframe:relay', ['--once' => true]);
            @fwrite($statusRelay, (string) $exit);
            fclose($statusRelay);
            $this->childExit();
        }

        fclose($statusRelay);
        $client = null;
        $upstreamLeg = null;
        try {
            // The obfuscated2 client connects first (retried until the child's
            // listener is up), which unblocks the child's accept.
            $deadline = microtime(true) + self::DEADLINE_S;
            $client = $this->connectWithRetry($listenerPort, $deadline);
            self::assertIsResource($client, 'client never reached the relay listener');

            $header = $this->syntheticHeader(5, 'intermediate');
            $cipher = Obfuscated2Cipher::fromHeader($header);
            $payload = 'relay-cmd-roundtrip-payload';

            $this->writeAll($client, $header);
            $this->writeAll($client, $cipher->xcrypt($payload, true));

            // The child's upstream resolver now dials the fake upstream DC and
            // writes the abridged init byte (FrameCodec::writeInit parity).
            $upstreamLeg = @stream_socket_accept($upstream, 5);
            self::assertIsResource($upstreamLeg, 'relay child never connected upstream');

            self::assertSame("\xef", $this->readExact($upstreamLeg, 1, $deadline), 'upstream must receive the abridged init byte');
            self::assertSame($payload, $this->readExact($upstreamLeg, strlen($payload), $deadline), 'upstream must receive the decrypted payload');

            // Upstream → relay → client: reply, re-encrypted on the client leg.
            $reply = 'relay-cmd-echo-reply';
            $this->writeAll($upstreamLeg, $reply);
            self::assertSame($reply, $cipher->xcrypt($this->readExact($client, strlen($reply), $deadline), false));

            // Close both legs so the child's pump drains fully, serveOnce returns,
            // and the child writes its exit status before we assert on it.
            fclose($client);
            $client = null;
            fclose($upstreamLeg);
            $upstreamLeg = null;

            self::assertSame('0', $this->readStatus($statusTest, $deadline), 'relay command child must exit SUCCESS');
        } finally {
            if (is_resource($client)) {
                fclose($client);
            }
            if (is_resource($upstreamLeg)) {
                fclose($upstreamLeg);
            }
            fclose($upstream);
            fclose($statusTest);
            $this->reapChild($pid);
        }
    }

    public function test_websocket_socket_option_roundtrips_through_the_command_layer(): void
    {
        if (! function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl_fork required for the relay child');
        }

        $listenerPort = $this->freePort();

        $upstream = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        self::assertIsResource($upstream, "upstream bind failed: {$errstr} ({$errno})");
        [, $upstreamPort] = explode(':', stream_socket_get_name($upstream, false));

        config()->set('teleframe.relay', [
            'enabled' => true,
            'listen_host' => '127.0.0.1',
            'accounts' => [[
                'account_id' => 7,
                'dc_id' => 5,
                'port' => $listenerPort,
                'upstream_host' => '127.0.0.1',
                'upstream_port' => (int) $upstreamPort,
            ]],
        ]);

        [$statusRelay, $statusTest] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        $pid = pcntl_fork();
        if ($pid === -1) {
            self::markTestSkipped('pcntl_fork failed');
        }

        if ($pid === 0) {
            // Child: the real command layer, websocket socket leg — one pass.
            fclose($statusTest);
            $exit = Artisan::call('teleframe:relay', ['--once' => true, '--socket' => 'websocket']);
            @fwrite($statusRelay, (string) $exit);
            fclose($statusRelay);
            $this->childExit();
        }

        fclose($statusRelay);
        $client = null;
        $upstreamLeg = null;
        try {
            $deadline = microtime(true) + self::DEADLINE_S;
            $client = $this->connectWithRetry($listenerPort, $deadline);
            self::assertIsResource($client, 'client never reached the relay listener');

            // RFC6455 opening handshake, then the obfuscated2 header + one
            // encrypted payload as MASKED binary WS messages (browser shape).
            $this->performHandshake($client);

            $header = $this->syntheticHeader(5, 'intermediate');
            $cipher = Obfuscated2Cipher::fromHeader($header);
            $payload = 'ws-relay-cmd-roundtrip-payload';

            $this->sendBinary($client, $header);
            $this->sendBinary($client, $cipher->xcrypt($payload, true));

            // The child's upstream resolver now dials the fake upstream DC and
            // writes the abridged init byte (FrameCodec::writeInit parity).
            $upstreamLeg = @stream_socket_accept($upstream, 5);
            self::assertIsResource($upstreamLeg, 'relay child never connected upstream');

            self::assertSame("\xef", $this->readExact($upstreamLeg, 1, $deadline), 'upstream must receive the abridged init byte');
            self::assertSame($payload, $this->readExact($upstreamLeg, strlen($payload), $deadline), 'upstream must receive the decrypted payload');

            // Upstream → relay → client: reply, re-encrypted and re-framed
            // as an unmasked WS binary message on the client leg.
            $reply = 'ws-relay-cmd-echo-reply';
            $this->writeAll($upstreamLeg, pack('V', strlen($reply)).$reply);
            [$replyLen, $replyBody] = $this->readEncryptedBinaryFrame($client, $cipher, $deadline);
            self::assertSame(strlen($reply), $replyLen);
            self::assertSame($reply, $replyBody);

            // Close both legs so the child's pump drains fully, serveOnce returns,
            // and the child writes its exit status before we assert on it.
            fclose($client);
            $client = null;
            fclose($upstreamLeg);
            $upstreamLeg = null;

            self::assertSame('0', $this->readStatus($statusTest, $deadline), 'relay command child must exit SUCCESS');
        } finally {
            if (is_resource($client)) {
                fclose($client);
            }
            if (is_resource($upstreamLeg)) {
                fclose($upstreamLeg);
            }
            fclose($upstream);
            fclose($statusTest);
            $this->reapChild($pid);
        }
    }

    /**
     * The vault /apiws terminal wiring: with
     * config('teleframe.relay.vault_terminal.enabled') true and the relay RSA
     * key set, every websocket listener must carry a VaultMtprotoAnswerer
     * over its answerer seam (built from the container's
     * RelayVaultTerminalFactory — dispatch() + rsaPrivateKey()). No network,
     * no credentials; `--once` with no client just does a bounded accept tick.
     */
    public function test_vault_terminal_wires_the_answerer_into_the_websocket_listener(): void
    {
        $key = RSA::createKey(2048);
        $captured = null;
        $capturedTicketValidator = null;
        $command = new RelayCommand(
            static function (string $host, int $port, callable $resolver, string $socket, ?callable $answerer = null, ?int $defaultDcId = null, ?callable $ticketValidator = null, ?string $tcpSecret = null) use (&$captured, &$capturedTicketValidator) {
                $captured = $answerer;
                $capturedTicketValidator = $ticketValidator;

                return $socket === 'websocket'
                    ? new WebSocketRelayServer($host, $port, $resolver, null, $answerer, $ticketValidator)
                    : new RelayServer($host, $port, $resolver);
            },
        );
        $this->app->make(Kernel::class)->registerCommand($command);

        config()->set('teleframe.relay', [
            'enabled' => true,
            'listen_host' => '127.0.0.1',
            'accounts' => [['account_id' => 7, 'dc_id' => 5, 'port' => 0]],
            'vault_terminal' => ['enabled' => true, 'rsa_key' => $key->toString('PKCS1')],
        ]);

        self::assertSame(RelayCommand::SUCCESS, Artisan::call('teleframe:relay', ['--once' => true, '--socket' => 'websocket']));
        self::assertInstanceOf(VaultMtprotoAnswerer::class, $captured, 'the websocket listener must carry the vault answerer');
        self::assertIsCallable($capturedTicketValidator, 'the websocket listener must carry the W-02 one-shot ticket gate');
    }

    /**
     * The vault terminal is a browser (/apiws) path: on the raw-TCP leg the
     * answerer stays unset and the listener keeps the byte-proxy server — and
     * so does the seed responder (peek is a websocket-only seam).
     */
    public function test_vault_terminal_does_not_wire_the_answerer_into_the_tcp_listener(): void
    {
        $key = RSA::createKey(2048);
        $captured = 'unset';
        $command = new RelayCommand(
            static function (string $host, int $port, callable $resolver, string $socket, ?callable $answerer = null, ?int $defaultDcId = null, ?callable $ticketValidator = null, ?string $tcpSecret = null) use (&$captured) {
                $captured = $answerer;

                return $socket === 'websocket'
                    ? new WebSocketRelayServer($host, $port, $resolver, null, $answerer, $ticketValidator)
                    : new RelayServer($host, $port, $resolver);
            },
        );
        $this->app->make(Kernel::class)->registerCommand($command);

        config()->set('teleframe.relay', [
            'enabled' => true,
            'listen_host' => '127.0.0.1',
            'accounts' => [['account_id' => 7, 'dc_id' => 5, 'port' => 0]],
            'vault_terminal' => ['enabled' => true, 'rsa_key' => $key->toString('PKCS1')],
        ]);

        self::assertSame(RelayCommand::SUCCESS, Artisan::call('teleframe:relay', ['--once' => true, '--socket' => 'tcp']));
        self::assertNull($captured, 'the raw-TCP leg stays the byte proxy, no answerer');
    }

    /**
     * Enabling the vault terminal without its RSA identity is a hard failure:
     * the command must surface the missing key before any listener binds.
     */
    public function test_vault_terminal_without_rsa_key_returns_failure(): void
    {
        $command = new RelayCommand;
        $this->app->make(Kernel::class)->registerCommand($command);

        config()->set('teleframe.relay', [
            'enabled' => true,
            'listen_host' => '127.0.0.1',
            'accounts' => [['account_id' => 7, 'dc_id' => 5, 'port' => 0]],
            'vault_terminal' => ['enabled' => true, 'rsa_key' => ''],
        ]);

        self::assertSame(RelayCommand::FAILURE, Artisan::call('teleframe:relay', ['--once' => true, '--socket' => 'websocket']));
    }

    /**
     * Performs the RFC6455 client side of the handshake against the command's
     * websocket listener.
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
                throw new RuntimeException('RelayCommandTest: handshake response timed out');
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
     * Reads the re-encrypted WS reply and returns the decrypted length+body.
     *
     * @param  resource  $socket
     * @return array{int, string}
     */
    private function readEncryptedBinaryFrame($socket, Obfuscated2Cipher $cipher, float $deadline): array
    {
        $wire = '';
        $frame = null;
        while ($frame === null) {
            if (microtime(true) >= $deadline) {
                throw new RuntimeException('RelayCommandTest: WS reply frame timed out');
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
        $length = unpack('V', $cipher->xcrypt(substr($encrypted, 0, 4), false))[1];

        return [$length, $cipher->xcrypt(substr($encrypted, 4), false)];
    }

    /** @return resource|null */
    private function connectWithRetry(int $port, float $deadline)
    {
        while (microtime(true) < $deadline) {
            $socket = @stream_socket_client("tcp://127.0.0.1:{$port}", $errno, $errstr, 0.5);
            if (is_resource($socket)) {
                return $socket;
            }
            usleep(20000);
        }

        return null;
    }

    private function freePort(): int
    {
        $probe = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        self::assertIsResource($probe, "probe bind failed: {$errstr} ({$errno})");
        [, $port] = explode(':', stream_socket_get_name($probe, false));
        fclose($probe);

        return (int) $port;
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

    /** @param resource $socket */
    private function readExact($socket, int $length, float $deadline): string
    {
        @stream_set_blocking($socket, false);
        $buffer = '';
        while (strlen($buffer) < $length) {
            if (microtime(true) >= $deadline) {
                throw new RuntimeException('RelayCommandTest: timed out reading '.$length.' bytes');
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

    /** @param resource $socket */
    private function writeAll($socket, string $bytes): void
    {
        $total = strlen($bytes);
        $written = 0;
        while ($written < $total) {
            $n = @fwrite($socket, substr($bytes, $written));
            if ($n === false || $n === 0) {
                throw new RuntimeException('RelayCommandTest: write failed at offset '.$written);
            }
            $written += $n;
        }
    }

    /** @param resource $socket */
    private function readStatus($socket, float $deadline): string
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

    private function reapChild(int $pid): void
    {
        $deadline = microtime(true) + 1.0;
        while (microtime(true) < $deadline) {
            $status = null;
            $reaped = pcntl_waitpid($pid, $status, WNOHANG);
            if ($reaped === $pid) {
                self::assertSame(0, pcntl_wexitstatus($status) ?? -1, 'relay command child must exit cleanly');

                return;
            }
            if ($reaped === -1) {
                return;
            }
            usleep(5000);
        }

        @posix_kill($pid, SIGKILL);
        pcntl_waitpid($pid, $status);

        self::fail('relay command child did not exit within the deadline');
    }

    private function childExit(): never
    {
        if (extension_loaded('FFI')) {
            try {
                $libc = \FFI::cdef('void _exit(int status);', 'libc.so.6');
                $libc->_exit(0);
            } catch (\Throwable) {
                // FFI disabled or not glibc — exit normally.
            }
        }

        exit(0);
    }
}
