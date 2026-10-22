<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core\MTProto\Transport;

use MeRezaRezaei\Teleframe\Core\MTProto\Connection\PlainConnection;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLEncoder;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\FrameCodec;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\Obfuscated2Cipher;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\VaultMtprotoAnswerer;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\WebSocketFrameCodec;
use phpseclib3\Crypt\RSA;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Stringable;

/**
 * The per-connection budgets are only real if they are REACHABLE — and no test
 * in this repo ever exercised one before
 * (`grep -rn "MAX_LIFETIME|emptyReads|MAX_EMPTY" tests/` matched nothing),
 * which is why a resource-exhaustion defect survived: the DH-HANDSHAKE phase
 * was outside every budget, and nextFrame()'s internal `continue` on an empty
 * read made MAX_EMPTY_READS structurally unreachable even for the session.
 *
 * Both regressions are pinned here, each by the probe its own hunk fixes:
 *
 *   testSilentPeerIsDroppedBeforeTheHandshakeCompletes
 *       A ticketed peer that never writes a byte. Reverting the nextFrame()
 *       hunk (return '' instead of `continue`) loops forever INSIDE
 *       nextFrame(), so the caller never regains control to cap anything.
 *
 *   testStalledHandshakeAfterFirstDhStepIsDropped
 *       A ticketed peer that sends one well-formed encrypted abridged
 *       req_pq_multi (the answerer answers resPQ) and then goes silent inside
 *       the handshake phase. Reverting the hoisted-budgets hunk leaves the
 *       handshake loop with no cap at all, so it spins for ever.
 *
 * WHY A SHRUNKEN COPY OF THE CLASS: MAX_LIFETIME_S = 900 and
 * MAX_EMPTY_READS = 120 at READ_TIMEOUT_S = 5 mean the honest shipped
 * constants need up to 600s to trip — unusable inside a unit test. PHP class
 * constants are baked in at compile time and cannot be overridden, so the test
 * loads a byte-identical copy of the real source with ONLY those three
 * constants rewritten (and the class renamed so it can coexist). Every line of
 * budget LOGIC under test — the hoisted initialisation, the handshake gate, the
 * `return ''` in nextFrame() — is the shipped source verbatim, so a reverted
 * hunk in src/ still hangs the copy and still fails here.
 *
 * The deadline is a real FORK, not an assertion: the answerer runs in a child
 * and the parent SIGKILLs it if it has not exited, so a hang is a RED test
 * rather than an infinite block. Loopback only: stream_socket_pair, the real
 * Obfuscated2Cipher, a generated RSA key, no network, no credentials.
 *
 * THE TWO SHRUNK BUDGETS MUST NOT OVERLAP. Both probes assert the SAME thing
 * — that the drop was caused by `budget_wedged` — so only the wedged budget is
 * allowed to be reachable inside the deadline. The wedged budget costs
 * MAX_EMPTY_READS x READ_TIMEOUT_S = 2 x 1s ~= 2s. A probe lifetime small
 * enough to compete (3s) leaves a ~1s margin, and `time()`-based lifetime
 * checks race the read-timeout clock: under CPU load (the whole Transport +
 * Ingest + Handler suite, which takes ~61s of wall time before this file is
 * even reached) the elapsed-time check can cross 3s while the second empty
 * read is still in flight, the child logs `budget_lifetime` instead, and the
 * assertion below fails on a CORRECT production build — a coin flip, not a
 * signal. PROBE_MAX_LIFETIME_S is therefore 120: unreachable within the 25s
 * DEADLINE_S, so the wedged budget is the ONLY thing that can trip, and the
 * result is deterministic under any load. The shipped 900s lifetime is still
 * asserted-reachable in principle; the deadline is what guards a genuine hang.
 */
final class VaultMtprotoAnswererHandshakeBudgetTest extends TestCase
{
    /** Hard deadline for the answerer child. A hang is killed, never awaited. */
    private const DEADLINE_S = 25.0;

    /** Poll interval for waiting on child process exit. */
    private const CHILD_WAIT_POLL_US = 20_000;

    /** Poll interval for reading from socket in probe. */
    private const SOCKET_READ_POLL_US = 5000;

    /**
     * Shipped constant, pushed OUT of reach rather than shrunk: the wedged
     * budget is the one under test, so the lifetime budget must never be able
     * to win the race. 120s >> DEADLINE_S (25s) — it cannot fire at all here.
     */
    private const PROBE_MAX_LIFETIME_S = 120;

    private const PROBE_MAX_EMPTY_READS = 2;

    private const PROBE_READ_TIMEOUT_S = 1;

    /** @var array{0: string, 1: string}|null [private PKCS1, public PKCS1] */
    private static ?array $pair = null;

    private static ?string $probeFile = null;

    /** @return array{0: string, 1: string} */
    private static function pair(): array
    {
        if (self::$pair === null) {
            $key = RSA::createKey(2048);
            self::$pair = [$key->toString('PKCS1'), $key->getPublicKey()->toString('PKCS1')];
        }

        return self::$pair;
    }

    /**
     * Materialises the shrunk-budget copy of the shipped answerer ONCE and
     * returns its class name. Every constant rewrite is asserted, so a source
     * refactor that moves a constant fails loudly here instead of silently
     * testing the FULL (unreachable-in-a-test) budget.
     */
    private static function probeClass(): string
    {
        if (self::$probeFile !== null) {
            return self::PROBE_CLASS;
        }

        $source = file_get_contents(self::shippedSource());
        self::assertIsString($source, 'the shipped answerer source must be readable');

        $rewrites = [
            'final class VaultMtprotoAnswerer' => 'final class '.self::PROBE_SHORT,
            'private const READ_TIMEOUT_S = 5;' => 'private const READ_TIMEOUT_S = '.self::PROBE_READ_TIMEOUT_S.';',
            'private const MAX_LIFETIME_S = 900;' => 'private const MAX_LIFETIME_S = '.self::PROBE_MAX_LIFETIME_S.';',
            'private const MAX_EMPTY_READS = 120;' => 'private const MAX_EMPTY_READS = '.self::PROBE_MAX_EMPTY_READS.';',
        ];
        foreach ($rewrites as $from => $to) {
            self::assertStringContainsString($from, $source, "probe rewrite target moved or changed: {$from} — update this test");
            $source = str_replace($from, $to, $source);
        }

        // tempnam() returns string|false; false can only mean sys_get_temp_dir()
        // is unwritable, which is a hard environment failure, not a skip.
        $tmp = tempnam(sys_get_temp_dir(), 'answerer-budget-probe-');
        self::assertIsString($tmp, 'could not create the probe file in the temp dir');
        $file = $tmp.'.php';
        file_put_contents($file, $source);
        require_once $file;
        self::$probeFile = $file;

        return self::PROBE_CLASS;
    }

    /** Bare class name of the shrunk copy (same namespace as the shipped class). */
    private const PROBE_SHORT = 'VaultMtprotoAnswererBudgetProbe';

    /** FQCN of the shrunk copy, as it lives in the shipped class's namespace. */
    private const PROBE_CLASS = '\\MeRezaRezaei\\Teleframe\\Core\\MTProto\\Transport\\'.self::PROBE_SHORT;

    private static function shippedSource(): string
    {
        return dirname(__DIR__, 4).'/src/Core/MTProto/Transport/VaultMtprotoAnswerer.php';
    }

    /**
     * Runs the (possibly shrunk) answerer over a real socket_pair against a
     * scripted peer, and returns how long the answerer held the socket. The
     * answerer runs in a FORKED CHILD so a wedged budget is SIGKILLed by the
     * deadline instead of hanging the suite.
     *
     * @param  string|null  $firstPlainFrame  the one peer frame to send (already
     *                                        abridged + obfuscated2 + WS framed),
     *                                        or null for a peer that stays mute
     */
    private function runAnswererAgainstPeer(?string $firstPlainFrame): float
    {
        if (! function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl_fork required to bound the answerer with a real deadline');
        }

        $probeClass = self::probeClass();
        [$private] = self::pair();

        [$server, $peer] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        // The child's own memory is copy-on-write, so its log records die with
        // it — the logger streams them over a socket pair instead, which is the
        // ONLY way the parent can tell a budget drop from an incidental close.
        [$logRelay, $logChild] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        $logger = new class($logChild) extends AbstractLogger
        {
            /** @param resource $socket */
            public function __construct(private $socket) {}

            /** @param array<string, mixed> $context */
            public function log($level, string|Stringable $message, array $context = []): void
            {
                @fwrite($this->socket, json_encode(['message' => (string) $message])."\n");
            }
        };

        $answerer = new $probeClass(
            static fn (string $method, array $params, ?array $grant): array => ['_' => 'boolTrue'],
            $private,
            $logger,
            ticketRequired: true,
        );

        // The relay already authenticated this socket with a real ticket; the
        // grant is the account id the terminal would have received.
        $cipher = Obfuscated2Cipher::fromHeader(Obfuscated2CodecTest::clientHeader(5, 'abridged'));

        $startedAt = microtime(true);
        $pid = pcntl_fork();
        if ($pid === -1) {
            self::markTestSkipped('pcntl_fork failed');
        }

        if ($pid === 0) {
            fclose($peer);
            fclose($logRelay);
            ($answerer)($server, $cipher, '', 5, 'abridged', 42);
            self::childExit();
        }

        fclose($server);
        fclose($logChild);
        try {
            if ($firstPlainFrame !== null) {
                $this->writeAll($peer, $firstPlainFrame);
            }

            // The peer then says NOTHING and never closes: the only exit left
            // for the answerer is one of its own budgets.
            $elapsed = $this->awaitChildExit($pid, $startedAt);
            $events = $this->budgetEventsFromChild($logRelay);
            self::assertContains(
                'teleframe.mtproto.answerer.budget_wedged',
                $events,
                'the socket must be dropped BY A BUDGET, not by an incidental parse exception or EOF',
            );

            return $elapsed;
        } finally {
            if (is_resource($peer)) {
                fclose($peer);
            }
            if (is_resource($logRelay)) {
                fclose($logRelay);
            }
            @unlink((string) self::$probeFile);
        }
    }

    /**
     * One WS binary message carrying one obfuscated2-abridged plaintext DH
     * request, exactly as a browser would put it on the wire.
     */
    private function clientDhFrame(string $method, array $params): string
    {
        [, $public] = self::pair();
        $cipher = Obfuscated2Cipher::fromHeader(Obfuscated2CodecTest::clientHeader(5, 'abridged'));
        $abridged = FrameCodec::wrapAbridgedPayload(
            PlainConnection::buildPlainEnvelope(
                TLEncoder::encodeObject($method, $params),
                random_int(1, PHP_INT_MAX),
            ),
        );

        return WebSocketFrameCodec::frame(
            WebSocketFrameCodec::OPCODE_BINARY,
            $cipher->xcrypt($abridged, true),
        );
    }

    /**
     * Waits for the answerer child with a REAL deadline: SIGKILL on overrun,
     * so a reverted hunk fails the test instead of wedging the run.
     *
     * @param  float  $startedAt  microtime(true) taken just before the fork
     */
    private function awaitChildExit(int $pid, float $startedAt): float
    {
        $deadline = $startedAt + self::DEADLINE_S;
        $status = null;
        while (microtime(true) < $deadline) {
            if (pcntl_waitpid($pid, $status, WNOHANG) === $pid) {
                self::assertSame(
                    0,
                    (int) pcntl_wexitstatus($status),
                    'the answerer must exit cleanly when a budget trips',
                );

                return microtime(true) - $startedAt;
            }
            usleep(self::CHILD_WAIT_POLL_US);
        }

        @posix_kill($pid, SIGKILL);
        pcntl_waitpid($pid, $status);

        self::fail(sprintf(
            'the answerer still held the socket after %.1fs (budget deadline %.1fs): a per-connection budget is unreachable — '
            .'a silent peer can pin the process for ever',
            microtime(true) - $startedAt,
            self::DEADLINE_S,
        ));
    }

    /** @param resource $socket */
    private function writeAll($socket, string $bytes): void
    {
        $written = 0;
        $total = strlen($bytes);
        while ($written < $total) {
            $n = @fwrite($socket, substr($bytes, $written));
            if ($n === false || $n === 0) {
                self::fail('peer write failed at offset '.$written);
            }
            $written += $n;
        }
    }

    /**
     * Terminates the forked child WITHOUT PHP's shutdown sequence (a forked
     * PHPUnit child would flush buffers and re-run teardown otherwise).
     */
    private static function childExit(): never
    {
        if (extension_loaded('FFI')) {
            try {
                $libc = \FFI::cdef('void _exit(int status);', 'libc.so.6');
                $libc->_exit(0);
            } catch (\Throwable) {
                // FFI disabled or not a glibc host — exit normally.
            }
        }

        exit(0);
    }

    /**
     * A ticketed peer that redeems the relay ticket and then never writes a
     * byte. The answerer's very first nextFrame() must hand control BACK to
     * its caller on the read timeout — otherwise it re-reads inside itself for
     * ever and no budget is ever consulted.
     */
    public function test_silent_peer_is_dropped_before_the_handshake_completes(): void
    {
        $elapsed = $this->runAnswererAgainstPeer(null);

        self::assertGreaterThan(0.0, $elapsed);
        self::assertLessThan(
            self::DEADLINE_S,
            $elapsed,
            'a peer that never writes must be dropped by a per-connection budget, not held for ever',
        );
    }

    /**
     * A ticketed peer that sends ONE well-formed encrypted abridged
     * req_pq_multi (the answerer answers resPQ and asks for req_DH_params)
     * and then goes silent — the handshake stalls with a live socket. The
     * handshake phase must carry the same lifetime + empty-read caps as the
     * session phase.
     */
    public function test_stalled_handshake_after_first_dh_step_is_dropped(): void
    {
        $elapsed = $this->runAnswererAgainstPeer(
            $this->clientDhFrame('req_pq_multi', ['nonce' => random_bytes(16)]),
        );

        self::assertGreaterThan(0.0, $elapsed);
        self::assertLessThan(
            self::DEADLINE_S,
            $elapsed,
            'a handshake that stalls after req_pq_multi must be dropped by a budget, not held for ever',
        );
    }

    /**
     * The shipped class must be the one whose source the probe copies: if the
     * probe were ever built from a stale file, the two regressions below would
     * prove nothing. Cheap guard, no runtime cost.
     */
    public function test_probe_is_generated_from_the_shipped_answerer_source(): void
    {
        $source = file_get_contents(self::shippedSource());
        self::assertIsString($source);
        self::assertStringContainsString('final class VaultMtprotoAnswerer', $source);
        self::assertTrue(class_exists(VaultMtprotoAnswerer::class), 'the shipped answerer must stay loadable');
        self::assertStringContainsString(self::PROBE_CLASS, (string) self::probeClass());
    }

    /**
     * The budget events the answerer logged, read back through a socket so the
     * FORKED child can hand them to the parent (the child's own memory is
     * copy-on-write and its copy of any array dies with it).
     *
     * @return list<string> the `message` of every budget_* event
     */
    private function budgetEventsFromChild($socket): array
    {
        $events = [];
        $carry = '';
        $deadline = microtime(true) + 2.0;
        @stream_set_blocking($socket, false);
        while (microtime(true) < $deadline) {
            $chunk = @fread($socket, 8192);
            if ($chunk === false || $chunk === '') {
                usleep(self::SOCKET_READ_POLL_US);

                continue;
            }
            $carry .= $chunk;
            while (($nl = strpos($carry, "\n")) !== false) {
                $line = substr($carry, 0, $nl);
                $carry = substr($carry, $nl + 1);
                $decoded = json_decode($line, true);
                if (is_array($decoded) && isset($decoded['message'])) {
                    $events[] = (string) $decoded['message'];
                }
            }
        }

        return $events;
    }
}
