<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core\MTProto\Transport;

use MeRezaRezaei\Teleframe\Core\MTProto\Transport\Obfuscated2Cipher;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\Obfuscated2Codec;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\Obfuscated2Exception;
use PHPUnit\Framework\TestCase;

/**
 * Pure obfuscated2 transport codec: header parse/validate against the REAL
 * client header shape (transport obfuscation spec as implemented by GramJS
 * initHeader / TDesktop prepareConnectionStartPrefix): raw key material at
 * bytes 8-56, encrypted tag+dcId+padding at bytes 56-64 under the
 * from-client AES-256-CTR keystream at offset 56. Zero-regex, string
 * functions only; no sockets, no network.
 */
final class Obfuscated2CodecTest extends TestCase
{
    private const RANDOM_WINDOW = "\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x10"
        ."\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F\x20"
        ."\x21\x22\x23\x24\x25\x26\x27\x28\x29\x2A\x2B\x2C\x2D\x2E\x2F\x30";

    /**
     * Builds a client-shaped 64-byte obfuscated2 header exactly the way the
     * vendored GramJS ObfuscatedIO::initHeader does: random nonce (first
     * byte not 0xef, no forbidden prefixes, bytes 4-8 nonzero), raw key
     * window at 8-56, then the tag+dcId+padding tail encrypted with the
     * from-client keystream at offset 56.
     */
    public static function clientHeader(int $dcId, string $protocol = 'abridged', string $window = self::RANDOM_WINDOW): string
    {
        $tag = match ($protocol) {
            'abridged' => "\xEF\xEF\xEF\xEF",
            'intermediate' => "\xEE\xEE\xEE\xEE",
            'secure-intermediate' => "\xDD\xDD\xDD\xDD",
            default => throw new \InvalidArgumentException("unknown protocol {$protocol}"),
        };

        // Bytes 0-8: nonce; the relay never reads them as key material.
        $nonce = "\x71\x72\x73\x74\x75\x76\x77\x78";

        $tail = $tag
            .pack('v', $dcId)
            ."\x79\x7A"; // encrypted padding the client chose

        $encryptKey = substr($window, 0, 32);
        $encryptIv = substr($window, 32, 16);
        $encryptedTail = Obfuscated2Cipher::xcryptAt($tail, $encryptKey, $encryptIv, 56);

        return $nonce.$window.$encryptedTail;
    }

    public function test_parse_header_derives_raw_keys_and_routes_on_the_encrypted_dc_id(): void
    {
        foreach ([
            ['abridged', 2],
            ['intermediate', 5],
            ['secure-intermediate', 100],
        ] as [$protocol, $dcId]) {
            $header = self::clientHeader($dcId, $protocol);

            $parsed = Obfuscated2Codec::parseHeader($header);

            $window = substr($header, 8, 48);
            self::assertSame(substr($window, 0, 32), $parsed['decryptKey'], "{$protocol} from-client key is raw header[8:40]");
            self::assertSame(substr($window, 32, 16), $parsed['decryptIv'], "{$protocol} from-client iv is raw header[40:56]");
            self::assertSame(substr(strrev($window), 0, 32), $parsed['encryptKey'], "{$protocol} to-client key is reversed-window[0:32]");
            self::assertSame(substr(strrev($window), 32, 16), $parsed['encryptIv'], "{$protocol} to-client iv is reversed-window[32:48]");
            self::assertSame($dcId, $parsed['dc_id'], "{$protocol} dc id from encrypted bytes 60-62");
            self::assertSame($protocol, $parsed['protocol'], "{$protocol} protocol from encrypted tag");
        }
    }

    public function test_negative_test_dc_ids_decode_as_signed_int16(): void
    {
        // Test-server clients write a negative int16 dc id; it must survive
        // the little-endian sign conversion instead of wrapping to 6553x.
        $header = self::clientHeader(-2, 'abridged');

        self::assertSame(-2, Obfuscated2Codec::parseHeader($header)['dc_id']);
    }

    public function test_garbage_tail_is_rejected(): void
    {
        // A header whose encrypted tail decrypts to no known tag is what a
        // non-obfuscated2 connection (or corruption) looks like.
        $header = self::clientHeader(5, 'abridged');
        $header[56] = $header[56] ^ "\xFF";

        self::expectException(Obfuscated2Exception::class);
        self::expectExceptionMessage('magic');

        Obfuscated2Codec::parseHeader($header);
    }

    public function test_plain_transport_markers_in_the_nonce_are_accepted(): void
    {
        // Bytes 0-4 are plain nonce from the relay's perspective — a client
        // may legitimately start with 0xEE (what the old house codec
        // misread as the "abridged tag" in cleartext). The encrypted tail is
        // the only thing that identifies the transport.
        $header = self::clientHeader(5, 'abridged');
        $header[0] = "\xEE";

        self::assertSame(5, Obfuscated2Codec::parseHeader($header)['dc_id']);
    }

    public function test_header_leading_with_the_abridged_init_byte_is_rejected(): void
    {
        // First byte 0xEF is the plain-abridged init marker — a raw abridged
        // stream, not an obfuscated2 header.
        $header = self::clientHeader(5, 'abridged');
        $header[0] = "\xEF";

        self::expectException(Obfuscated2Exception::class);
        self::expectExceptionMessage('0xEF');

        Obfuscated2Codec::parseHeader($header);
    }

    public function test_header_with_all_zero_bytes_4_to_8_is_rejected(): void
    {
        // A degenerate all-zero keystream window is a plaintext transport
        // giveaway and must not key-derive.
        $header = self::clientHeader(5, 'abridged');
        $header[4] = $header[5] = $header[6] = $header[7] = "\x00";

        self::expectException(Obfuscated2Exception::class);
        self::expectExceptionMessage('all zero');

        Obfuscated2Codec::parseHeader($header);
    }

    public function test_header_colliding_with_an_intermediate_init_tag_is_rejected(): void
    {
        // Bytes 0-4 matching a cleartext intermediate init tag (\xEE x4) is
        // not an obfuscated2 header — the tag belongs inside the encrypted
        // tail, never in the open nonce prefix.
        $header = self::clientHeader(5, 'intermediate');
        $header[0] = $header[1] = $header[2] = $header[3] = "\xEE";

        self::expectException(Obfuscated2Exception::class);
        self::expectExceptionMessage('plain-transport init marker');

        Obfuscated2Codec::parseHeader($header);
    }

    public function test_too_short_header_is_rejected(): void
    {
        self::expectException(Obfuscated2Exception::class);
        self::expectExceptionMessage('64 bytes');

        Obfuscated2Codec::parseHeader(substr(self::clientHeader(3, 'abridged'), 0, 63));
    }

    public function test_cipher_roundtrip_restores_the_payload(): void
    {
        // A real client encrypts with the FORWARD window as its to-server
        // pair; the server's from-client pair is identical, so decrypting
        // the client's ciphertext must restore the plaintext — and the
        // header's 64 bytes must already be consumed in that direction.
        $header = self::clientHeader(4, 'intermediate');
        $window = substr($header, 8, 48);

        $serverCipher = Obfuscated2Cipher::fromHeader($header);
        $clientToServer = new Obfuscated2Cipher(
            substr($window, 0, 32),
            substr(strrev($window), 0, 32),
            substr($window, 32, 16),
            substr(strrev($window), 32, 16),
        );

        // Consume the header exactly like the client does (it encrypts the
        // full 64 bytes with its to-server cipher, keeping only the tail).
        $clientToServer->xcrypt(str_repeat("\x00", 64), true);

        $payload = str_repeat("\xAB\xCD\xEF\x11\x22\x33\x44\x55", 10); // 80 bytes, 5 AES blocks

        $encrypted = $clientToServer->xcrypt($payload, true);
        self::assertNotSame($payload, $encrypted, 'obfuscation must change the payload');

        self::assertSame($payload, $serverCipher->xcrypt($encrypted, true));
    }

    public function test_cipher_keystream_is_monotonic_across_chunk_boundaries(): void
    {
        // Two xcrypt calls on one instance must continue the stream, not
        // restart it: a 96-byte (6-block) payload split in half and decrypted
        // in one shot by a fresh instance yields the original only if the
        // counter advanced between the calls.
        $header = self::clientHeader(5, 'secure-intermediate');
        $window = substr($header, 8, 48);

        $cipher = Obfuscated2Cipher::fromHeader($header);
        $verifier = new Obfuscated2Cipher(
            substr($window, 0, 32),
            substr(strrev($window), 0, 32),
            substr($window, 32, 16),
            substr(strrev($window), 32, 16),
        );
        $verifier->xcrypt(str_repeat("\x00", 64), true);

        $payload = str_repeat("\x12\x34\x56\x78\x9A\xBC\xDE\xF0", 12); // 96 bytes
        $half = strlen($payload) >> 1;

        $wire = $cipher->xcrypt(substr($payload, 0, $half), true)
            .$cipher->xcrypt(substr($payload, $half), true);

        self::assertNotSame($payload, $wire);
        self::assertSame($payload, $verifier->xcrypt($wire, true), 'keystream must stay monotonic across calls');
    }

    public function test_server_to_client_direction_starts_at_keystream_offset_zero(): void
    {
        // The client's from-server pair is the reversed window at offset 0;
        // the server's to-client direction must match so the client can
        // decrypt upstream plaintext from the very first byte.
        $header = self::clientHeader(6, 'abridged');
        $window = substr($header, 8, 48);

        $serverCipher = Obfuscated2Cipher::fromHeader($header);
        $clientFromServer = new Obfuscated2Cipher(
            substr(strrev($window), 0, 32),
            substr(strrev($window), 0, 32), // client only ever decrypts with this pair
            substr(strrev($window), 32, 16),
            substr(strrev($window), 32, 16),
        );

        $payload = 'upstream-plaintext-frame';

        self::assertSame(
            $payload,
            $clientFromServer->xcrypt($serverCipher->xcrypt($payload, false), false),
        );
    }
}
