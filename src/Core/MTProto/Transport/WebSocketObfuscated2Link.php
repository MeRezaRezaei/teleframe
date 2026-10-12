<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\MTProto\Transport;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * The WS ↔ obfuscated2 PLAINTEXT duplex, extracted from
 * WebSocketRelayServer::pump so the byte-proxy relay and the
 * VaultMtprotoAnswerer share ONE implementation of the decrypt boundary:
 *
 *   - readPlain(): read the client's RFC6455 binary data frames, answer
 *     ping/close control frames per RFC6455, decrypt the concatenated data
 *     payloads with the from-client key pair and hand the decrypted bytes to
 *     the caller.
 *   - writePlain(): encrypt plaintext with the to-client key pair and re-frame
 *     it as ONE unmasked RFC6455 binary message.
 *
 * The link owns the Obfuscated2Cipher instance and the partial-frame carry,
 * so the keystream stays monotonic across arbitrarily split chunk boundaries —
 * exactly the semantics the byte-proxy pump had (bytes that arrived with the
 * 64-byte header are carried as $lead and yield the first plaintext chunk, at
 * from-client keystream offset 64).
 *
 * readPlain() -> string  decrypted bytes now available ('' when nothing
 *                        complete arrived within $timeoutSec)
 *             -> null    the connection is OVER: a WS close frame arrived,
 *                        the socket hit EOF, write failures, or close() was
 *                        called. A plain timeout is NOT a teardown — a live
 *                        WebSocket may idle for minutes, and the byte-proxy
 *                        must keep it alive exactly as pump() did.
 *
 * Silent by default (NullLogger), mirroring the engine's logging seam.
 * Straightforward string functions only — zero regex, per the src/Core
 * discipline.
 */
final class WebSocketObfuscated2Link
{
    private LoggerInterface $logger;

    /** Partial-frame wire bytes not yet parsed into a complete RFC6455 frame. */
    private string $carry = '';

    /** The read side is over (close frame received / EOF) — readPlain must be done. */
    private bool $readEof = false;

    /** The link itself is torn down — writePlain is inert. */
    private bool $closed = false;

    /**
     * @param  resource  $client  the WebSocket client socket (post-handshake,
     *                            post-header-parse; blocking mode is enforced here)
     * @param  string  $lead  decrypted-at-64 payload bytes that arrived with
     *                        the 64-byte header but were already extracted
     *                        from the decode stream by the caller
     */
    public function __construct(
        private $client,
        private readonly Obfuscated2Cipher $cipher,
        private string $lead,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger;
        if (is_resource($client)) {
            stream_set_blocking($client, true);
        }
    }

    /**
     * Returns the next chunk of decrypted plaintext from the client — the
     * bytes that arrived with the header first ($lead), then whatever a full
     * drain of complete data frames decrypts to.
     *
     * @return string|null decrypted bytes ('' = nothing new within the
     *                     timeout, caller may retry), or null when the
     *                     connection is over (close frame / EOF / close())
     */
    public function readPlain(int $timeoutSec = 5): ?string
    {
        if ($this->closed || $this->readEof) {
            return null;
        }

        if ($this->lead !== '') {
            $this->logger->debug('WebSocketObfuscated2Link: yielding lead plaintext', ['bytes' => strlen($this->lead)]);
            $lead = $this->cipher->xcrypt($this->lead, true);
            $this->lead = '';

            return $lead;
        }

        for (; ;) {
            $plain = $this->drain();
            if ($this->readEof) {
                return null;
            }
            if ($plain !== '') {
                return $plain;
            }

            $chunk = $this->readWire($timeoutSec);
            if ($chunk === null) {
                $this->logger->debug('WebSocketObfuscated2Link: client EOF');
                $this->readEof = true;

                return null;
            }
            if ($chunk === '') {
                // Read window expired with no complete frame yet — the
                // connection is alive; the caller keeps polling.
                return '';
            }
            $this->carry .= $chunk;
        }
    }

    /**
     * Encrypts $plaintext with the to-client key pair (keystream offset 0 —
     * the client never encrypted a header through this direction) and writes
     * it to the client as one unmasked RFC6455 binary message.
     */
    public function writePlain(string $plaintext): void
    {
        if ($this->closed) {
            $this->logger->debug('WebSocketObfuscated2Link: write on a closed link dropped', ['bytes' => strlen($plaintext)]);

            return;
        }

        $encrypted = $this->cipher->xcrypt($plaintext, false);
        self::writeAll($this->client, WebSocketFrameCodec::frame(WebSocketFrameCodec::OPCODE_BINARY, $encrypted));
    }

    /**
     * Tears the link down: close records the end and frees the client socket.
     * Idempotent — later readPlain()/writePlain() calls are inert.
     */
    public function close(): void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;
        if (is_resource($this->client)) {
            fclose($this->client);
        }
    }

    /**
     * Decodes every complete frame from $carry (in/out), answering control
     * frames, and returns the decrypted concatenation of the data-frame
     * payloads ('' when no complete frame is present yet), or null when a
     * close frame was received (the caller should tear down).
     *
     * @phpstan-impure  mutates $carry/[$readEof and writes pong frames
     */
    private function drain(): ?string
    {
        $out = '';
        for (; ;) {
            $frame = WebSocketFrameCodec::parseFrame($this->carry);
            if ($frame === null) {
                return $this->cipher->xcrypt($out, true);
            }
            $this->carry = substr($this->carry, self::frameLength($frame));
            $opcode = $frame['opcode'];

            if ($opcode === WebSocketFrameCodec::OPCODE_PING) {
                self::writeAll($this->client, WebSocketFrameCodec::frame(WebSocketFrameCodec::OPCODE_PONG, $frame['payload']));

                continue;
            }
            if ($opcode === WebSocketFrameCodec::OPCODE_CLOSE) {
                $this->readEof = true; // read side over; writes may still flush

                return null; // RFC6455 close handshake initiator → close our side
            }
            if (
                $opcode === WebSocketFrameCodec::OPCODE_BINARY
                || $opcode === WebSocketFrameCodec::OPCODE_CONTINUATION
            ) {
                $out .= $frame['payload'];

                continue;
            }
            // TEXT (0x1) and PONG (0xA) frames carry no wire data — ignore.
        }
    }

    /**
     * Waits up to $timeoutSec for the client socket to become readable, then
     * reads at most one chunk. Returns '' when the window expired with no
     * data (alive), null on EOF/read failure ($closed is set by the caller).
     *
     * @phpstan-impure  reads the socket
     */
    private function readWire(int $timeoutSec): ?string
    {
        $read = [$this->client];
        $write = null;
        $except = null;
        $ready = @stream_select($read, $write, $except, $timeoutSec, 0);
        if ($ready === false || $ready === 0) {
            return '';
        }

        $chunk = @fread($this->client, 8192);
        if ($chunk === false || $chunk === '') {
            return null; // EOF
        }

        return $chunk;
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

    /**
     * @param  resource  $socket
     */
    private static function writeAll($socket, string $bytes): void
    {
        $total = strlen($bytes);
        $written = 0;
        while ($written < $total) {
            $n = @fwrite($socket, substr($bytes, $written));
            if ($n === false || $n === 0) {
                throw new RuntimeException('WebSocketObfuscated2Link: write to peer failed');
            }
            $written += $n;
        }
    }
}
