<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\MTProto\Transport;

/**
 * WebSocket framing helper (RFC 6455).
 *
 * Handles encoding/decoding of WebSocket frames for WEB proxy websocket carriers.
 */
class WebSocketFrame
{
    /** Frame opcodes */
    public const OP_CONTINUATION = 0x0;

    public const OP_TEXT = 0x1;

    public const OP_BINARY = 0x2;

    public const OP_CLOSE = 0x8;

    public const OP_PING = 0x9;

    public const OP_PONG = 0xA;

    /** Close status codes */
    public const CLOSE_NORMAL = 1000;

    public const CLOSE_GOING_AWAY = 1001;

    public const CLOSE_PROTOCOL_ERROR = 1002;

    public const CLOSE_UNSUPPORTED_DATA = 1003;

    public const CLOSE_NO_STATUS = 1005;

    public const CLOSE_ABNORMAL = 1006;

    public const CLOSE_INVALID_PAYLOAD = 1007;

    public const CLOSE_POLICY_VIOLATION = 1008;

    public const CLOSE_TOO_BIG = 1009;

    public const CLOSE_EXTENSION_REQUIRED = 1010;

    public const CLOSE_UNEXPECTED_CONDITION = 1011;

    /**
     * Encode a WebSocket frame.
     *
     * @param  string  $payload  Frame payload (binary data for WEB proxy)
     * @param  int  $opcode  Frame opcode (default: binary)
     * @param  bool  $fin  FIN bit (final fragment)
     * @param  bool  $mask  Whether to mask the payload (client must mask)
     * @return string Encoded WebSocket frame
     */
    public static function encode(string $payload, int $opcode = self::OP_BINARY, bool $fin = true, bool $mask = true): string
    {
        $length = strlen($payload);
        $firstByte = ($fin ? 0x80 : 0x00) | ($opcode & 0x0F);
        $frame = chr($firstByte);

        if ($length < 126) {
            $secondByte = $mask ? 0x80 | $length : $length;
            $frame .= chr($secondByte);
        } elseif ($length <= 0xFFFF) {
            $secondByte = $mask ? 0xFE : 126;
            $frame .= chr($secondByte);
            $frame .= pack('n', $length);
        } else {
            $secondByte = $mask ? 0xFF : 127;
            $frame .= chr($secondByte);
            $frame .= pack('J', $length); // 64-bit unsigned
        }

        if ($mask) {
            $maskKey = random_bytes(4);
            $frame .= $maskKey;
            $payload = self::applyMask($payload, $maskKey);
        }

        $frame .= $payload;

        return $frame;
    }

    /**
     * Decode a WebSocket frame from a socket.
     *
     * @param  resource  $socket
     * @return array{opcode: int, fin: bool, payload: string, close_code: int|null}|null Null on connection close
     */
    public static function decode($socket): ?array
    {
        // Read first 2 bytes
        $header = StreamSocket::readExact($socket, 2);
        if ($header === '') {
            return null;
        }

        $firstByte = ord($header[0]);
        $secondByte = ord($header[1]);

        $fin = ($firstByte & 0x80) !== 0;
        $opcode = $firstByte & 0x0F;
        $masked = ($secondByte & 0x80) !== 0;
        $length = $secondByte & 0x7F;

        // Read extended length
        if ($length === 126) {
            $extLen = StreamSocket::readExact($socket, 2);
            $length = unpack('n', $extLen)[1];
        } elseif ($length === 127) {
            $extLen = StreamSocket::readExact($socket, 8);
            // PHP unpack 'J' is platform-dependent, use manual calculation for 64-bit
            $length = 0;
            for ($i = 0; $i < 8; $i++) {
                $length = ($length << 8) | ord($extLen[$i]);
            }
        }

        // Read mask key
        $maskKey = '';
        if ($masked) {
            $maskKey = StreamSocket::readExact($socket, 4);
        }

        // Read payload
        $payload = '';
        if ($length > 0) {
            $payload = StreamSocket::readExact($socket, $length);
        }

        // Unmask if needed (server->client frames are not masked per RFC 6455)
        if ($masked && $maskKey !== '') {
            $payload = self::applyMask($payload, $maskKey);
        }

        // Handle close frame
        $closeCode = null;
        if ($opcode === self::OP_CLOSE && strlen($payload) >= 2) {
            $closeCode = unpack('n', $payload)[1];
        }

        return [
            'opcode' => $opcode,
            'fin' => $fin,
            'payload' => $payload,
            'close_code' => $closeCode,
        ];
    }

    /**
     * Apply XOR mask to payload.
     */
    private static function applyMask(string $payload, string $maskKey): string
    {
        $len = strlen($payload);
        $result = '';
        for ($i = 0; $i < $len; $i++) {
            $result .= $payload[$i] ^ $maskKey[$i % 4];
        }

        return $result;
    }

    /**
     * Build a close frame.
     */
    public static function buildCloseFrame(int $code = self::CLOSE_NORMAL, string $reason = ''): string
    {
        $payload = pack('n', $code).$reason;

        return self::encode($payload, self::OP_CLOSE, true, true);
    }

    /**
     * Build a ping frame.
     */
    public static function buildPingFrame(string $payload = ''): string
    {
        return self::encode($payload, self::OP_PING, true, true);
    }

    /**
     * Build a pong frame (response to ping).
     */
    public static function buildPongFrame(string $payload = ''): string
    {
        // Pong must echo the ping payload, unmasked (server->client)
        return self::encode($payload, self::OP_PONG, true, false);
    }
}
