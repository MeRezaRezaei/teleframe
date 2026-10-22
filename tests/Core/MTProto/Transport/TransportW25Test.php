<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core\MTProto\Transport;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\WebSocketFrameCodec;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\WebSocketFrameTooLargeException;
use PHPUnit\Framework\TestCase;

// Worktree class pinning: vendor/ is a symlink to the main tree, so its
// composer autoloader resolves every MeRezaRezaei\Teleframe\* class to the
// main tree's (unfixed) src/. Pin the touched namespace to THIS tree's
// sources instead — prepended so it wins over the shared loader. Harmless
// once merged: the relative path resolves identically in the main tree.
spl_autoload_register(static function (string $class): void {
    $prefix = 'MeRezaRezaei\\Teleframe\\Core\\MTProto\\Transport\\';
    if (str_starts_with($class, $prefix)) {
        $file = __DIR__.'/../../../../src/Core/MTProto/Transport/'
            .str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
        if (is_file($file) && ! class_exists($class, false)) {
            require $file;
        }
    }
}, true, true);

/**
 * W-25 (senior inspection, security): WebSocket frame lengths are now
 * bounded. parseFrame() rejects a declared length above MAX_FRAME_LENGTH —
 * straight from the header, before any body is buffered — with an exception
 * carrying RFC6455 close code 1009, and closeFrame() builds the
 * conformant close reply. Zero-regex discipline maintained.
 */
final class TransportW25Test extends TestCase
{
    // Belt-and-suspenders pin: the shared workspaces bootstrapper registers the
    // whole MeRezaRezaei\Teleframe to this tree before the transport test body
    // autoloads the codec (harmless duplicate of the file-scope pin above).
    public static function setUpBeforeClass(): void
    {
        require dirname(__DIR__, 4).'/tests/Schema/FixesAutoload.php';
    }

    public function test_max_frame_length_is_16_mib(): void
    {
        self::assertSame(16777216, WebSocketFrameCodec::MAX_FRAME_LENGTH);
        self::assertSame(1009, WebSocketFrameCodec::CLOSE_MESSAGE_TOO_BIG);
    }

    public function test_oversized_declared_length_is_rejected_without_a_body_byte(): void
    {
        // FIN + binary opcode, 64-bit extended length declaring MAX+1 — and
        // nothing else. parseFrame must throw before awaiting one payload byte.
        $header = "\x82\x7F".pack('J', WebSocketFrameCodec::MAX_FRAME_LENGTH + 1);

        try {
            WebSocketFrameCodec::parseFrame($header);
            self::fail('oversized declared length must be rejected from the header alone');
        } catch (WebSocketFrameTooLargeException $e) {
            self::assertSame(1009, $e->getCode(), 'exception carries the RFC6455 message-too-big close code');
            self::assertStringContainsString('too large', $e->getMessage());
        }
    }

    public function test_64bit_length_fields_that_wrap_negative_are_rejected(): void
    {
        // Hex lenths > PHP_INT_MAX are float literals on 64-bit PHP, so the
        // frames are glued from hex strings (pack H*) rather than ints.
        foreach (['FFFFFFFFFFFFFFFF', '8000000000000000'] as $hex) {
            $header = "\x82\x7F".pack('H*', $hex);

            try {
                WebSocketFrameCodec::parseFrame($header);
                self::fail('declared length 0x'.$hex.' must be rejected');
            } catch (WebSocketFrameTooLargeException $e) {
                self::assertSame(1009, $e->getCode());
            }
        }
    }

    public function test_frame_exactly_at_the_boundary_parses(): void
    {
        $payload = str_repeat('x', WebSocketFrameCodec::MAX_FRAME_LENGTH);
        $frame = WebSocketFrameCodec::frame(WebSocketFrameCodec::OPCODE_BINARY, $payload);

        $decoded = WebSocketFrameCodec::parseFrame($frame);

        self::assertNotNull($decoded);
        self::assertSame(WebSocketFrameCodec::OPCODE_BINARY, $decoded['opcode']);
        self::assertSame(WebSocketFrameCodec::MAX_FRAME_LENGTH, strlen($decoded['payload']));
    }

    public function test_regular_small_and_16bit_length_frames_still_parse(): void
    {
        foreach (['short hello', str_repeat('a', 70_000)] as $payload) {
            $frame = WebSocketFrameCodec::frame(WebSocketFrameCodec::OPCODE_TEXT, $payload, true);

            $decoded = WebSocketFrameCodec::parseFrame($frame);

            self::assertNotNull($decoded);
            self::assertSame(WebSocketFrameCodec::OPCODE_TEXT, $decoded['opcode']);
            self::assertSame($payload, $decoded['payload']);
            self::assertTrue($decoded['masked']);
        }
    }

    public function test_oversized_masked_client_frame_is_rejected(): void
    {
        $header = "\x82\x7F".pack('J', WebSocketFrameCodec::MAX_FRAME_LENGTH + 1).str_repeat("\x01", 4);

        $this->expectException(WebSocketFrameTooLargeException::class);

        WebSocketFrameCodec::parseFrame($header);
    }

    public function test_close_frame_builder_emits_the_rfc_binary_shape(): void
    {
        $close = WebSocketFrameCodec::closeFrame();
        $decoded = WebSocketFrameCodec::parseFrame($close);

        self::assertNotNull($decoded);
        self::assertSame(WebSocketFrameCodec::OPCODE_CLOSE, $decoded['opcode']);
        self::assertSame(pack('n', 1000), $decoded['payload'], 'normal close carries the 1000 code');
        self::assertFalse($decoded['masked'], 'server frames are never masked');

        $tooBig = WebSocketFrameCodec::closeFrame(WebSocketFrameCodec::CLOSE_MESSAGE_TOO_BIG, 'message too big');
        $decodedBig = WebSocketFrameCodec::parseFrame($tooBig);
        self::assertNotNull($decodedBig);
        self::assertSame(pack('n', 1009).'message too big', $decodedBig['payload']);
    }

    public function test_close_frame_rejects_out_of_range_codes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('16-bit');

        WebSocketFrameCodec::closeFrame(0x10000);
    }
}
