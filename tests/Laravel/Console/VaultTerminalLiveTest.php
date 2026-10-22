<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Console;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use MeRezaRezaei\Teleframe\Core\MTProto\Connection\PlainConnection;
use MeRezaRezaei\Teleframe\Core\MTProto\Crypto\AuthKeyFactory;
use MeRezaRezaei\Teleframe\Core\MTProto\Crypto\PacketCodec;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLDecoder;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLEncoder;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\FrameCodec;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\Obfuscated2Cipher;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\WebSocketFrameCodec;
use MeRezaRezaei\Teleframe\Core\Support\EnvFile;
use MeRezaRezaei\Teleframe\Laravel\Console\RelayCommand;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayTicketBroker;
use MeRezaRezaei\Teleframe\Tests\Core\MTProto\Transport\Obfuscated2CodecTest;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;
use phpseclib3\Crypt\RSA;
use RuntimeException;

/**
 * LIVE gate proving the RELAY COMMAND wiring of the vault /apiws terminal
 * end to end — not just the raw VaultMtprotoAnswerer.
 *
 * Skipped unless TELEFRAME_VALIDATE_VAULT_TERMINAL=1 AND a real user
 * session resolves (sibling-../teleframe/.env first, environment fallback —
 * the same opt-in convention as tests/Backup/LiveVaultSmokeTest):
 *
 *     TELEFRAME_VALIDATE_VAULT_TERMINAL=1 vendor/bin/phpunit --filter VaultTerminalLive
 *
 * A forked child runs `teleframe:relay` --once --socket=websocket through
 * the REAL command layer (config teleframe.relay.vault_terminal.enabled=true,
 * rsa_key = a temp PKCS#1 PEM) with the listener bound to an ephemeral free
 * port. The parent plays the browser exactly like VaultMtprotoAnswererTest:
 * RFC6455 opening handshake, the 64-byte obfuscated2 header as its own
 * masked WS binary message, then a req_pq_multi plain envelope — and asserts
 * the resPQ the COMMAND's answerer returns advertises the fingerprint of the
 * very RSA key the config supplied (AuthKeyFactory::fingerprintBytesOf), and
 * that the child exits SUCCESS once the connection closes.
 */
final class VaultTerminalLiveTest extends TestCase
{
    private const DEADLINE_S = 5.0;

    private const SOCKET_POLL_US = 1000;

    private const CHILD_WAIT_POLL_US = 5000;

    private const SERVER_STARTUP_POLL_US = 20_000;

    protected function setUp(): void
    {
        parent::setUp();

        // The provider registers it in real apps; register here too so the
        // testbench kernel is never left without it. The testbench kernel is
        // always the framework kernel (which alone declares registerCommand).
        $kernel = $this->app->make(Kernel::class);
        if (! $kernel instanceof \Illuminate\Foundation\Console\Kernel) {
            self::fail('testbench console kernel must be the framework kernel');
        }
        $kernel->registerCommand(new RelayCommand);
    }

    public function test_relay_command_vault_terminal_answers_res_pq_with_the_configured_rsa_key(): void
    {
        if (getenv('TELEFRAME_VALIDATE_VAULT_TERMINAL') !== '1') {
            $this->markTestSkipped('TELEFRAME_VALIDATE_VAULT_TERMINAL=1 to run');
        }

        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl_fork required for the relay command child');
        }

        $env = EnvFile::read(dirname(__DIR__, 3).'/teleframe/.env');
        $session = (string) ($env['TELEGRAM_USER_SESSION'] ?? getenv('TELEGRAM_USER_SESSION') ?: '');
        if ($session === '') {
            $this->markTestSkipped(
                'live vault terminal gate: provide TELEGRAM_USER_SESSION '
                .'(../teleframe/.env or environment) to run',
            );
        }

        // The relay-local RSA identity the vault terminal authenticates its
        // DH handshake with — written to a temp PEM exactly as
        // teleframe.relay.vault_terminal.rsa_key points at.
        $key = RSA::createKey(2048);
        $privatePem = $key->toString('PKCS1');
        $publicPem = (string) $key->getPublicKey()->toString('PKCS1');
        $keyFile = tempnam(sys_get_temp_dir(), 'vault-live-rsa-');
        self::assertNotFalse($keyFile, 'could not create the temp RSA key file');
        file_put_contents($keyFile, $privatePem);

        $listenerPort = $this->freePort();

        config()->set('teleframe.relay', [
            'enabled' => true,
            'listen_host' => '127.0.0.1',
            'accounts' => [['account_id' => 7, 'dc_id' => 5, 'port' => $listenerPort]],
            'vault_terminal' => ['enabled' => true, 'rsa_key' => $keyFile],
        ]);

        [$statusRelay, $statusTest] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        // W-02: mint the one-shot relay ticket BEFORE forking — the child's
        // cache is copy-on-write, so the command's redeem must see it.
        $ticket = app(RelayTicketBroker::class)->mint(7)['ticket'];

        $pid = pcntl_fork();
        if ($pid === -1) {
            self::markTestSkipped('pcntl_fork failed');
        }

        if ($pid === 0) {
            // Child: the real command layer — one bounded websocket accept
            // pass, answerer (built from the container factory + config)
            // serving the vault DH handshake instead of an upstream dial.
            fclose($statusTest);
            $exit = Artisan::call('teleframe:relay', ['--once' => true, '--socket' => 'websocket']);
            @fwrite($statusRelay, (string) $exit);
            fclose($statusRelay);
            $this->childExit();
        }

        fclose($statusRelay);
        $client = null;
        try {
            $deadline = microtime(true) + self::DEADLINE_S;
            $client = $this->connectWithRetry($listenerPort, $deadline);
            self::assertIsResource($client, 'client never reached the vault terminal listener');

            // RFC6455 handshake (W-02: presents the pre-fork minted ticket),
            // then the obfuscated2 header as its own masked WS binary message
            // (VaultMtprotoAnswererTest mechanics).
            $this->performHandshake($client, $ticket);

            $header = Obfuscated2CodecTest::clientHeader(5, 'abridged');
            $this->sendBinary($client, $header);
            $clientCipher = Obfuscated2Cipher::fromHeader($header);

            // req_pq_multi -> resPQ: the FIRST DH step; the COMMAND's answerer
            // (not a hand-built one) must answer with the resPQ advertising the
            // RSA key the config pointed at.
            $nonce = random_bytes(16);
            $body = TLEncoder::encodeObject('req_pq_multi', ['nonce' => $nonce]);
            $this->sendBinary(
                $client,
                $clientCipher->xcrypt(FrameCodec::wrapAbridgedPayload(
                    PlainConnection::buildPlainEnvelope($body, PacketCodec::generateMessageId()),
                ), true),
            );

            $reply = $this->readEnvelope($client, $clientCipher, $deadline);
            $plain = PlainConnection::parsePlainEnvelope($reply);
            $offset = 0;
            $respq = TLDecoder::decodeObject($plain, $offset);

            self::assertSame('resPQ', $respq['_'] ?? null, 'the command-layer answerer must reply with resPQ');
            self::assertSame($nonce, $respq['nonce'] ?? null, 'resPQ must echo the client nonce');
            $fingerprints = (array) ($respq['server_public_key_fingerprints'] ?? []);
            self::assertNotEmpty($fingerprints, 'resPQ must advertise the relay public key');
            self::assertStringContainsString(
                base64_encode(AuthKeyFactory::fingerprintBytesOf($publicPem)),
                base64_encode(TLEncoder::encodeValue('long', $fingerprints[0])),
                'resPQ (via teleframe:relay) must advertise the relay key from teleframe.relay.vault_terminal.rsa_key',
            );

            // Close cleanly: the answerer sees EOF, serveOnce returns, and the
            // --once pass exits SUCCESS.
            fclose($client);
            $client = null;

            self::assertSame('0', $this->readStatus($statusTest, $deadline), 'relay command child must exit SUCCESS');
        } finally {
            if (is_resource($client)) {
                fclose($client);
            }
            fclose($statusTest);
            @unlink($keyFile);
            $this->reapChild($pid);
        }
    }

    /** @return resource|null */
    private function connectWithRetry(int $port, float $deadline)
    {
        while (microtime(true) < $deadline) {
            $socket = @stream_socket_client("tcp://127.0.0.1:{$port}", $errno, $errstr, 0.5);
            if (is_resource($socket)) {
                return $socket;
            }
            usleep(self::SERVER_STARTUP_POLL_US);
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
     * Performs the RFC6455 client side of the handshake against the command's
     * websocket listener (GramJS shape, `binary` subprotocol). W-02: the
     * command's vault terminal now demands a one-shot relay ticket, so the
     * parent presents the pre-fork minted one (Authorization Bearer).
     *
     * @param  resource  $socket
     */
    private function performHandshake($socket, string $ticket): void
    {
        $key = base64_encode(random_bytes(16));
        $this->writeAll($socket, "GET /apiws HTTP/1.1\r\n"
            ."Host: 127.0.0.1\r\n"
            ."Authorization: Bearer {$ticket}\r\n"
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
                throw new RuntimeException('VaultTerminalLiveTest: handshake response timed out');
            }
            $chunk = @fread($socket, 8192);
            if ($chunk === false || $chunk === '') {
                usleep(self::SOCKET_POLL_US);

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
     * Reads one server->client envelope (abridged-framed inside one or more
     * unmasked WS binary messages), decrypting across chunk boundaries.
     *
     * @param  resource  $socket
     */
    private function readEnvelope($socket, Obfuscated2Cipher $cipher, float $deadline): string
    {
        @stream_set_blocking($socket, false);
        $wire = '';
        $plain = '';
        while (true) {
            $envelope = $this->peelAbridged($plain);
            if ($envelope !== null) {
                return $envelope;
            }

            $chunk = @fread($socket, 8192);
            if ($chunk === false || $chunk === '') {
                if (microtime(true) >= $deadline) {
                    throw new RuntimeException('VaultTerminalLiveTest: WS frame timed out');
                }
                usleep(self::SOCKET_POLL_US);

                continue;
            }
            $wire .= $chunk;
            while (true) {
                $frame = WebSocketFrameCodec::parseFrame($wire);
                if ($frame === null) {
                    break;
                }
                self::assertFalse($frame['masked'], 'server frames must NOT be masked');
                self::assertSame(WebSocketFrameCodec::OPCODE_BINARY, $frame['opcode']);
                $wire = substr($wire, self::frameLength($frame));
                $plain .= $cipher->xcrypt($frame['payload'], false);
            }
        }
    }

    /**
     * Strips one abridged frame (varint len/4 prefix + payload) from $buffer.
     *
     * @param  string  $buffer  in/out
     */
    private function peelAbridged(string &$buffer): ?string
    {
        if (strlen($buffer) < 1) {
            return null;
        }
        $len4 = ord($buffer[0]);
        $header = 1;
        if ($len4 >= 0x7F) {
            if (strlen($buffer) < 4) {
                return null;
            }
            $len4 = unpack('V', substr($buffer, 1, 3)."\x00")[1];
            $header = 4;
        }
        $len = $len4 << 2;
        if ($len < 1 || $len > 2 * 1024 * 1024) {
            throw new RuntimeException(sprintf('VaultTerminalLiveTest: invalid abridged frame length %d', $len));
        }
        if (strlen($buffer) < $header + $len) {
            return null;
        }
        $payload = substr($buffer, $header, $len);
        $buffer = substr($buffer, $header + $len);

        return $payload;
    }

    /** @param array{opcode: int, payload: string, masked: bool} $frame */
    private static function frameLength(array $frame): int
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

    /** @param resource $socket */
    private function writeAll($socket, string $bytes): void
    {
        $total = strlen($bytes);
        $written = 0;
        while ($written < $total) {
            $n = @fwrite($socket, substr($bytes, $written));
            if ($n === false || $n === 0) {
                throw new RuntimeException('VaultTerminalLiveTest: write failed at offset '.$written);
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
            usleep(self::SOCKET_POLL_US);
        }

        return $data;
    }

    private function reapChild(int $pid): void
    {
        $deadline = microtime(true) + 1.0;
        while (microtime(true) < $deadline) {
            $status = 0;
            $reaped = pcntl_waitpid($pid, $status, WNOHANG);
            if ($reaped === $pid) {
                self::assertSame(0, pcntl_wexitstatus($status), 'relay command child must exit cleanly');

                return;
            }
            if ($reaped === -1) {
                return;
            }
            usleep(self::CHILD_WAIT_POLL_US);
        }

        @posix_kill($pid, SIGKILL);
        pcntl_waitpid($pid, $status);

        self::fail('relay command child did not exit within the deadline');
    }

    /**
     * Terminates the forked child to status 0. PHP's shutdown sequence in a
     * forked PHPUnit child would flush buffers and re-run teardown; the
     * sibling fork harnesses _exit via libc where FFI is available, and this
     * is their plain fallback — phpstan cannot model FFI's dynamic method
     * surface, so the simple exit stays.
     */
    private function childExit(): never
    {
        exit(0);
    }
}
