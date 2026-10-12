<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core\MTProto\Transport;

use MeRezaRezaei\Teleframe\Core\MTProto\Transport\WebSocketFrameCodec;
use PHPUnit\Framework\TestCase;

/**
 * Server-side RFC6455 codec tests — no sockets, no network.
 *
 * Covers the opening handshake (including the RFC6455 §1.3 accept-value
 * test vector), frame encode/decode round-trips, client-mask handling,
 * extended length encodings, and the incomplete-frame boundary (the callers
 * read more bytes and retry).
 */
final class WebSocketFrameCodecTest extends TestCase
{
    public function test_accept_key_matches_the_rfc6455_test_vector(): void
    {
        self::assertSame(
            's3pPLMBiTxaQ9kYGzzhZRbK+xOo=',
            WebSocketFrameCodec::acceptKey('dGhlIHNhbXBsZSBub25jZQ=='),
        );
    }

    public function test_parse_handshake_accepts_a_realistic_browser_request(): void
    {
        $request = "GET /apiws HTTP/1.1\r\n"
            ."Host: example.com\r\n"
            ."Upgrade: websocket\r\n"
            ."Connection: keep-alive, Upgrade\r\n"
            ."Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==\r\n"
            ."Sec-WebSocket-Version: 13\r\n"
            ."Origin: http://example.com\r\n"
            ."\r\n";

        $handshake = WebSocketFrameCodec::parseHandshake($request);
        self::assertNotNull($handshake);
        self::assertSame('dGhlIHNhbXBsZSBub25jZQ==', $handshake['key']);
        self::assertNull($handshake['subprotocol'], 'no subprotocol offered → negotiated none');
    }

    public function test_parse_handshake_negotiates_the_binary_subprotocol(): void
    {
        // MTProto's WebSocket transport requires the client to offer the
        // `binary` subprotocol; GramJS connects with new WebSocket(url, 'binary').
        $request = "GET /apiws HTTP/1.1\r\n"
            ."Host: example.com\r\n"
            ."Upgrade: websocket\r\n"
            ."Connection: Upgrade\r\n"
            ."Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==\r\n"
            ."Sec-WebSocket-Protocol: chat, binary\r\n"
            ."Sec-WebSocket-Version: 13\r\n"
            ."\r\n";

        $handshake = WebSocketFrameCodec::parseHandshake($request);
        self::assertNotNull($handshake);
        self::assertSame('binary', $handshake['subprotocol']);
    }

    public function test_parse_handshake_rejects_non_get_and_missing_upgrade(): void
    {
        self::assertNull(WebSocketFrameCodec::parseHandshake(
            "POST /apiws HTTP/1.1\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Key: a==\r\n\r\n",
        ));
        self::assertNull(WebSocketFrameCodec::parseHandshake(
            "GET /apiws HTTP/1.1\r\nConnection: Upgrade\r\nSec-WebSocket-Key: a==\r\n\r\n",
        ));
        self::assertNull(WebSocketFrameCodec::parseHandshake(
            "GET /apiws HTTP/1.1\r\nUpgrade: websocket\r\nConnection: Upgrade\r\n\r\n",
        ));
    }

    public function test_handshake_response_contains_the_computed_accept(): void
    {
        $response = WebSocketFrameCodec::handshakeResponse('dGhlIHNhbXBsZSBub25jZQ==');

        self::assertStringStartsWith('HTTP/1.1 101', $response);
        self::assertStringContainsString('Upgrade: websocket', $response);
        self::assertStringContainsString('Sec-WebSocket-Accept: s3pPLMBiTxaQ9kYGzzhZRbK+xOo=', $response);
        self::assertStringNotContainsString(
            'Sec-WebSocket-Protocol:',
            $response,
            'no subprotocol negotiated → none echoed',
        );
    }

    public function test_handshake_response_echoes_the_negotiated_binary_subprotocol(): void
    {
        $response = WebSocketFrameCodec::handshakeResponse('dGhlIHNhbXBsZSBub25jZQ==', 'binary');

        self::assertStringContainsString('Sec-WebSocket-Protocol: binary', $response);
    }

    public function test_server_frames_roundtrip_unmasked(): void
    {
        $payload = 'binary-payload-bytes';

        foreach ([2, 1] as $opcode) {
            $frame = WebSocketFrameCodec::frame($opcode, $payload);
            $parsed = WebSocketFrameCodec::parseFrame($frame);

            self::assertNotNull($parsed);
            self::assertSame($opcode, $parsed['opcode']);
            self::assertFalse($parsed['masked'], 'server frames must NOT be masked');
            self::assertSame($payload, $parsed['payload']);
        }
    }

    public function test_client_frames_are_unmasked_after_parse(): void
    {
        $clientFrame = WebSocketFrameCodec::frame(WebSocketFrameCodec::OPCODE_BINARY, 'masked-payload', mask: true);
        $parsed = WebSocketFrameCodec::parseFrame($clientFrame);

        self::assertNotNull($parsed);
        self::assertTrue($parsed['masked'], 'client frames MUST be masked per RFC6455');
        self::assertSame('masked-payload', $parsed['payload']);
    }

    public function test_extended_lengths_encode_and_decode(): void
    {
        // 126-byte payload (2-byte extended length).
        $medium = str_repeat('m', 126);
        $parsed = WebSocketFrameCodec::parseFrame(WebSocketFrameCodec::frame(WebSocketFrameCodec::OPCODE_BINARY, $medium));
        self::assertNotNull($parsed);
        self::assertSame($medium, $parsed['payload']);

        // 70_000-byte payload (8-byte extended length).
        $large = str_repeat('l', 70_000);
        $parsed = WebSocketFrameCodec::parseFrame(WebSocketFrameCodec::frame(WebSocketFrameCodec::OPCODE_BINARY, $large));
        self::assertNotNull($parsed);
        self::assertSame($large, $parsed['payload']);
    }

    public function test_parse_frame_returns_null_for_incomplete_frames(): void
    {
        self::assertNull(WebSocketFrameCodec::parseFrame(''));
        self::assertNull(WebSocketFrameCodec::parseFrame("\x82"));
        self::assertNull(WebSocketFrameCodec::parseFrame("\x82\x0F")); // claims 15 bytes, only header present
        self::assertNull(WebSocketFrameCodec::parseFrame("\x82\x7E\x01")); // 126: needs 2 length bytes

        // Partial payload — header complete, body truncated.
        $full = WebSocketFrameCodec::frame(WebSocketFrameCodec::OPCODE_BINARY, 'hello world');
        self::assertNull(WebSocketFrameCodec::parseFrame(substr($full, 0, -3)));
    }

    public function test_multiple_frames_concatenate_and_decode_independently(): void
    {
        $a = WebSocketFrameCodec::frame(WebSocketFrameCodec::OPCODE_BINARY, 'AAA');
        $b = WebSocketFrameCodec::frame(WebSocketFrameCodec::OPCODE_BINARY, 'BBBBB');

        $parsed = WebSocketFrameCodec::parseFrame($a.$b);
        self::assertNotNull($parsed);
        self::assertSame('AAA', $parsed['payload']);

        $rest = substr($a.$b, strlen($a));
        $parsed = WebSocketFrameCodec::parseFrame($rest);
        self::assertNotNull($parsed);
        self::assertSame('BBBBB', $parsed['payload']);
    }

    public function test_ping_payload_roundtrips(): void
    {
        $ping = WebSocketFrameCodec::frame(WebSocketFrameCodec::OPCODE_PING, 'ping-me');
        $parsed = WebSocketFrameCodec::parseFrame($ping);

        self::assertNotNull($parsed);
        self::assertSame(WebSocketFrameCodec::OPCODE_PING, $parsed['opcode']);
        self::assertSame('ping-me', $parsed['payload']);
    }
}
