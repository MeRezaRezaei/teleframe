<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core\MTProto\Transport;

use MeRezaRezaei\Teleframe\Core\MTProto\Connection\PlainConnection;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLDecoder;
use MeRezaRezaei\Teleframe\Core\MTProto\TL\TLEncoder;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\FrameCodec;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\Obfuscated2Cipher;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\VaultMtprotoAnswerer;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\WebSocketFrameCodec;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\WebSocketObfuscated2Link;
use phpseclib3\Crypt\RSA;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * THE READ-TIMEOUT-vs-EOF CLASS, AUDITED ON THE RELAY-SIDE ANSWERER.
 *
 * A sibling lane fixed `StreamSocket::read()`, which reported EVERY empty
 * `fread()` as a fatal EOF — including the shape a blocking socket with
 * `stream_set_timeout()` returns when a read timeout elapses with nothing
 * available. That collapsed two different events into one and killed live
 * ingest runs with "StreamSocket: EOF while reading" while the connection was
 * still perfectly usable.
 *
 * That lane then reported, unprompted:
 *
 *   > `VaultMtprotoAnswerer` has its own separate read path with its own
 *   > empty-read budget — not touched, and worth a look for the same bug class.
 *
 * THIS FILE IS THAT LOOK. The answerer does NOT route through
 * `StreamSocket::read()`/`readExact()`, so it neither inherits the fix nor
 * re-implements the defect — and the reason is structural, not accidental:
 *
 *   - Its read path is `nextFrame()` -> `WebSocketObfuscated2Link::readPlain()`
 *     -> `WebSocketObfuscated2Link::readWire()`
 *     (src/Core/MTProto/Transport/VaultMtprotoAnswerer.php:596).
 *   - `readWire()` waits on `stream_select()` — a READINESS POLL — and only
 *     calls `fread()` once the socket is already readable
 *     (src/Core/MTProto/Transport/WebSocketObfuscated2Link.php:206-216).
 *
 * So this path can never produce the offending shape at all. There is no
 * `stream_set_timeout()` anywhere on the client socket (the link sets
 * BLOCKING mode and nothing else, WebSocketObfuscated2Link.php:69), so a
 * `fread()` on it cannot time out, and its three outcomes map one-to-one onto
 * the three measured cases from the sibling fix:
 *
 *   stream_select() === 0      -> ''  (return value)  = TIMEOUT, connection alive
 *   stream_select() > 0, fread === ''                  = GENUINE EOF, peer closed
 *   stream_select() === false  -> ''  (return value)  = select() failed; reported as
 *                                                     a timeout, never as an EOF
 *
 * The timeout case is a distinct RETURN VALUE (`''`) rather than a distinct
 * exception, and the EOF case is `null`. That is the same three-way separation
 * the StreamSocket fix bought, reached by a different route.
 *
 * What this file therefore pins is the SAFETY of the delegation, plus the
 * BOUNDEDNESS of the answerer's own (separate) empty-read budget — because an
 * unbounded retry would trade the fatal error for a silent hang, which is worse
 * than either defect:
 *
 *   test_a_read_window_that_expires_is_reported_as_idle_not_as_eof
 *   test_a_genuine_peer_close_is_still_reported_as_an_eof
 *   test_the_socket_still_works_after_a_timed_out_read_window
 *   test_the_answerer_serves_a_request_that_arrives_after_a_timed_out_window
 *   test_the_empty_read_budget_is_bounded
 *   test_the_read_path_never_goes_through_StreamSocketRead
 *
 * NO DEFECT IS MANUFACTURED. There is no hunk in src/ to write, and there must
 * not be: the answerer is correct as shipped.
 *
 * EVERY probe is FAKE-SOCKET: `stream_socket_pair` and the real
 * Obfuscated2Cipher, with a generated RSA key. No network, no credentials, no
 * live Telegram, no .env.
 *
 * A HANG IS A RED TEST, NOT A WEDGED SUITE: the answerer probe runs in a forked
 * child that the parent SIGKILLs at DEADLINE_S, mirroring
 * VaultMtprotoAnswererHandshakeBudgetTest.
 */
final class VaultMtprotoAnswererReadTimeoutTest extends TestCase
{
    /** Hard deadline for the forked answerer probe. Overrun => SIGKILL + failure. */
    private const DEADLINE_S = 40.0;

    /**
     * The read window the link-level probes use. One second keeps "the peer was
     * silent" cheap; the answerer-level probe uses the SHIPPED READ_TIMEOUT_S
     * (5s), because that probe runs the real class, not a shrunk copy.
     */
    private const WINDOW_S = 1;

    /**
     * How long the answerer-level peer stays mute. MUST exceed one shipped read
     * window (5s) so at least one read window genuinely expires before the
     * request arrives — that expiry is the whole point of the probe.
     */
    private const PEER_SILENCE_S = 6.0;

    /** How long the parent waits for the answerer's resPQ reply. */
    private const REPLY_DEADLINE_S = 10.0;

    /** Microseconds per second for explicit delay calculations. */
    private const MICROS_PER_SECOND = 1_000_000;

    /** @var array{0: string, 1: string}|null [private PKCS1, public PKCS1] */
    private static ?array $pair = null;

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
     * @return array{0: resource, 1: resource}
     */
    private function socketPair(): array
    {
        $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        self::assertIsArray($pair, 'stream_socket_pair is required to probe the transport offline');

        return $pair;
    }

    /** A fresh client-shaped cipher, built the way the relay builds one. */
    private static function clientCipher(): Obfuscated2Cipher
    {
        return Obfuscated2Cipher::fromHeader(Obfuscated2CodecTest::clientHeader(5, 'abridged'));
    }

    /**
     * THE CENTRAL PROOF, part 1 — an expired read window is IDLE, not EOF.
     *
     * The peer never writes a byte and never closes. `readPlain()` must come
     * back with `''` (the caller may retry, and the answerer caps the retries),
     * and NOT with `null`. `null` is the answerer's teardown signal
     * (VaultMtprotoAnswerer.php:597-599), so conflating the two would end the
     * connection on the first idle window — which is precisely the bug class
     * that killed the sibling's live run.
     */
    public function test_a_read_window_that_expires_is_reported_as_idle_not_as_eof(): void
    {
        [$local, $peer] = $this->socketPair();
        $link = new WebSocketObfuscated2Link($local, self::clientCipher(), '');

        $result = $link->readPlain(self::WINDOW_S);

        self::assertNotNull(
            $result,
            'an expired read window is an IDLE connection and must not be reported as an EOF — '
            .'returning null here ends the relay session on the first quiet window, which is the '
            .'defect class that killed the sibling run',
        );
        self::assertSame(
            '',
            $result,
            'an expired read window with nothing buffered must return the empty string, '
            .'which is the caller-retry signal',
        );

        // And the mechanism is proven, not assumed: this socket is NOT carrying a
        // stream read timeout, so a `fread()` on it cannot produce the
        // "empty read" shape that StreamSocket's fix had to classify. The timeout
        // here is produced by stream_select() — a readiness poll — which is why
        // this path never had the defect to begin with.
        $meta = stream_get_meta_data($local);
        self::assertFalse(
            $meta['timed_out'],
            'the relay client socket carries no stream read timeout, so a timeout on this path '
            .'cannot be confused with an empty fread() — readWire() gates on stream_select()',
        );

        fclose($local);
        fclose($peer);
    }

    /**
     * THE CENTRAL PROOF, part 2 — a genuine peer close IS still an EOF.
     *
     * The mirror image, and the reason the file is not a rubber stamp: if
     * everything were reported as an idle timeout the answerer would spin
     * forever on a dead socket. Only the real EOF may return null.
     */
    public function test_a_genuine_peer_close_is_still_reported_as_an_eof(): void
    {
        [$local, $peer] = $this->socketPair();
        $link = new WebSocketObfuscated2Link($local, self::clientCipher(), '');
        fclose($peer);

        self::assertNull(
            $link->readPlain(self::WINDOW_S),
            'a closed peer is an EOF and must terminate the read side — an idle-timeout '
            .'classification must not swallow a real close into a spin',
        );

        fclose($local);
    }

    /**
     * THE CENTRAL PROOF, part 3 — the socket is STILL USABLE after a window
     * expires. Two timed-out windows, then real bytes, and they must still
     * arrive intact and decrypted in order.
     *
     * This is the property the StreamSocket defect denied: a slow answer must
     * survive the wait. Had `''` been conflated into EOF, the third read would
     * return null and the bytes would be lost.
     */
    public function test_the_socket_still_works_after_a_timed_out_read_window(): void
    {
        [$local, $peer] = $this->socketPair();
        $link = new WebSocketObfuscated2Link($local, self::clientCipher(), '');

        // Two windows elapse with the peer mute: two idle answers, no teardown.
        self::assertSame('', $link->readPlain(self::WINDOW_S), 'window 1 must be idle');
        self::assertSame('', $link->readPlain(self::WINDOW_S), 'window 2 must be idle');

        // Now the slow peer finally speaks: one WS binary message carrying
        // obfuscated2 plaintext, exactly as a browser puts it on the wire.
        $plaintext = 'SLOW-BUT-WELL-FORMED-PLAINTEXT';
        $sender = self::clientCipher();
        $this->writeAll($peer, WebSocketFrameCodec::frame(
            WebSocketFrameCodec::OPCODE_BINARY,
            $sender->xcrypt($plaintext, true),
        ));

        self::assertSame(
            $plaintext,
            $link->readPlain(self::WINDOW_S),
            'bytes that arrive after one or more timed-out read windows must still be '
            .'decrypted and delivered — an expired window costs latency, never data',
        );

        fclose($local);
        fclose($peer);
    }

    /**
     * THE SAME PROOF, THROUGH THE ANSWERER — the real shipped class, no shrunk
     * copy, no rewritten constants.
     *
     * A ticketed peer stays mute past one full 5s read window, then sends a
     * well-formed req_pq_multi. The answerer must have SURVIVED the expired
     * window and answer with resPQ. If `''` were conflated with EOF the answerer
     * would have torn the session down at t=5s and the reply would never come.
     *
     * The child is forked and the parent SIGKILLs it at DEADLINE_S, so a hang in
     * this path is a RED TEST rather than a wedged suite. The child is killed
     * rather than awaited to completion: the answerer will keep serving (and
     * keep reading) until one of its own budgets trips ~600s from now, which is
     * out of scope here and already covered by
     * VaultMtprotoAnswererHandshakeBudgetTest.
     */
    public function test_the_answerer_serves_a_request_that_arrives_after_a_timed_out_window(): void
    {
        if (! function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl_fork required to bound the answerer with a real deadline');
        }

        [$private] = self::pair();
        [$server, $peer] = $this->socketPair();

        $answerer = new VaultMtprotoAnswerer(
            static fn (string $method, array $params, ?array $grant): array => ['_' => 'boolTrue'],
            $private,
            ticketRequired: true,
        );

        $cipher = self::clientCipher();
        $startedAt = microtime(true);
        $pid = pcntl_fork();
        if ($pid === -1) {
            self::markTestSkipped('pcntl_fork failed');
        }

        if ($pid === 0) {
            fclose($peer);
            try {
                ($answerer)($server, $cipher, '', 5, 'abridged', 42);
            } catch (\Throwable) {
                // The child's own assertion surface is unreachable from here.
            }
            self::childExit();
        }

        fclose($server);

        try {
            // The peer redeems its ticket, then says NOTHING for longer than one
            // shipped read window — this silence is what the answerer must ride
            // out instead of dying on.
            usleep((int) (self::PEER_SILENCE_S * self::MICROS_PER_SECOND));

            $this->writeAll($peer, $this->clientDhFrame('req_pq_multi', ['nonce' => random_bytes(16)]));

            $ctor = $this->awaitOneDhReply($peer, self::clientCipher());

            self::assertSame(
                'resPQ',
                $ctor,
                'the answerer must answer resPQ even though an entire read window expired '
                .'before the request arrived — surviving the idle window is the whole property',
            );
        } finally {
            @posix_kill($pid, SIGKILL);
            $status = null;
            pcntl_waitpid($pid, $status, WNOHANG);
            if (is_resource($peer)) {
                fclose($peer);
            }
        }

        self::assertLessThan(
            self::DEADLINE_S,
            microtime(true) - $startedAt,
            'this probe must complete inside its deadline; the parent is bounded by the silence '
            .'window plus the reply window, so exceeding it means the read path stopped answering',
        );
    }

    /**
     * Reads ONE WebSocket binary message from the answerer and returns the
     * constructor name of the decoded TL object it carries, or '' if none
     * arrives before the deadline. Bounded by stream_select(), so it can never
     * block for ever.
     */
    private function awaitOneDhReply($socket, Obfuscated2Cipher $cipher): string
    {
        $deadline = microtime(true) + self::REPLY_DEADLINE_S;
        $carry = '';
        while (microtime(true) < $deadline) {
            $read = [$socket];
            $write = null;
            $except = null;
            $ready = @stream_select($read, $write, $except, 1, 0);
            if ($ready === false || $ready === 0) {
                continue;
            }

            $chunk = @fread($socket, 8192);
            if ($chunk === false || $chunk === '') {
                continue; // child exited before answering
            }
            $carry .= $chunk;

            $frame = WebSocketFrameCodec::parseFrame($carry);
            if ($frame === null) {
                continue; // frame still incomplete
            }
            $plaintext = self::unwrapAbridged($cipher->xcrypt($frame['payload'], false));
            if ($plaintext === '') {
                continue;
            }

            $body = PlainConnection::parsePlainEnvelope($plaintext);
            $offset = 0;
            $decoded = TLDecoder::decodeObject($body, $offset);

            return is_array($decoded) ? (string) ($decoded['_'] ?? '') : '';
        }

        return '';
    }

    /**
     * Strips one abridged frame (varint len/4 prefix) off a plaintext payload.
     * Mirrors VaultMtprotoAnswerer::parseFrame(); no regex, per src discipline.
     */
    private static function unwrapAbridged(string $payload): string
    {
        if ($payload === '') {
            return '';
        }
        $len4 = ord($payload[0]);
        $header = 1;
        if ($len4 >= 0x7F) {
            if (strlen($payload) < 4) {
                return '';
            }
            $len4 = unpack('V', substr($payload, 1, 3)."\x00")[1];
            $header = 4;
        }
        $len = $len4 << 2;
        if (strlen($payload) < $header + $len) {
            return '';
        }

        return substr($payload, $header, $len);
    }

    /**
     * One WS binary message carrying one obfuscated2-abridged plaintext DH
     * request, exactly as a browser would put it on the wire.
     */
    private function clientDhFrame(string $method, array $params): string
    {
        $cipher = self::clientCipher();
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
     * THE BUDGET IS BOUNDED — proof 4.
     *
     * The answerer does not inherit StreamSocket's READ_RETRY_BUDGET_SECONDS;
     * it runs a separate budget of its own (MAX_EMPTY_READS consecutive expired
     * read windows at READ_TIMEOUT_S each), and an unbounded retry there would
     * be WORSE than the original defect: a wedged relay child is
     * indistinguishable from a slow one.
     *
     * The shipped numbers are far too large to exercise in a unit test, so the
     * boundedness is proven from the constants themselves: they exist, they are
     * positive and finite, and the empty-read budget cannot exceed the lifetime
     * budget — so the idle path is always capped, whichever trips first. The
     * REACHABILITY of both is pinned separately by
     * VaultMtprotoAnswererHandshakeBudgetTest.
     */
    public function test_the_empty_read_budget_is_bounded(): void
    {
        $constants = (new ReflectionClass(VaultMtprotoAnswerer::class))->getConstants();

        foreach (['READ_TIMEOUT_S', 'MAX_EMPTY_READS', 'MAX_LIFETIME_S'] as $name) {
            self::assertArrayHasKey(
                $name,
                $constants,
                "the answerer's own empty-read budget constant {$name} vanished — the idle path "
                .'would then be capped by nothing at all',
            );
            self::assertIsInt($constants[$name]);
            self::assertGreaterThan(0, $constants[$name], "{$name} must be a positive bound");
        }

        $idleBudget = $constants['MAX_EMPTY_READS'] * $constants['READ_TIMEOUT_S'];

        self::assertLessThanOrEqual(
            $constants['MAX_LIFETIME_S'],
            $idleBudget,
            'the empty-read budget must sit inside the lifetime budget, so the idle read path '
            .'is always capped and can never retry for ever',
        );
        self::assertSame(
            600,
            $idleBudget,
            'MAX_EMPTY_READS x READ_TIMEOUT_S — 120 idle windows of 5s each. Asserted so a change '
            .'to either constant is a DELIBERATE, visible edit to this bound.',
        );
    }

    /**
     * The read path is a stream_select() readiness poll, not a StreamSocket
     * read. This pins WHY the answerer was immune to the sibling defect: it
     * cannot reach the ambiguous shape at all, so it neither needed the fix nor
     * re-implemented it.
     */
    public function test_the_read_path_never_goes_through_stream_socket_read(): void
    {
        $answerer = (string) file_get_contents(
            dirname(__DIR__, 4).'/src/Core/MTProto/Transport/VaultMtprotoAnswerer.php'
        );
        $link = (string) file_get_contents(
            dirname(__DIR__, 4).'/src/Core/MTProto/Transport/WebSocketObfuscated2Link.php'
        );
        self::assertIsString($answerer);
        self::assertIsString($link);

        self::assertStringNotContainsString(
            'StreamSocket::read',
            $answerer.$link,
            'the answerer read path must not call StreamSocket::read()/readExact() — if it did, it '
            .'would inherit that class\' empty-read semantics rather than this readiness poll',
        );
        self::assertStringNotContainsString(
            'stream_set_timeout',
            $link,
            'no stream read timeout may ever be armed on the relay client socket: a blocking fread() '
            .'under a timeout returns \'\' on timeout AND on close, which is the ambiguity this '
            .'path avoids by using stream_select() instead',
        );
        self::assertStringContainsString(
            'stream_select',
            $link,
            'the idle window must come from a stream_select() readiness poll',
        );
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
}
