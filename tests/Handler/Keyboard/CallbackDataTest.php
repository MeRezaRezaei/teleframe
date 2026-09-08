<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Handler\Keyboard;

use MeRezaRezaei\Teleframe\Handler\Keyboard\CallbackData;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Q15 codec: round-trip, the 64-byte budget, and every rejection surface —
 * forged signatures, tampered fields, rebound msg/chat, wrong secret, and
 * malformed token shapes must all decode to null (zero-regex colon-split).
 */
final class CallbackDataTest extends TestCase
{
    private const SECRET = 'test-hmac-secret';

    public function test_round_trip_returns_route_and_arg(): void
    {
        $token = CallbackData::encode('a1b2c3d8f', 2, 'dark', 7, 421, self::SECRET);

        self::assertSame(['route' => 'a1b2c3d8f:2', 'arg' => 'dark'], CallbackData::decode($token, 7, 421, self::SECRET));
    }

    public function test_round_trip_with_string_ids(): void
    {
        $token = CallbackData::encode('afe00712', 0, 'sku-1', '-100123', '@channel', self::SECRET);

        self::assertSame(['route' => 'afe00712:0', 'arg' => 'sku-1'], CallbackData::decode($token, '-100123', '@channel', self::SECRET));
    }

    public function test_empty_arg_round_trips(): void
    {
        $token = CallbackData::encode('a1b2c3d8f', 0, '', 7, 421, self::SECRET);

        self::assertSame(['route' => 'a1b2c3d8f:0', 'arg' => ''], CallbackData::decode($token, 7, 421, self::SECRET));
    }

    public function test_token_fits_the_64_byte_callback_budget(): void
    {
        $token = CallbackData::encode('a1b2c3d8f', 2, 'some-argument-string', 7, 421, self::SECRET);

        self::assertLessThanOrEqual(64, strlen($token));
        self::assertSame(4, substr_count($token, ':'));
    }

    public function test_forged_signature_is_rejected(): void
    {
        $token = CallbackData::encode('a1b2c3d8f', 0, 'dark', 7, 421, self::SECRET);
        $flipped = substr($token, 0, -1) . ($token[-1] === 'A' ? 'B' : 'A');

        self::assertNull(CallbackData::decode($flipped, 7, 421, self::SECRET));
    }

    public function test_tampered_arg_is_rejected(): void
    {
        $token = CallbackData::encode('a1b2c3d8f', 0, 'dark', 7, 421, self::SECRET);
        $tampered = str_replace(':dark:', ':light:', $token);

        self::assertNull(CallbackData::decode($tampered, 7, 421, self::SECRET));
    }

    public function test_tampered_key_idx_is_rejected(): void
    {
        $token = CallbackData::encode('a1b2c3d8f', 1, 'dark', 7, 421, self::SECRET);
        $tampered = str_replace(':1:', ':9:', $token);

        self::assertNull(CallbackData::decode($tampered, 7, 421, self::SECRET));
    }

    public function test_replay_with_rebound_message_is_rejected(): void
    {
        $token = CallbackData::encode('a1b2c3d8f', 0, 'dark', 7, 421, self::SECRET);

        self::assertSame(['route' => 'a1b2c3d8f:0', 'arg' => 'dark'], CallbackData::decode($token, 7, 421, self::SECRET));
        self::assertNull(CallbackData::decode($token, 8, 421, self::SECRET));
    }

    public function test_relay_into_another_chat_is_rejected(): void
    {
        $token = CallbackData::encode('a1b2c3d8f', 0, 'dark', 7, 421, self::SECRET);

        self::assertNull(CallbackData::decode($token, 7, 422, self::SECRET));
        self::assertNull(CallbackData::decode($token, null, 422, self::SECRET));
    }

    public function test_wrong_secret_is_rejected(): void
    {
        $token = CallbackData::encode('a1b2c3d8f', 0, 'dark', 7, 421, self::SECRET);

        self::assertNull(CallbackData::decode($token, 7, 421, 'not-the-secret'));
    }

    public function test_unknown_format_is_rejected(): void
    {
        $token = CallbackData::encode('a1b2c3d8f', 0, 'dark', 7, 421, self::SECRET);
        $rebranded = 'v9' . substr($token, 2);

        self::assertNull(CallbackData::decode($rebranded, 7, 421, self::SECRET));
    }

    public function test_malformed_token_shapes_are_rejected(): void
    {
        $env = [
            'garbage',
            '',
            'v1:a1b2c3d8f:0:dark',
            'v1:a1b2c3d8f:0:dark:',
            'v1:a1b2c3d8f::dark:sig',
            ':a1b2c3d8f:0:dark:sig',
            'a1b2c3d8f:0:dark:sig',
        ];

        foreach ($env as $token) {
            self::assertNull(CallbackData::decode($token, 7, 421, self::SECRET), "token [$token] must reject");
        }
    }

    public function test_encode_rejects_colon_in_signed_fields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CallbackData::encode('bad:key', 0, 'dark', 7, 421, self::SECRET);
    }

    public function test_encode_rejects_colon_in_arg(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CallbackData::encode('a1b2c3d8f', 0, 'a:b', 7, 421, self::SECRET);
    }

    public function test_encode_rejects_negative_key_idx(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CallbackData::encode('a1b2c3d8f', -1, 'dark', 7, 421, self::SECRET);
    }

    #[DataProvider('longPayloads')]
    public function test_encode_rejects_token_over_the_budget(string $keyId, string $arg): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CallbackData::encode($keyId, 0, $arg, 7, 421, self::SECRET);
    }

    public static function longPayloads(): array
    {
        return [
            'long keyId + arg' => ['a1b2c3d8f-extra-long', str_repeat('x', 30)],
            'long arg' => ['a1b2c3d8f', str_repeat('x', 40)],
        ];
    }
}