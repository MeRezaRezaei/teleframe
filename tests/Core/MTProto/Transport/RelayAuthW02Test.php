<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core\MTProto\Transport;

use MeRezaRezaei\Teleframe\Core\MTProto\Transport\WebSocketFrameCodec;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\WebSocketRelayServer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Loopback proof of the vault /apiws auth gate (finding W-02) — NO external
 * network, no real DCs, no credentials.
 *
 * The raw RFC6455 upgrade authenticates nobody, so when a ticketValidator is
 * configured the relay MUST demand a one-shot relay ticket (Authorization
 * Bearer or ?ticket= on the request line) and redeem it BEFORE the 101
 * upgrade — missing/invalid tickets get an HTTP 401 protocol error, a valid
 * ticket upgrades and the validator's granted account id rides through.
 *
 * A forked child runs one serveOnce per case; the parent plays the browser
 * and asserts strictly on the wire: the HTTP status line, whether the
 * validator was reached, and the exact ticket the validator saw (written to
 * a real file — the child's memory is copy-on-write).
 *
 * Bounded and deterministic (deadline-guarded reads); a stuck child can
 * never hang CI. The happy-path session flow on top of the gate is proven
 * separately by VaultMtprotoAnswererTest (which now authenticates with
 * `test-ticket` and asserts the granted account binds the socket).
 */
final class RelayAuthW02Test extends TestCase
{
    private const DEADLINE_S = 3.0;

    // vendor/ is a symlink to the main tree, so vendor/autoload.php resolves
    // WebSocketRelayServer to the main tree's unfixed src/. Pin this tree's
    // sources before any test body autoloads the class.
    public static function setUpBeforeClass(): void
    {
        require dirname(__DIR__, 4).'/tests/Schema/FixesAutoload.php';
    }

    public function test_handshake_without_a_ticket_is_rejected_with_401(): void
    {
        $this->assertRejectedWith401(null, 'relay must refuse a ticket-less upgrade');
    }

    public function test_handshake_with_an_unknown_ticket_is_rejected_with_401(): void
    {
        $this->assertRejectedWith401('not-a-real-ticket', 'relay must refuse an unredeemable ticket');
    }

    public function test_handshake_with_a_valid_bearer_ticket_upgrades_and_reaches_the_validator(): void
    {
        $this->assertUpgradesWithTicket('good-ticket', 'GET /apiws HTTP/1.1', ['Authorization: Bearer good-ticket'], 'Authorization: Bearer');
    }

    public function test_handshake_with_a_valid_query_ticket_upgrades_and_reaches_the_validator(): void
    {
        $this->assertUpgradesWithTicket(
            'query-ticket',
            'GET /apiws?ticket=query-ticket HTTP/1.1',
            [],
            '?ticket= on the request line',
        );
    }

    public function test_handshake_with_an_identity_validator_passes_the_grant_array_through(): void
    {
        if (! function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl_fork required for the forked relay child');
        }

        $grantLog = tempnam(sys_get_temp_dir(), 'relay-w02-');
        if ($grantLog === false) {
            self::markTestSkipped('could not create a grant log file');
        }
        @unlink($grantLog);

        // Identity validator: redeems the ticket to an identity array
        // {account_id, user_id} (the Laravel user capture). The relay's 401
        // gate must accept it, and the SAME array — unchanged — must be what
        // reaches the answerer as its 6th argument.
        $relay = new WebSocketRelayServer(
            '127.0.0.1',
            0,
            answerer: static function ($client, $cipher, $lead, int $dcId, string $protocol, $ticketGrant) use ($grantLog): void {
                file_put_contents($grantLog, json_encode($ticketGrant));
            },
            ticketValidator: static fn (string $ticket): ?array => $ticket === 'id-ticket' ? ['account_id' => 7, 'user_id' => 5] : null,
        );
        $listen = $relay->listen();
        $port = $this->listeningPort($listen);

        $client = null;
        $pid = pcntl_fork();
        if ($pid === -1) {
            self::markTestSkipped('pcntl_fork failed');
        }

        if ($pid === 0) {
            $relay->serveOnce($listen);
            self::childExit();
        }

        fclose($listen);
        try {
            $client = stream_socket_client("tcp://127.0.0.1:{$port}", $errno, $errstr, 2.0);
            self::assertIsResource($client, "client connect failed: {$errstr} ({$errno})");

            $this->writeAll($client, $this->handshakeRequest('GET /apiws HTTP/1.1', ['Authorization: Bearer id-ticket']));
            $status = $this->readStatusLine($client);
            self::assertStringStartsWith('HTTP/1.1 101', $status, 'an identity-bearing valid ticket must upgrade');

            // Serve the 64-byte obfuscated2 header as its own masked binary
            // message so the answerer (with the raw grant array) is reached.
            $this->writeAll($client, WebSocketFrameCodec::frame(
                WebSocketFrameCodec::OPCODE_BINARY,
                Obfuscated2CodecTest::clientHeader(5, 'abridged'),
                mask: true,
            ));

            $raw = $this->readValidatorLog($grantLog);
            self::assertIsString($raw, 'the answerer must be reached and record the grant');
            self::assertSame(
                ['account_id' => 7, 'user_id' => 5],
                json_decode($raw, true),
                'the identity array rides through the relay UNCHANGED',
            );
        } finally {
            if (is_resource($client)) {
                fclose($client);
            }
            @unlink($grantLog);
            $this->reapChild($pid);
        }
    }

    // ---- cases -----------------------------------------------------------

    /**
     * @param  string|null  $ticket  null = send no ticket at all
     */
    private function assertRejectedWith401(?string $ticket, string $reason): void
    {
        if (! function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl_fork required for the forked relay child');
        }

        $validatorLog = tempnam(sys_get_temp_dir(), 'relay-w02-');
        if ($validatorLog === false) {
            self::markTestSkipped('could not create a validator log file');
        }
        @unlink($validatorLog);

        $relay = new WebSocketRelayServer(
            '127.0.0.1',
            0,
            answerer: null,
            ticketValidator: static function (string $seen) use ($validatorLog): ?int {
                file_put_contents($validatorLog, $seen);

                return null;
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
            $relay->serveOnce($listen);
            self::childExit();
        }

        fclose($listen);
        try {
            $client = stream_socket_client("tcp://127.0.0.1:{$port}", $errno, $errstr, 2.0);
            self::assertIsResource($client, "client connect failed: {$errstr} ({$errno})");

            $headers = $ticket === null ? null : ['Authorization: Bearer '.$ticket];
            $this->writeAll($client, $this->handshakeRequest('GET /apiws HTTP/1.1', $headers));
            $status = $this->readStatusLine($client);
            self::assertStringStartsWith('HTTP/1.1 401', $status, $reason);
            if ($ticket !== null) {
                self::assertSame($ticket, $this->readValidatorLog($validatorLog), 'a presented ticket must reach the validator');
            } else {
                self::assertFileDoesNotExist($validatorLog, 'a ticket-less client must never reach the validator');
            }
        } finally {
            if (is_resource($client)) {
                fclose($client);
            }
            @unlink($validatorLog);
            $this->reapChild($pid);
        }
    }

    /**
     * @param  list<string>  $extraHeaders  raw lines appended to the request
     */
    private function assertUpgradesWithTicket(string $ticket, string $requestLine, array $extraHeaders, string $context): void
    {
        if (! function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl_fork required for the forked relay child');
        }

        $validatorLog = tempnam(sys_get_temp_dir(), 'relay-w02-');
        if ($validatorLog === false) {
            self::markTestSkipped('could not create a validator log file');
        }
        @unlink($validatorLog);

        $relay = new WebSocketRelayServer(
            '127.0.0.1',
            0,
            answerer: null,
            ticketValidator: static function (string $seen) use ($validatorLog, $ticket): ?int {
                file_put_contents($validatorLog, $seen);

                return $seen === $ticket ? 7 : null;
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
            $relay->serveOnce($listen);
            self::childExit();
        }

        fclose($listen);
        try {
            $client = stream_socket_client("tcp://127.0.0.1:{$port}", $errno, $errstr, 2.0);
            self::assertIsResource($client, "client connect failed: {$errstr} ({$errno})");

            $this->writeAll($client, $this->handshakeRequest($requestLine, $extraHeaders));
            $status = $this->readStatusLine($client);
            self::assertStringStartsWith('HTTP/1.1 101', $status, 'a valid ticket ('.$context.') must upgrade to 101');
            self::assertSame($ticket, $this->readValidatorLog($validatorLog), 'the validator must see the exact ticket ('.$context.')');

            // The child now blocks reading the obfuscated2 header; closing the
            // socket lets it exit cleanly (the client sent no header bytes).
            fclose($client);
            $client = null;
        } finally {
            if (is_resource($client)) {
                fclose($client);
            }
            @unlink($validatorLog);
            $this->reapChild($pid);
        }
    }

    // ---- wire helpers ----------------------------------------------------

    /**
     * @param  list<string>|null  $extraHeaders
     */
    private function handshakeRequest(string $requestLine, ?array $extraHeaders): string
    {
        $request = $requestLine."\r\n"
            ."Host: 127.0.0.1\r\n"
            ."Upgrade: websocket\r\n"
            ."Connection: Upgrade\r\n"
            .'Sec-WebSocket-Key: '.base64_encode(random_bytes(16))."\r\n"
            ."Sec-WebSocket-Protocol: binary\r\n"
            ."Sec-WebSocket-Version: 13\r\n";
        foreach ($extraHeaders ?? [] as $line) {
            $request .= $line."\r\n";
        }

        return $request."\r\n";
    }

    /**
     * Reads the status line of the server's HTTP response.
     *
     * @param  resource  $socket
     */
    private function readStatusLine($socket): string
    {
        @stream_set_blocking($socket, false);
        $buffer = '';
        $deadline = microtime(true) + self::DEADLINE_S;
        while (! str_contains($buffer, "\r\n")) {
            if (microtime(true) >= $deadline) {
                throw new RuntimeException('RelayAuthW02Test: response status line timed out');
            }
            $chunk = @fread($socket, 8192);
            if ($chunk === false || $chunk === '') {
                usleep(1000);

                continue;
            }
            $buffer .= $chunk;
        }

        return trim((string) strstr($buffer, "\r\n", true) ?: $buffer);
    }

    private function readValidatorLog(string $path): ?string
    {
        $deadline = microtime(true) + self::DEADLINE_S;
        while (microtime(true) < $deadline) {
            if (is_file($path)) {
                $contents = @file_get_contents($path);

                return $contents === false ? null : $contents;
            }
            usleep(1000);
        }

        return null;
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
    private function writeAll($socket, string $bytes): void
    {
        $total = strlen($bytes);
        $written = 0;
        while ($written < $total) {
            $n = @fwrite($socket, substr($bytes, $written));
            if ($n === false || $n === 0) {
                throw new RuntimeException('RelayAuthW02Test: write failed at offset '.$written);
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
     * (a forked PHPUnit child would flush buffers and re-run teardown).
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
