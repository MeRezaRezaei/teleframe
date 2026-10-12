<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core\MTProto;

use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use PHPUnit\Framework\TestCase;

/**
 * The auth key must never ride a plain array.
 *
 * SessionData is the DTO that holds the MTProto auth key; an array of one is
 * the shape that ends up in a log line, a debug payload or an API response.
 * exportString()/importString() remain the credential-shaped persistence
 * path; toArray() is for the NON-secret context, and a caller that genuinely
 * needs the key has to say so.
 */
final class SessionDataArrayTest extends TestCase
{
    private const KEY = "\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0a\x0b\x0c\x0d\x0e\x0f\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1a\x1b\x1c\x1d\x1e\x1f\x20";

    public function test_to_array_does_not_carry_the_auth_key(): void
    {
        $session = new SessionData(dcId: 2, authKey: self::KEY, userId: 99, serverTimeDelta: -5, serverSalt: 1234);

        $array = $session->toArray();

        self::assertArrayNotHasKey('auth_key', $array);
        self::assertStringNotContainsString(base64_encode(self::KEY), (string) json_encode($array));
        // the non-secret context is intact
        self::assertSame(2, $array['dc_id']);
        self::assertSame(99, $array['user_id']);
        self::assertSame(-5, $array['server_time_delta']);
        self::assertSame(1234, $array['server_salt']);
        self::assertSame(0, $array['seq_no']);
    }

    public function test_the_auth_key_is_opt_in_and_still_roundtrips(): void
    {
        $session = new SessionData(dcId: 4, authKey: self::KEY, userId: 7);

        $withKey = $session->toArray(includeAuthKey: true);

        self::assertSame(base64_encode(self::KEY), $withKey['auth_key']);
        $restored = SessionData::fromArray($withKey);
        self::assertSame(self::KEY, $restored->authKey);
        self::assertSame(4, $restored->dcId);
        self::assertSame(7, $restored->userId);
    }

    public function test_the_export_string_path_is_unchanged(): void
    {
        // The credential-shaped persistence path is deliberately untouched —
        // it is how a session is stored, and it is opaque (one base64 blob).
        $session = new SessionData(dcId: 2, authKey: self::KEY, userId: 99, serverTimeDelta: -5, serverSalt: 1234);

        $restored = SessionData::importString($session->exportString());

        self::assertSame(self::KEY, $restored->authKey);
        self::assertSame(2, $restored->dcId);
        self::assertSame(99, $restored->userId);
        self::assertSame(1234, $restored->serverSalt);
    }
}
