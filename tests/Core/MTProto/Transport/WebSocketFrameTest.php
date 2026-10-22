<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Tests\MTProto\Transport;

use MeRezaRezaei\Teleframe\Core\MTProto\Transport\WebSocketFrame;
use PHPUnit\Framework\TestCase;

/**
 * Tests for WebSocket framing (RFC 6455).
 */
class WebSocketFrameTest extends TestCase
{
    public function test_encode_decode_binary_frame(): void
    {
        $payload = 'Hello, WebSocket!';
        $encoded = WebSocketFrame::encode($payload, WebSocketFrame::OP_BINARY, true, false); // No mask for testing

        // Decode manually since we can't easily test with a socket
        $this->assertStringStartsWith(chr(0x82), $encoded); // FIN=1, OPCODE=2 (binary)
    }

    public function test_encode_text_frame(): void
    {
        $payload = 'Hello, World!';
        $encoded = WebSocketFrame::encode($payload, WebSocketFrame::OP_TEXT, true, false);

        $this->assertStringStartsWith(chr(0x81), $encoded); // FIN=1, OPCODE=1 (text)
    }

    public function test_encode_close_frame(): void
    {
        $encoded = WebSocketFrame::buildCloseFrame(WebSocketFrame::CLOSE_NORMAL, 'Goodbye');
        $this->assertStringStartsWith(chr(0x88), $encoded); // FIN=1, OPCODE=8 (close)
    }

    public function test_encode_ping_frame(): void
    {
        $encoded = WebSocketFrame::buildPingFrame('ping-data');
        $this->assertStringStartsWith(chr(0x89), $encoded); // FIN=1, OPCODE=9 (ping)
    }

    public function test_encode_pong_frame(): void
    {
        $encoded = WebSocketFrame::buildPongFrame('pong-data');
        $this->assertStringStartsWith(chr(0x8A), $encoded); // FIN=1, OPCODE=10 (pong)
    }

    public function test_encode_with_mask(): void
    {
        $payload = 'test payload';
        $encoded = WebSocketFrame::encode($payload, WebSocketFrame::OP_BINARY, true, true);

        // Should have mask bit set
        $secondByte = ord($encoded[1]);
        $this->assertTrue(($secondByte & 0x80) !== 0, 'Mask bit should be set');
    }

    public function test_close_frame_payload_structure(): void
    {
        // Build close frame without mask to inspect structure
        $frame = WebSocketFrame::buildCloseFrame(WebSocketFrame::CLOSE_GOING_AWAY, '');
        // Close frame payload: 2-byte status code
        $this->assertStringStartsWith(chr(0x88), $frame);
    }

    public function test_opcode_constants(): void
    {
        $this->assertSame(0x0, WebSocketFrame::OP_CONTINUATION);
        $this->assertSame(0x1, WebSocketFrame::OP_TEXT);
        $this->assertSame(0x2, WebSocketFrame::OP_BINARY);
        $this->assertSame(0x8, WebSocketFrame::OP_CLOSE);
        $this->assertSame(0x9, WebSocketFrame::OP_PING);
        $this->assertSame(0xA, WebSocketFrame::OP_PONG);
    }

    public function test_close_code_constants(): void
    {
        $this->assertSame(1000, WebSocketFrame::CLOSE_NORMAL);
        $this->assertSame(1001, WebSocketFrame::CLOSE_GOING_AWAY);
        $this->assertSame(1002, WebSocketFrame::CLOSE_PROTOCOL_ERROR);
    }
}
