<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core\MTProto\Transport;

use MeRezaRezaei\Teleframe\Core\MTProto\Transport\FrameCodec;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\StreamSocket;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayLogger;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

/**
 * `StreamSocket::read()` reported EVERY empty fread() as a fatal EOF.
 *
 * That claim is false for a blocking socket with a read timeout: stream_set_timeout()
 * makes fread() return '' when the timeout elapses with no bytes available, and a
 * TLS stream can also yield a transient zero-byte read mid-frame. Only ONE of those
 * is an EOF. The other two are an IDLE CONNECTION, and collapsing them is what killed
 * live ingest runs with "StreamSocket: EOF while reading" — a TIMEOUT wearing an EOF's
 * name, propagated verbatim out of FrameCodec::readExact() into Client::call(), which
 * treats any transport RuntimeException as a dead connection (evict + rethrow).
 *
 * EVERY probe here is FAKE-SOCKET: stream_socket_pair, or a plain local file handle
 * for the hard-read-error branch. No network, no credentials, no live Telegram.
 *
 * THE TWO PROPERTIES THIS FILE PINS, which pull in opposite directions:
 *
 *   1. A timeout is NOT an EOF (testAReadThatTimesOutIsNotReportedAsAnEof,
 *      testATimeoutMidFrameDoesNotDesyncTheStream, testTheRetryGivesUpWithinItsBudget,
 *      testAReadTimeoutIsLoggedSeparatelyFromAnEof, testAHardReadErrorIsNeither).
 *      A timeout is ridden out under a BOUNDED budget, and when the budget is spent
 *      it raises something a caller can tell from a closed connection.
 *
 *   2. read() stays a SINGLE read of AT MOST $length (testReadReturnsAtMostLengthWithoutWaiting).
 *      The MTProto desync protection is the whole reason read()/readExact() are split:
 *      a caller that treats a short read as a whole buffer corrupts every frame after
 *      it. The fix must never become "loop and accumulate inside read()".
 *
 * A HANG IS A RED TEST, NOT A WEDGED SUITE: every probe that could spin runs in a
 * forked child that the parent SIGKILLs at DEADLINE_S, mirroring the spirit of
 * VaultMtprotoAnswererHandshakeBudgetTest.
 */
final class StreamSocketReadTimeoutTest extends TestCase
{
    /** Hard deadline for a forked probe. Overrun ⇒ SIGKILL and a failure, never a block. */
    private const DEADLINE_S = 20.0;

    /** The probe socket's per-read timeout: SHORT, so "timed out" costs a second, not a minute. */
    private const STREAM_TIMEOUT_S = 1;

    /** The peer's silence must EXCEED the read timeout for a timeout to happen at all. */
    private const PEER_PAUSE_S = 2.0;

    public function testAReadThatTimesOutIsNotReportedAsAnEof(): void
    {
        // The peer is silent for longer than the read timeout, then answers. A timeout
        // is an idle connection, so the bytes must still be picked up.
        [$local, $peer] = $this->socketPair();
        $this->writeLaterInChild($peer, 'PONG');

        stream_set_timeout($local, self::STREAM_TIMEOUT_S);

        self::assertSame(
            'PONG',
            StreamSocket::readExact($local, 4),
            'a read that merely TIMED OUT must not be reported as an EOF — the bytes arrive a moment later',
        );

        fclose($local);
    }

    /**
     * The desync half, at the layer the defect was actually observed on: a length
     * prefix arrives, then the payload stalls past the read timeout, then the rest
     * arrives. Framing must still succeed — and readExact() must return the payload
     * in order, with nothing dropped and nothing duplicated by the retry.
     */
    public function testATimeoutMidFrameDoesNotDesyncTheStream(): void
    {
        $payload = str_repeat('MTPROTO-PAYLOAD-', 8); // 128 bytes: split across the pause
        $frame = FrameCodec::wrapPayload($payload);
        $splitAt = 4 + (int) (strlen($payload) / 2); // the 4-byte length prefix + half the body

        [$local, $peer] = $this->socketPair();
        $this->writeInTwoHalvesInChild($peer, substr($frame, 0, $splitAt), substr($frame, $splitAt));

        stream_set_timeout($local, self::STREAM_TIMEOUT_S);

        self::assertSame(
            $payload,
            FrameCodec::receiveMessage($local),
            'a timeout between the length prefix and the body must not desync the frame',
        );

        fclose($local);
    }

    /**
     * THE REGRESSION GUARD: read() must keep returning "up to $length" and must NOT
     * wait for the remainder. Making read() loop-and-accumulate would silence the
     * timeout at the cost of reintroducing frame desync, which is a net loss.
     */
    public function testReadReturnsAtMostLengthWithoutWaiting(): void
    {
        [$local, $peer] = $this->socketPair();
        $this->writeAll($peer, 'HEAD'); // queued before the probe starts, so no race

        stream_set_timeout($local, self::STREAM_TIMEOUT_S);

        $outcome = $this->inBoundedChild(fn () => ['value' => StreamSocket::read($local, 8)]);

        self::assertTrue($outcome['ok'], 'read() must not raise: '.($outcome['message'] ?? ''));
        self::assertSame('HEAD', $outcome['value'], 'read() must return the bytes that HAVE arrived, not the ones asked for');
        self::assertLessThan(
            1.0,
            (float) $outcome['elapsed'],
            'read() must be a single read: it may not block to fill the requested length',
        );

        fclose($local);
        fclose($peer);
    }

    /**
     * A GENUINE EOF — the peer closed — must still raise PROMPTLY and keep its
     * historic message. It must never be retried: a closed socket stays closed, so
     * retrying it converts the real failure into the exact hang this fix forbids.
     */
    public function testAGenuineEofStillRaisesPromptly(): void
    {
        [$local, $peer] = $this->socketPair();
        fclose($peer);

        stream_set_timeout($local, self::STREAM_TIMEOUT_S);

        $outcome = $this->inBoundedChild(function () use ($local) {
            StreamSocket::readExact($local, 64);

            return ['value' => 'NO EXCEPTION'];
        });

        self::assertFalse($outcome['ok'], 'a closed peer must raise, not return');
        self::assertSame('RuntimeException', $outcome['class'], 'a genuine EOF keeps raising a RuntimeException');
        self::assertStringContainsString('EOF', (string) $outcome['message'], 'the genuine-EOF message must be preserved');
        self::assertFalse((bool) $outcome['is_timeout'], 'a genuine EOF must NOT be reported as a timeout');
        self::assertLessThan(
            2.0,
            (float) $outcome['elapsed'],
            'a genuine EOF must raise on the first read, not be retried until some budget runs out',
        );

        fclose($local);
    }

    /**
     * THE BOUND: a peer that never writes and never closes must not hold the caller
     * for ever. The retry is bounded by a wall-clock budget, and the budget is passed
     * IN so this probe costs ~2s instead of the shipped default.
     */
    public function testTheRetryGivesUpWithinItsBudget(): void
    {
        [$local, $peer] = $this->socketPair(); // peer stays open and mute for ever
        stream_set_timeout($local, self::STREAM_TIMEOUT_S);

        $budget = 2.0;
        $outcome = $this->inBoundedChild(function () use ($local, $budget) {
            StreamSocket::read($local, 16, null, $budget);

            return ['value' => 'NO EXCEPTION'];
        });

        self::assertFalse(
            $outcome['ok'],
            'a mute peer must eventually raise, not return: '.(string) ($outcome['value'] ?? ''),
        );
        self::assertSame('RuntimeException', $outcome['class']);
        self::assertTrue(
            (bool) $outcome['is_timeout'],
            'an exhausted retry budget must raise something a caller can tell from an EOF, got: '.$outcome['message'],
        );
        self::assertStringNotContainsString(
            'EOF',
            (string) $outcome['message'],
            'the timeout message must not reuse the EOF wording, or it is indistinguishable again',
        );
        self::assertGreaterThan(
            $budget / 2,
            (float) $outcome['elapsed'],
            'the budget must actually be spent retrying, not short-circuited on the first empty read',
        );
        self::assertLessThan(
            $budget + 3.0,
            (float) $outcome['elapsed'],
            'the retry must stop within its budget plus at most one stream read timeout',
        );

        fclose($local);
        fclose($peer);
    }

    /**
     * THE DISTINCTION, observed from the operator's side: the log must name which of
     * the three empty-read causes happened, and a timeout must NOT be filed under the
     * EOF event that the ingest ledger recorded for six cycles.
     */
    public function testAReadTimeoutIsLoggedSeparatelyFromAnEof(): void
    {
        [$local, $peer] = $this->socketPair();
        stream_set_timeout($local, self::STREAM_TIMEOUT_S);

        $logger = new ArrayLogger();
        try {
            StreamSocket::read($local, 16, $logger, 1.0);
            self::fail('a mute peer must raise once the budget is spent');
        } catch (RuntimeException) {
            // expected
        }

        $timeoutEvents = $this->recordsOf($logger, StreamSocket::READ_TIMEOUT_EVENT);
        self::assertCount(1, $timeoutEvents, 'a spent retry budget must log exactly one timeout event');
        self::assertSame('error', $timeoutEvents[0]['level']);
        self::assertSame(16, $timeoutEvents[0]['context']['length']);
        self::assertSame(1.0, $timeoutEvents[0]['context']['budget_seconds']);
        self::assertSame([], $this->recordsOf($logger, 'teleframe.mtproto.socket.read_failed'), 'a timeout must not be filed as an EOF');
        self::assertTrue($timeoutEvents[0]['context']['socket_open'], 'the event must record that the peer never closed');

        // The genuine EOF keeps the historic event and message.
        fclose($peer);
        $eofLogger = new ArrayLogger();
        try {
            StreamSocket::read($local, 16, $eofLogger);
            self::fail('a closed peer must raise');
        } catch (RuntimeException) {
            // expected
        }

        $eofEvents = $this->recordsOf($eofLogger, 'teleframe.mtproto.socket.read_failed');
        self::assertCount(1, $eofEvents, 'a genuine EOF must keep logging read_failed');
        self::assertSame([], $this->recordsOf($eofLogger, StreamSocket::READ_TIMEOUT_EVENT));

        fclose($local);
    }

    /**
     * fread() === false is a third thing again — the read FAILED, and it failed even
     * when the metadata says eof (a directory handle reports eof AND returns false).
     * It must be reported as a read failure, not laundered into either of the others.
     */
    public function testAHardReadErrorIsNeitherAReadNorAnEof(): void
    {
        // A directory handle is a deterministic, local, network-free fread() === false.
        $handle = @fopen(sys_get_temp_dir(), 'r');
        self::assertIsResource($handle, 'the temp dir must be openable as a stream handle');

        $logger = new ArrayLogger();
        try {
            StreamSocket::read($handle, 4, $logger);
            self::fail('a hard read error must raise');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('read failed', $e->getMessage(), 'a hard read error needs its own honest message');
            self::assertStringNotContainsString('EOF', $e->getMessage());
            self::assertFalse(StreamSocket::isReadTimeout($e), 'a hard read error is not a timeout');
        }

        $failed = $this->recordsOf($logger, 'teleframe.mtproto.socket.read_failed');
        self::assertCount(1, $failed);
        self::assertSame(4, $failed[0]['context']['length']);
        fclose($handle);
    }

    /**
     * stream_socket_pair: the whole point is that the "network" here is a kernel
     * socket pair, so every probe above is reproducible offline.
     *
     * @return array{0: resource, 1: resource}
     */
    private function socketPair(): array
    {
        $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        self::assertIsArray($pair, 'stream_socket_pair is required to probe the transport offline');

        /** @var array{0: resource, 1: resource} $pair */
        return $pair;
    }

    /** A peer that writes $bytes after a delay, in a child that outlives the fork. */
    private function writeLaterInChild($peer, string $bytes, ?float $delay = null): void
    {
        $this->writeInChild($peer, [$bytes], $delay ?? self::PEER_PAUSE_S);
    }

    /** A peer that writes $first, pauses past the read timeout, then writes $second. */
    private function writeInTwoHalvesInChild($peer, string $first, string $second): void
    {
        $this->writeInChild($peer, [$first, $second], self::PEER_PAUSE_S);
    }

    /**
     * @param  resource  $peer
     * @param  list<string>  $chunks  written in order, each preceded by $gapSeconds
     *                             of silence, so the FIRST write also lands after
     *                             the probe's read timeout has already elapsed
     */
    private function writeInChild($peer, array $chunks, float $gapSeconds): void
    {
        if (! function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl_fork required to make a peer write AFTER the read timeout');
        }

        $pid = pcntl_fork();
        if ($pid === -1) {
            self::markTestSkipped('pcntl_fork failed');
        }

        if ($pid === 0) {
            foreach ($chunks as $chunk) {
                usleep((int) ($gapSeconds * 1000000));
                $this->writeAll($peer, $chunk);
            }
            self::childExit();
        }

        // The parent must not hold the peer's end open, or its own EOF probes would
        // never see the peer go away.
        fclose($peer);
    }

    /** @param resource $socket */
    private function writeAll($socket, string $bytes): void
    {
        $written = 0;
        $total = strlen($bytes);
        while ($written < $total) {
            $n = @fwrite($socket, substr($bytes, $written));
            if ($n === false || $n === 0) {
                self::childExit();
            }
            $written += $n;
        }
    }

    /**
     * Runs $probe in a FORKED child and returns what it reported: ['ok' => true,
     * ...payload] on return, ['ok' => false, 'class' => ..., 'message' => ...,
     * 'is_timeout' => ..., 'elapsed' => ...] on a throwable. The parent SIGKILLs on
     * overrun, so an unbounded retry is a failing test rather than a wedged suite.
     *
     * @return array{ok: bool, value?: mixed, class?: string, message?: string, is_timeout?: bool, elapsed: float}
     */
    private function inBoundedChild(callable $probe): array
    {
        if (! function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl_fork required to bound the probe with a real deadline');
        }

        // The child's own memory is copy-on-write, so the report is streamed back
        // over a socket pair — the only way the parent can see it. $startedAt is
        // taken BEFORE the fork so the child measures the same span the parent does.
        [$reportRead, $reportWrite] = $this->socketPair();
        $startedAt = microtime(true);

        $pid = pcntl_fork();
        if ($pid === -1) {
            self::markTestSkipped('pcntl_fork failed');
        }

        if ($pid === 0) {
            fclose($reportRead);
            try {
                $payload = $probe();
                $report = ['ok' => true] + $payload;
            } catch (Throwable $e) {
                $report = [
                    'ok' => false,
                    'class' => $e::class,
                    'message' => $e->getMessage(),
                    'is_timeout' => self::timeoutVerdict($e),
                ];
            }
            @fwrite($reportWrite, json_encode($report + ['elapsed' => microtime(true) - $startedAt]));
            self::childExit();
        }

        fclose($reportWrite);
        try {
            return $this->readReport($reportRead, $pid, $startedAt);
        } finally {
            if (is_resource($reportRead)) {
                fclose($reportRead);
            }
        }
    }

    /**
     * Reads the child's JSON report with a REAL deadline; SIGKILLs the child on
     * overrun so a reverted bounded-retry hunk fails here instead of wedging the run.
     *
     * @param  resource  $reportRead
     * @return array{ok: bool, value?: mixed, class?: string, message?: string, is_timeout?: bool, elapsed: float}
     */
    private function readReport($reportRead, int $pid, float $startedAt): array
    {
        stream_set_blocking($reportRead, false);
        $deadline = $startedAt + self::DEADLINE_S;
        $exited = false;
        $carry = '';
        $status = null;

        while (microtime(true) < $deadline) {
            if (pcntl_waitpid($pid, $status, WNOHANG) === $pid) {
                $exited = true;
                break;
            }
            usleep(20000);
        }

        $carry .= (string) @fread($reportRead, 8192);
        $decoded = $carry === '' ? null : json_decode($carry, true);
        if (is_array($decoded)) {
            /** @var array{ok: bool, value?: mixed, class?: string, message?: string, is_timeout?: bool, elapsed: float} $decoded */
            return $decoded;
        }

        if ($exited) {
            // The child finished but reported nothing: it died inside the harness
            // itself. Say THAT, rather than blaming the retry budget for a hang.
            self::fail('the probe child exited without reporting (child exit status: '.($status >> 8).')');
        }

        @posix_kill($pid, SIGKILL);
        pcntl_waitpid($pid, $status);
        self::fail(sprintf(
            'the probe still held the socket after %.1fs (deadline %.1fs): the read retry is UNBOUNDED — '
            .'a timeout has been turned into a hang',
            microtime(true) - $startedAt,
            self::DEADLINE_S,
        ));
    }

    /**
     * The harness must not be the thing that hangs: a source that has not got the
     * predicate yet must produce a FAILED probe with a readable message, never a
     * child that dies inside its own reporting path.
     */
    private static function timeoutVerdict(Throwable $e): bool
    {
        return method_exists(StreamSocket::class, 'isReadTimeout') && StreamSocket::isReadTimeout($e);
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
     * Terminates the forked child WITHOUT PHP's shutdown sequence (a forked PHPUnit
     * child would flush buffers and re-run teardown otherwise).
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
}