<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\MTProto\Transport;

use InvalidArgumentException;
use RuntimeException;

/**
 * Server-side RFC6455 WebSocket codec — the framing layer of the control
 * layer's /apiws terminal (browser clients).
 *
 * The browser cannot speak raw TCP; the vendored GramJS client dials
 * wss://…/apiws and runs its obfuscated2 transport inside binary WebSocket
 * messages. WebSocket is a byte-STREAM carrier here: frame boundaries carry
 * no transport meaning and the relay treats every data-frame payload as a
 * continuation of one wire stream (the client's PromisedWebSockets
 * concatenates frames exactly like a socket read side would).
 *
 * Responsibilities:
 *   - parseHandshake(): validate the client's HTTP 101-upgrade request and
 *     negotiate the `binary` subprotocol MTProto's WebSocket transport
 *     requires (GramJS offers `Sec-WebSocket-Protocol: binary`; browsers
 *     enforce the server echo).
 *   - acceptKey(): the RFC6455 accept value (base64(sha1(key . GUID))),
 *     HTTP/1.1 101 response builder for the server side of the handshake.
 *   - frame()/parseFrame(): build server→client frames (unmasked) and
 *     parse client→server frames (mandatorily masked, unmasked in place).
 *     Control frames (ping/pong/close) are surfaced to the caller and a
 *     close-reply frame can be produced with closeFrame().
 *   - frame-size bound: a declared frame length above MAX_FRAME_LENGTH
 *     throws WebSocketFrameTooLargeException (close code 1009) straight
 *     from the header, before any body bytes are awaited — a client that
 *     declares an enormous length and trickle-feeds the body can no longer
 *     pin the relay reader open.
 *
 * Straightforward string functions only — zero regex, per the src/Core
 * discipline (same as Obfuscated2Codec).
 */
final class WebSocketFrameCodec
{
    public const OPCODE_CONTINUATION = 0x0;

    public const OPCODE_TEXT = 0x1;

    public const OPCODE_BINARY = 0x2;

    public const OPCODE_CLOSE = 0x8;

    public const OPCODE_PING = 0x9;

    public const OPCODE_PONG = 0xA;

    /** Hard cap on the accepted frame payload length (16 MiB). */
    public const MAX_FRAME_LENGTH = 16777216;

    /** RFC6455 §7.4.1 normal closure. */
    public const CLOSE_NORMAL = 1000;

    /** RFC6455 §7.4.1 "message too big" — the oversized-frame rejection code. */
    public const CLOSE_MESSAGE_TOO_BIG = 1009;

    /** RFC6455 §1.3 magic GUID appended to the client key for acceptKey(). */
    private const GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    /** @var array<int, string> */
    private const OPCODES = [
        self::OPCODE_CONTINUATION => 'continuation',
        self::OPCODE_TEXT => 'text',
        self::OPCODE_BINARY => 'binary',
        self::OPCODE_CLOSE => 'close',
        self::OPCODE_PING => 'ping',
        self::OPCODE_PONG => 'pong',
    ];

    /**
     * RFC6455 §4.2.2 accept value from the client's Sec-WebSocket-Key.
     */
    public static function acceptKey(string $key): string
    {
        return base64_encode(sha1($key.self::GUID, true));
    }

    /**
     * Parses a server-side HTTP opening handshake request. Returns the
     * client's Sec-WebSocket-Key plus the negotiated subprotocol (null when
     * the client offered none; 'binary' when MTProto's required subprotocol
     * is offered), or null when the request is not a valid websocket upgrade
     * (missing key / upgrade header / not a GET).
     *
     * MTProto's WebSocket transport REQUIRES the client to offer the
     * `binary` subprotocol (core.telegram.org/mtproto/transports#websocket)
     * and the server to echo it — browsers enforce the echo and fail the
     * upgrade without it. GramJS always connects with `binary`.
     *
     * @return array{key: string, subprotocol: string|null}|null
     */
    public static function parseHandshake(string $request): ?array
    {
        $lines = explode("\r\n", $request);
        if (! str_starts_with($lines[0], 'GET ')) {
            return null;
        }

        $upgrading = false;
        $connecting = false;
        $key = null;
        $offersBinary = false;

        foreach (array_slice($lines, 1) as $line) {
            if ($line === '') {
                continue;
            }
            $colon = strpos($line, ':');
            if ($colon === false) {
                continue;
            }
            $name = strtolower(trim(substr($line, 0, $colon)));
            $value = trim(substr($line, $colon + 1));

            if ($name === 'upgrade' && strtolower($value) === 'websocket') {
                $upgrading = true;
            } elseif ($name === 'connection' && str_contains(strtolower($value), 'upgrade')) {
                $connecting = true;
            } elseif ($name === 'sec-websocket-key') {
                $key = $value;
            } elseif ($name === 'sec-websocket-protocol') {
                // Comma-separated subprotocol list, case-insensitive.
                foreach (explode(',', $value) as $protocol) {
                    if (strtolower(trim($protocol)) === 'binary') {
                        $offersBinary = true;
                    }
                }
            }
        }

        if (! $upgrading || ! $connecting || $key === null) {
            return null;
        }

        return [
            'key' => $key,
            'subprotocol' => $offersBinary ? 'binary' : null,
        ];
    }

    /**
     * Builds the HTTP/1.1 101 response for the client's Sec-WebSocket-Key.
     * Echoes the subprotocol negotiated by parseHandshake (null when the
     * client offered none — a plain RFC6455 connection is fine too).
     */
    public static function handshakeResponse(string $key, ?string $subprotocol = null): string
    {
        $protocolLine = $subprotocol === null
            ? ''
            : 'Sec-WebSocket-Protocol: '.$subprotocol."\r\n";

        return "HTTP/1.1 101 Switching Protocols\r\n"
            ."Upgrade: websocket\r\n"
            ."Connection: Upgrade\r\n"
            .'Sec-WebSocket-Accept: '.self::acceptKey($key)."\r\n"
            .$protocolLine
            ."\r\n";
    }

    /**
     * Builds one frame.
     *
     * @param  bool  $mask  server→client frames MUST NOT be masked (false);
     *                      true is supported so tests can build client-shaped frames
     */
    public static function frame(int $opcode, string $payload, bool $mask = false): string
    {
        $length = strlen($payload);

        $header = chr(0x80 | $opcode); // FIN + opcode (no fragmentation from the server)
        if ($length <= 125) {
            $header .= chr(($mask ? 0x80 : 0x00) | $length);
        } elseif ($length <= 0xFFFF) {
            $header .= chr(($mask ? 0x80 : 0x00) | 126).pack('n', $length);
        } else {
            $header .= chr(($mask ? 0x80 : 0x00) | 127).pack('J', $length);
        }

        if (! $mask) {
            return $header.$payload;
        }

        $maskKey = random_bytes(4);
        $masked = '';
        $ml = strlen($payload);
        for ($i = 0; $i < $ml; $i++) {
            $masked .= $payload[$i] ^ $maskKey[$i & 3];
        }

        return $header.$maskKey.$masked;
    }

    /**
     * Parses one complete frame from the head of $buffer.
     *
     * @return array{opcode: int, payload: string, masked: bool}|null the
     *                                                                decoded frame, or null when $buffer does not yet hold a
     *                                                                full frame (caller should read more bytes and retry)
     */
    public static function parseFrame(string $buffer): ?array
    {
        $length = strlen($buffer);
        if ($length < 2) {
            return null;
        }

        $b0 = ord($buffer[0]);
        $b1 = ord($buffer[1]);

        $opcode = $b0 & 0x0F;
        $masked = ($b1 & 0x80) !== 0;
        $len = $b1 & 0x7F;

        $offset = 2;
        if ($len === 126) {
            if ($length < 4) {
                return null;
            }
            $len = unpack('n', substr($buffer, 2, 2))[1];
            $offset = 4;
        } elseif ($len === 127) {
            if ($length < 10) {
                return null;
            }
            $len = unpack('J', substr($buffer, 2, 8))[1];
            $offset = 10;
        }

        // Enforce the 16 MiB bound from the header alone: a declared length
        // above MAX_FRAME_LENGTH — or a 64-bit length field that wrapped to a
        // negative int — must be rejected before the caller buffers the body.
        if ($len < 0 || $len > self::MAX_FRAME_LENGTH) {
            throw new WebSocketFrameTooLargeException($len, self::MAX_FRAME_LENGTH);
        }

        if ($masked) {
            if ($length < $offset + 4) {
                return null;
            }
            $maskKey = substr($buffer, $offset, 4);
            $offset += 4;
        }

        if ($length < $offset + $len) {
            return null;
        }

        $payload = substr($buffer, $offset, $len);
        if ($masked) {
            $unmasked = '';
            for ($i = 0; $i < $len; $i++) {
                $unmasked .= $payload[$i] ^ $maskKey[$i & 3];
            }
            $payload = $unmasked;
        }

        return [
            'opcode' => $opcode,
            'payload' => $payload,
            'masked' => $masked,
        ];
    }

    /**
     * Builds a close frame (RFC6455 §5.5.1): the 16-bit close code in the
     * first two payload bytes, optional UTF-8 reason after it. Server-sent
     * frames are unmasked. The codec's 1009 rejection can be echoed back
     * with closeFrame(self::CLOSE_MESSAGE_TOO_BIG, 'message too big').
     */
    public static function closeFrame(int $code = self::CLOSE_NORMAL, string $reason = ''): string
    {
        if ($code < 0 || $code > 0xFFFF) {
            throw new InvalidArgumentException('close code must be a 16-bit unsigned integer');
        }

        return self::frame(self::OPCODE_CLOSE, pack('n', $code).$reason);
    }

    /**
     * Human-readable opcode name (for logging/diagnostics only).
     */
    public static function opcodeName(int $opcode): string
    {
        return self::OPCODES[$opcode] ?? '0x'.dechex($opcode);
    }

    private function __construct() {}
}

/**
 * Thrown by WebSocketFrameCodec::parseFrame() when a declared frame length
 * exceeds MAX_FRAME_LENGTH. Carries the RFC6455 "message too big" close code
 * (1009) so a caller can both abort the read loop and reply with an
 * RFC-conformant close frame.
 *
 * Declared near the codec so the throw site stays self-contained; it is
 * never constructed in isolation by callers.
 */
final class WebSocketFrameTooLargeException extends RuntimeException
{
    public function __construct(int|float $declaredLength, int $maxLength)
    {
        parent::__construct(
            'WebSocket frame too large: declared '.(string) $declaredLength.' bytes exceeds the '.$maxLength.'-byte maximum',
            self::CLOSE_CODE,
        );
    }

    public const CLOSE_CODE = WebSocketFrameCodec::CLOSE_MESSAGE_TOO_BIG;
}
