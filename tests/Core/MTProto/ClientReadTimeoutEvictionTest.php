<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Tests\MTProto;

use MeRezaRezaei\Teleframe\Core\MTProto\Client;
use MeRezaRezaei\Teleframe\Core\MTProto\Connection\EncryptedConnection;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\StreamSocket;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayLogger;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use Throwable;

/**
 * `Client::call()` treated EVERY transport RuntimeException as a dead connection.
 *
 * That was correct while the only transport failure it could see was an EOF, and
 * wrong the moment `StreamSocket::read()` learned to tell an EOF from a TIMEOUT.
 * StreamSocket now rides a mute-but-open peer out under a bounded budget and then
 * raises `StreamSocket: read timed out after 30s … the socket is still open, the
 * peer has not closed it` — an error whose whole content is that the connection
 * SURVIVED. Client answered it by closing the socket and nulling the cache, so
 * the retry StreamSocket bought was thrown away and the next call paid a fresh
 * handshake against a DC that had merely been slow. The predicate that separates
 * the two causes already existed for this caller: `StreamSocket::isReadTimeout()`.
 *
 * So the policy this file pins is CAUSE-AWARE eviction:
 *
 *   1. A READ TIMEOUT is not evidence of a dead connection — it is evidence of
 *      an idle one. The request is put on the wire again, unchanged, ON THE SAME
 *      cached socket, at most ONCE.
 *   2. A GENUINE EOF / hard read error is still a dead connection: close it,
 *      drop the cache, rethrow, and let the next call reconnect.
 *   3. The retry is BOUNDED. Two attempts total (the original plus one resend),
 *      and the connection is dropped if the second one times out too, because a
 *      DC that missed two consecutive read budgets is not slow — it is gone.
 *
 * The transport failures here are REAL: they are raised by StreamSocket on real
 * socket pairs (mute, closed, and an unopenable handle), with only the retry
 * budget cut to milliseconds so the probes cost fractions of a second. Nothing
 * hand-types a timeout message, so every assertion below is a statement about
 * StreamSocket::isReadTimeout() rather than about a string literal.
 *
 * A HANG IS A RED TEST, NOT A WEDGED SUITE: the bounded probes run under a real
 * deadline — forked and SIGKILLed where the host allows it, measured against the
 * same budget where it does not — so an unbounded ladder FAILS instead of wedging
 * the suite, and a host that cannot enforce a deadline says so in the canonical
 * UNBOUNDED token rather than passing quietly.
 */
final class ClientReadTimeoutEvictionTest extends TestCase
{
    /** Hard deadline for the forked bound probe. Overrun ⇒ SIGKILL and a failure, never a block. */
    private const DEADLINE_S = 15.0;

    /** Poll interval for child process wedge simulation. */
    private const WEDGE_POLL_US = 10_000;

    /** Poll interval for waiting on child process exit. */
    private const CHILD_WAIT_POLL_US = 20_000;

    /**
     * A read timeout, raised by the REAL transport on a REAL (mute) socket pair.
     *
     * The per-read stream timeout is 0.2s and the retry budget 10ms, so the very
     * first timed-out read already exceeds the budget and the probe costs ~0.2s
     * instead of the shipped 30s.
     */
    public function test_a_read_timeout_is_distinguishable_from_a_closed_peer(): void
    {
        $timeout = self::transportTimeout();

        self::assertTrue(
            StreamSocket::isReadTimeout($timeout),
            'the transport must raise this as a TIMEOUT — a cause the caller can act on',
        );
        self::assertStringNotContainsString('EOF', $timeout->getMessage(), 'a timeout must not wear an EOF\'s message');
        self::assertStringContainsString('still open', $timeout->getMessage(), 'the message must say the socket survived');

        $eof = self::transportEof();

        self::assertFalse(StreamSocket::isReadTimeout($eof), 'a closed peer is NOT a timeout');
        self::assertStringContainsString('EOF', $eof->getMessage());

        // The third cause: the read FAILED. Also not a timeout, so also not retried.
        $hard = self::hardReadError();

        self::assertFalse(StreamSocket::isReadTimeout($hard), 'a hard read error is not a timeout');
    }

    /**
     * THE FIX: a timeout is retried on the SAME cached connection and the
     * connection stays cached — no close, no second handshake.
     */
    public function test_a_read_timeout_is_retried_on_the_same_connection_and_keeps_it_cached(): void
    {
        $answer = self::nearestDc();
        $conn = $this->scripted([self::transportTimeout(), $answer]);
        $logger = new ArrayLogger;
        $client = new ScriptedDialClient([$conn], $this->session(), $logger);

        $got = $client->call('help.getNearestDc');

        self::assertSame($answer, $got, 'the retry must return the answer the peer eventually gave');
        self::assertSame(2, $conn->calls, 'the timeout must be retried exactly once');
        self::assertSame(1, $client->freshConnections, 'the retry must ride the CACHED connection, never dial a second one');
        self::assertSame(0, $conn->closes, 'a timeout means the socket is still open: it must NOT be closed');
        self::assertSame($conn, $this->cachedConn($client), 'the connection must still be cached for the next call');

        // "Same socket, same request": the retry re-sends the identical call, so a
        // side-effecting method keeps the random_id that makes an MTProto resend safe.
        self::assertSame(
            [['help.getNearestDc', []], ['help.getNearestDc', []]],
            $conn->seen,
            'the retry must re-send the same method and params, unchanged',
        );

        // And it must be visible in the ledger, at warning level, separate from the
        // terminal 'rpc.failed' record: the operator's question is always "did the
        // DC go away or was it just slow?".
        $retries = $this->recordsOf($logger, Client::READ_TIMEOUT_RETRY_EVENT);
        self::assertCount(1, $retries, 'the retried timeout must be logged once, as its own event');
        self::assertSame('warning', $retries[0]['level']);
        self::assertSame(1, $retries[0]['context']['attempt']);
        self::assertSame(Client::READ_TIMEOUT_MAX_ATTEMPTS, $retries[0]['context']['max_attempts']);
        self::assertSame([], $this->recordsOf($logger, 'teleframe.mtproto.rpc.failed'), 'a retried timeout is not a failed call');
    }

    /**
     * NO REGRESSION: a genuine EOF is still a dead connection. It is raised at
     * once (never retried — a closed socket stays closed), the cached connection
     * is closed and dropped, and the NEXT call dials a fresh one.
     */
    public function test_a_genuine_eof_still_evicts_and_the_next_call_reconnects(): void
    {
        $dead = $this->scripted([self::transportEof()]);
        $replacement = $this->scripted([self::nearestDc()]);
        $client = new ScriptedDialClient([$dead, $replacement], $this->session(), new ArrayLogger);

        $thrown = null;
        try {
            $client->call('help.getNearestDc');
            self::fail('a closed peer must still raise');
        } catch (RuntimeException $e) {
            $thrown = $e;
        }

        self::assertNotNull($thrown);
        self::assertStringContainsString('EOF', $thrown->getMessage(), 'the historic EOF message must be preserved');
        self::assertSame(1, $dead->calls, 'an EOF must be raised on the FIRST read — retrying a closed socket is a hang');
        self::assertSame(1, $dead->closes, 'a closed peer must be closed');
        self::assertNull($this->cachedConn($client), 'a dead connection must be evicted');
        self::assertSame(1, $client->freshConnections);

        // The reconnect the eviction buys: the next call dials instead of reusing
        // the corpse, and the fresh connection answers normally.
        self::assertSame(self::nearestDc(), $client->call('help.getNearestDc'));
        self::assertSame(2, $client->freshConnections, 'the next call must dial a NEW connection');
        self::assertSame($replacement, $this->cachedConn($client));
    }

    /**
     * NO REGRESSION, third cause: a hard read error is not a timeout either, so it
     * is not retried and it still evicts.
     */
    public function test_a_hard_read_error_still_evicts_without_a_retry(): void
    {
        $conn = $this->scripted([self::hardReadError(), self::nearestDc()]);
        $client = new ScriptedDialClient([$conn], $this->session(), new ArrayLogger);

        $thrown = null;
        try {
            $client->call('help.getNearestDc');
            self::fail('a failed read must raise');
        } catch (RuntimeException $e) {
            $thrown = $e;
        }

        self::assertNotNull($thrown);
        self::assertStringContainsString('read failed', $thrown->getMessage());
        self::assertSame(1, $conn->calls, 'a failed read must not be retried');
        self::assertSame(1, $conn->closes);
        self::assertNull($this->cachedConn($client), 'a hard read error must evict the connection');
    }

    /**
     * THE BOUND. Two attempts, no more: the script holds a THIRD answer, and it
     * must never be consumed — otherwise the retry ladder is unbounded and a slow
     * DC is a silent hang instead of a bounded failure.
     *
     * Run under a deadline so an unbounded ladder FAILS instead of wedging the
     * suite. Where the host can fork, the parent SIGKILLs the child at DEADLINE_S.
     * Where it cannot, the probe runs inline and is MEASURED against the same
     * budget — a weaker guarantee (it can name an overrun, not prevent one) but a
     * real one, and every assertion below still runs. Never a skip.
     */
    public function test_the_retry_is_bounded_and_gives_up_after_one_resend(): void
    {
        $conn = $this->scripted([
            self::transportTimeout(),
            self::transportTimeout(),
            self::nearestDc(), // unreachable on purpose: the bound is 2 attempts
        ]);
        $client = new ScriptedDialClient([$conn], $this->session(), new ArrayLogger);

        $outcome = $this->underDeadline(static function () use ($client, $conn): array {
            $thrown = null;
            try {
                $client->call('help.getNearestDc');
            } catch (RuntimeException $e) {
                $thrown = $e;
            }

            // The eviction happens INSIDE the forked child, so its observable
            // effects are read here — the parent's copy of these objects is
            // untouched copy-on-write state and proves nothing.
            return [
                'threw' => $thrown !== null,
                'message' => $thrown?->getMessage() ?? '',
                'is_timeout' => $thrown !== null && StreamSocket::isReadTimeout($thrown),
                'calls' => $conn->calls,
                'closes' => $conn->closes,
                'cached' => (new ReflectionProperty(Client::class, 'conn'))->getValue($client) !== null,
            ];
        });

        self::assertTrue((bool) $outcome['threw'], 'a twice-timed-out call must GIVE UP, not keep retrying: '.($outcome['message'] ?? ''));
        self::assertSame(2, $outcome['calls'], 'exactly TWO attempts — the original plus one retry — and never a third');
        self::assertTrue((bool) $outcome['is_timeout'], 'the failure handed back must still be the timeout the caller can read');
        self::assertLessThan(
            self::DEADLINE_S,
            (float) $outcome['elapsed'],
            'the retry ladder must terminate on its own, never on the deadline',
        );

        // Two consecutive read budgets missed means the DC is gone, not slow: the
        // connection must not linger in the cache as a poisoned one.
        self::assertSame(1, $outcome['closes'], 'a twice-timed-out connection must be dropped');
        self::assertFalse((bool) $outcome['cached'], 'a twice-timed-out connection must not stay cached');
    }

    /**
     * Batch parity: callMany() evicted on ANY transport RuntimeException too, so a
     * timed-out batch paid the same pointless handshake. Same policy, same bound.
     */
    public function test_call_many_applies_the_same_cause_aware_policy(): void
    {
        $answer = self::nearestDc();
        $slow = $this->scripted([self::transportTimeout(), $answer]);
        $client = new ScriptedDialClient([$slow], $this->session(), new ArrayLogger);

        $requests = [
            'a' => ['method' => 'help.getNearestDc', 'params' => []],
            'b' => ['method' => 'help.getConfig', 'params' => []],
        ];

        $got = $client->callMany($requests);

        self::assertSame(['a' => $answer, 'b' => $answer], $got, 'keys and order preserved through the retry');
        self::assertSame(2, $slow->batches, 'the timed-out batch must be retried exactly once');
        self::assertSame(1, $client->freshConnections, 'the batch retry must ride the cached connection');
        self::assertSame(0, $slow->closes, 'a timed-out batch must not close a still-open socket');
        self::assertSame($slow, $this->cachedConn($client));

        $dead = $this->scripted([self::transportEof()]);
        $batchClient = new ScriptedDialClient([$dead], $this->session(), new ArrayLogger);

        $thrown = null;
        try {
            $batchClient->callMany($requests);
            self::fail('a closed peer must still raise on the batch path');
        } catch (RuntimeException $e) {
            $thrown = $e;
        }

        self::assertNotNull($thrown);
        self::assertStringContainsString('EOF', $thrown->getMessage());
        self::assertSame(1, $dead->batches, 'an EOF batch must not be retried');
        self::assertSame(1, $dead->closes);
        self::assertNull($this->cachedConn($batchClient), 'an EOF batch must still evict the connection');
    }

    /**
     * The bound above is only worth having if VIOLATING it fails. This proves the
     * deadline really fires: a child that never returns is SIGKILLed and the test
     * fails with an explicit UNBOUNDED message, in about a second. Without this,
     * the bound would be a comment — an unbounded ladder would simply hang the
     * suite instead of failing it.
     *
     * On a host WITHOUT pcntl the deadline cannot be enforced at all, so the harness
     * reports itself UNBOUNDED and this test verifies THAT reporting contract instead
     * of the SIGKILL: a real guarantee on such a host, never a vacuous green and never
     * a skip.
     */
    public function test_the_deadline_fails_a_hang_instead_of_wedging_the_suite(): void
    {
        $startedAt = microtime(true);

        $thrown = null;
        try {
            $this->inBoundedChild(static function (): array {
                while (true) {
                    usleep(self::WEDGE_POLL_US); // a wedge: never returns, never raises
                }
            }, 1.0);
        } catch (AssertionFailedError $e) {
            $thrown = $e;
        }

        self::assertNotNull($thrown, 'the deadline must FAIL the test, not block on it');
        self::assertStringContainsString('UNBOUNDED', $thrown->getMessage(), 'the failure must name the real defect');
        self::assertLessThan(10.0, microtime(true) - $startedAt, 'and it must do so at the deadline, not later');
    }

    // -------------------------------------------------------------------------
    // Harness
    // -------------------------------------------------------------------------

    /**
     * @param  list<array<string, mixed>|Throwable>  $script
     */
    private function scripted(array $script): ScriptedConnection
    {
        $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        self::assertIsArray($pair, 'stream_socket_pair is required to build a socket-bearing connection');

        /** @var array{0: resource, 1: resource} $pair */
        $conn = new ScriptedConnection($this->session(), $pair[0]);
        $conn->script = $script;

        return $conn;
    }

    private function session(): SessionData
    {
        return new SessionData(dcId: 2, authKey: random_bytes(256));
    }

    /** @return array<string, mixed> */
    private static function nearestDc(): array
    {
        return ['_' => 'nearestDc', 'country' => 'DE', 'this_dc' => 2, 'nearest_dc' => 2];
    }

    private static function cachedConn(Client $client): mixed
    {
        return (new ReflectionProperty(Client::class, 'conn'))->getValue($client);
    }

    /**
     * The REAL transport's timeout: a mute-but-open peer, read until the (tiny)
     * retry budget is spent.
     */
    private static function transportTimeout(): RuntimeException
    {
        [$local, $peer] = self::socketPair();
        stream_set_timeout($local, 0, 200000); // 0.2s: a blocking read that yields nothing

        try {
            StreamSocket::read($local, 16, null, 0.01); // budget spent on the first timeout

            throw new RuntimeException('harness bug: a mute peer returned bytes');
        } catch (RuntimeException $e) {
            if (! StreamSocket::isReadTimeout($e)) {
                throw new RuntimeException('harness bug: the transport raised a non-timeout: '.$e->getMessage());
            }

            return $e;
        } finally {
            fclose($local);
            fclose($peer);
        }
    }

    /** The REAL transport's EOF: the peer closed. */
    private static function transportEof(): RuntimeException
    {
        [$local, $peer] = self::socketPair();
        fclose($peer);
        stream_set_timeout($local, 1);

        try {
            StreamSocket::read($local, 16, null, 5.0);

            throw new RuntimeException('harness bug: a closed peer returned bytes');
        } catch (RuntimeException $e) {
            if (StreamSocket::isReadTimeout($e)) {
                throw new RuntimeException('harness bug: a closed peer was reported as a timeout');
            }

            return $e;
        } finally {
            fclose($local);
        }
    }

    /** The REAL transport's hard read error: fread() === false, no timeout. */
    private static function hardReadError(): RuntimeException
    {
        $handle = @fopen(sys_get_temp_dir(), 'r');
        self::assertIsResource($handle, 'the temp dir must be openable as a stream handle');

        try {
            StreamSocket::read($handle, 16);

            throw new RuntimeException('harness bug: an unopenable handle returned bytes');
        } catch (RuntimeException $e) {
            return $e;
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return array{0: resource, 1: resource}
     */
    private static function socketPair(): array
    {
        $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        if (! is_array($pair)) {
            throw new RuntimeException('stream_socket_pair is required to probe the transport offline');
        }

        /** @var array{0: resource, 1: resource} $pair */
        return $pair;
    }

    /**
     * Runs $probe in a FORKED child that is KILLED at the deadline. Strict: a host
     * that cannot fork cannot honour a deadline at all, so this runner FAILS
     * UNBOUNDED rather than pretending — see inBoundedChild().
     *
     * @return array<string, mixed>
     */
    private function inBoundedChild(callable $probe, float $deadlineSeconds = self::DEADLINE_S): array
    {
        if (! function_exists('pcntl_fork')) {
            // A probe that cannot bound itself must ANNOUNCE that in the canonical
            // UNBOUNDED token — the very word this harness's own overrun branch
            // (readReport) uses — and not merely in prose. Otherwise a reader (or a
            // machine) cannot tell an UNBOUNDED probe from a bounded one that merely
            // complained. It is a real failure, not a skip: the honest report of a
            // host that cannot enforce a deadline is that the probe is UNBOUNDED,
            // said loudly.
            self::fail(
                'UNBOUNDED: pcntl_fork is required to bound the probe with a real deadline'
                .' — this host cannot enforce a deadline, so the probe is UNBOUNDED'
            );
        }

        return $this->forkedProbe($probe, $deadlineSeconds);
    }

    /**
     * The SAME deadline, degraded honestly on a host that cannot fork — used by a
     * probe whose own contract is "this terminates on its own, inside the budget".
     *
     * Forked, the deadline KILLS the probe at the budget. Inline, nothing can kill
     * it, but the property under test still holds and is still measured: the ladder
     * must finish BY ITSELF and INSIDE the budget. A skip would drop every
     * assertion the caller makes about the report (the attempt count above all),
     * and a skipped gate is indistinguishable from a green one in the CI rollup —
     * so it runs degraded instead, and says which degradation it took.
     *
     * @return array<string, mixed>
     */
    private function underDeadline(callable $probe, float $deadlineSeconds = self::DEADLINE_S): array
    {
        if (! function_exists('pcntl_fork')) {
            return $this->inlineProbe($probe, $deadlineSeconds);
        }

        return $this->forkedProbe($probe, $deadlineSeconds);
    }

    /**
     * Runs $probe inline and MEASURES it. An overrun is reported as UNBOUNDED
     * rather than left silent — the honest limit is stated here and nowhere is it
     * papered over: an inline probe that overruns is detected only AFTER it
     * returns, so this runner cannot rescue a wedged suite, it can only name one.
     * What it does guarantee is that the probe TERMINATED, at the same budget the
     * forked path enforces.
     *
     * @return array<string, mixed>
     */
    private function inlineProbe(callable $probe, float $deadlineSeconds): array
    {
        $startedAt = microtime(true);

        try {
            $report = ['ok' => true] + $probe();
        } catch (Throwable $e) {
            $report = ['ok' => false, 'error' => $e->getMessage(), 'class' => $e::class];
        }

        $elapsed = microtime(true) - $startedAt;
        $report['elapsed'] = $elapsed;

        if ($elapsed >= $deadlineSeconds) {
            self::fail(sprintf(
                'UNBOUNDED: the probe ran %.3fs inline against a %.1fs budget on a host with no pcntl_fork to '
                .'kill it — the ladder did not terminate on its own inside the budget',
                $elapsed,
                $deadlineSeconds,
            ));
        }

        /** @var array<string, mixed> $report */
        return $report;
    }

    /**
     * @return array<string, mixed>
     */
    private function forkedProbe(callable $probe, float $deadlineSeconds): array
    {
        [$reportRead, $reportWrite] = self::socketPair();
        $startedAt = microtime(true);

        $pid = pcntl_fork();
        if ($pid === -1) {
            self::markTestSkipped('pcntl_fork failed');
        }

        if ($pid === 0) {
            fclose($reportRead);
            try {
                $report = ['ok' => true] + $probe();
            } catch (Throwable $e) {
                $report = ['ok' => false, 'error' => $e->getMessage(), 'class' => $e::class];
            }
            @fwrite($reportWrite, (string) json_encode($report + ['elapsed' => microtime(true) - $startedAt]));
            self::childExit();
        }

        fclose($reportWrite);
        try {
            return $this->readReport($reportRead, $pid, $startedAt, $deadlineSeconds);
        } finally {
            if (is_resource($reportRead)) {
                fclose($reportRead);
            }
        }
    }

    /**
     * @param  resource  $reportRead
     * @return array<string, mixed>
     */
    private function readReport($reportRead, int $pid, float $startedAt, float $deadlineSeconds): array
    {
        stream_set_blocking($reportRead, false);
        $deadline = $startedAt + $deadlineSeconds;
        $exited = false;
        $status = null;

        while (microtime(true) < $deadline) {
            if (pcntl_waitpid($pid, $status, WNOHANG) === $pid) {
                $exited = true;
                break;
            }
            usleep(self::CHILD_WAIT_POLL_US);
        }

        $decoded = json_decode((string) @fread($reportRead, 8192), true);
        if (is_array($decoded)) {
            /** @var array<string, mixed> $decoded */
            return $decoded;
        }

        if ($exited) {
            self::fail('the probe child exited without reporting (child exit status: '.($status >> 8).')');
        }

        @posix_kill($pid, SIGKILL);
        pcntl_waitpid($pid, $status);
        self::fail(sprintf(
            'the call still held the connection after %.1fs (deadline %.1fs): the retry ladder is UNBOUNDED — '
            .'a read timeout has been turned into a hang',
            microtime(true) - $startedAt,
            $deadlineSeconds,
        ));
    }

    /**
     * @return list<array{level: string, message: string, context: array<string, mixed>}>
     */
    private function recordsOf(ArrayLogger $logger, string $event): array
    {
        return array_values(array_filter(
            $logger->records,
            static fn (array $record): bool => $record['message'] === $event
        ));
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
            } catch (Throwable) {
                // FFI disabled or not a glibc host — exit normally.
            }
        }

        exit(0);
    }
}

/**
 * An EncryptedConnection whose wire is SCRIPTED: every call()/callBatch() consumes
 * the next step, which is either the decoded result to return or the throwable to
 * raise. It counts calls, closes and pings, and records what it was asked to send,
 * so a test can assert what Client did to the connection it was caching.
 */
final class ScriptedConnection extends EncryptedConnection
{
    public int $calls = 0;

    public int $batches = 0;

    public int $closes = 0;

    /** @var list<array{0: string, 1: array<string, mixed>}> */
    public array $seen = [];

    /**
     * One step per wire attempt: the decoded result to return, or the throwable to
     * raise. Assigned AFTER construction because EncryptedConnection's constructor
     * is final.
     *
     * @var list<array<string, mixed>|Throwable>
     */
    public array $script = [];

    public function call(string $constructor, array $args = []): array
    {
        $this->calls++;
        $this->seen[] = [$constructor, $args];

        return $this->nextStep();
    }

    public function callBatch(array $bodies): array
    {
        $this->batches++;
        $step = $this->nextStep();

        $results = [];
        foreach (array_keys($bodies) as $key) {
            $results[$key] = $step;
        }

        return $results;
    }

    /** Steady state: the layer is already established, so no first-call wrap. */
    public function isInited(): bool
    {
        return true;
    }

    public function ping(int $disconnectDelay = 50): array
    {
        return ['_' => 'pong'];
    }

    public function close(): void
    {
        $this->closes++;
        parent::close();
    }

    /**
     * @return array<string, mixed>
     */
    private function nextStep(): array
    {
        $step = $this->script[$this->calls + $this->batches - 1] ?? null;

        if ($step instanceof Throwable) {
            throw $step;
        }

        if (! is_array($step)) {
            throw new RuntimeException('ScriptedConnection: the script ran out of steps (an unbounded retry?)');
        }

        return $step;
    }
}

/**
 * A Client with the DIAL replaced by a scripted connection — and with the real
 * CACHE semantics left intact, because the cache is what this defect is about: a
 * cached connection is reused as-is, and only an EMPTY cache asks for a fresh one.
 * So `freshConnections` counts handshakes, and eviction shows up as the next
 * increment.
 */
final class ScriptedDialClient extends Client
{
    public int $freshConnections = 0;

    /**
     * @param  list<EncryptedConnection>  $fresh  handed out in order, one per empty cache
     */
    public function __construct(private array $fresh, SessionData $session, ArrayLogger $logger)
    {
        parent::__construct(apiId: 1, apiHash: 'h', session: $session, live: true, logger: $logger);
    }

    protected function ensureConnection(): EncryptedConnection
    {
        $cached = (new ReflectionProperty(Client::class, 'conn'))->getValue($this);
        if ($cached instanceof EncryptedConnection) {
            return $cached;
        }

        $conn = $this->fresh[$this->freshConnections]
            ?? throw new RuntimeException('ScriptedDialClient: no scripted connection left to dial');
        $this->freshConnections++;
        (new ReflectionProperty(Client::class, 'conn'))->setValue($this, $conn);

        return $conn;
    }
}
