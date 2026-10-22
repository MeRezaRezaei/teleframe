<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use MeRezaRezaei\Teleframe\Core\Passport\PassportDecryptor;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The REAL crypto round-trip: a throwaway RSA keypair is generated at test
 * time (openssl, nothing committed), a fixture passport payload is sealed
 * into the Telegram credential envelope with {@see SpecCredentialFactory} —
 * transcribed from upstream's build order, NOT copied from the implementation
 * — and the decrypt asserts the exact payload comes back.
 *
 * Why the factory and not a local `seal()`: the previous version of this file
 * hashed the plaintext, applied no Telegram padding and let OpenSSL add PKCS#7,
 * which described an algorithm Telegram does not use and passed only because
 * the implementation made the same three mistakes. A test that pins the bug is
 * worse than no test, so the fixture now has exactly one home and it is
 * derived from the spec.
 *
 * The refusal cases are the other half of the contract: tampered hash, bad
 * base64, wrong private key — and, new, the PKCS#7 shape the spec forbids.
 */
final class PassportDecryptorE2eTest extends TestCase
{
    private SpecCredentialFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new SpecCredentialFactory;
    }

    public function test_decrypt_returns_the_exact_sealed_payload(): void
    {
        $payload = [
            'secure_data' => [
                'personal_details' => ['first_name' => 'Ada', 'last_name' => 'Lovelace'],
                'email' => ['address' => 'ada@example.com'],
            ],
            'nonce' => 'aabbccddeeff00112233445566778899',
        ];
        $envelope = $this->factory->seal($payload);

        $decrypted = PassportDecryptor::decryptCredentials(
            $envelope['data'],
            $envelope['secret'],
            $this->factory->privateKeyPem(),
            $envelope['hash'],
        );

        self::assertSame($payload, $decrypted);
    }

    public function test_round_trip_survives_utf8_and_nested_secure_data(): void
    {
        $payload = [
            'secure_data' => [
                'personal_details' => [
                    'first_name' => 'Zoë Müller',
                    'middle_name' => 'José',
                    'last_name' => 'Šćepanović',
                ],
                'phone' => ['phone_number' => '+49 30 1234567'],
            ],
            'nonce' => 'abcdef0123456789',
        ];

        $decrypted = PassportDecryptor::decryptCredentials(
            ...$this->envelopeArgs($this->factory->seal($payload)),
        );

        self::assertSame($payload, $decrypted);
    }

    public function test_the_envelope_carries_a_telegram_padding_not_a_bare_json(): void
    {
        // The padding is the point of the fix: a `Credentials` payload on the
        // wire is 32..255 padding bytes followed by the JSON, never the bare
        // JSON. If the implementation ever stops stripping it again, this
        // still passes — the padding cases themselves live in
        // PassportSpecConformanceTest — but the fixture stops pretending the
        // bare shape is normal.
        $json = '{"secure_data":{},"nonce":"deadbeef"}';
        $padded = $this->factory->pad($json);
        $envelope = $this->factory->sealPadded($padded);

        self::assertNotSame($json, $padded);
        self::assertSame($json, substr($padded, ord($padded[0])));
        self::assertSame(0, strlen($padded) % 16);

        $decrypted = PassportDecryptor::decryptCredentials(
            ...$this->envelopeArgs($envelope),
        );

        self::assertSame(['secure_data' => [], 'nonce' => 'deadbeef'], $decrypted);
    }

    public function test_tampered_hash_throws(): void
    {
        $envelope = $this->factory->seal([
            'secure_data' => ['email' => ['address' => 'ada@example.com']],
            'nonce' => 'deadbeef',
        ]);

        $rawHash = base64_decode($envelope['hash'], true);
        self::assertIsString($rawHash);
        // Flip one bit: still 32 bytes, so it survives the length check, and
        // it disturbs BOTH the key/iv derivation and the hash_equals gate.
        $flipped = $rawHash;
        $flipped[0] = chr(ord($rawHash[0]) ^ 0x01);

        $this->expectException(RuntimeException::class);
        PassportDecryptor::decryptCredentials(
            $envelope['data'],
            $envelope['secret'],
            $this->factory->privateKeyPem(),
            base64_encode($flipped),
        );
    }

    public function test_a_hash_that_is_not_32_bytes_is_refused(): void
    {
        $envelope = $this->factory->seal(['secure_data' => [], 'nonce' => 'x']);

        $this->expectException(RuntimeException::class);
        PassportDecryptor::decryptCredentials(
            $envelope['data'],
            $envelope['secret'],
            $this->factory->privateKeyPem(),
            base64_encode('too short'),
        );
    }

    public function test_bad_base64_data_throws(): void
    {
        $envelope = $this->factory->seal(['secure_data' => [], 'nonce' => 'x']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid base64');

        PassportDecryptor::decryptCredentials(
            '!!! not base64 !!!',
            $envelope['secret'],
            $this->factory->privateKeyPem(),
            $envelope['hash'],
        );
    }

    public function test_bad_base64_secret_throws(): void
    {
        $envelope = $this->factory->seal(['secure_data' => [], 'nonce' => 'x']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid base64');

        PassportDecryptor::decryptCredentials(
            $envelope['data'],
            '@@@ not base64 @@@',
            $this->factory->privateKeyPem(),
            $envelope['hash'],
        );
    }

    public function test_wrong_private_key_throws(): void
    {
        $envelope = $this->factory->seal([
            'secure_data' => ['email' => ['address' => 'ada@example.com']],
            'nonce' => 'wrong-key',
        ]);

        $other = new SpecCredentialFactory;

        $this->expectException(RuntimeException::class);
        PassportDecryptor::decryptCredentials(
            $envelope['data'],
            $envelope['secret'],
            $other->privateKeyPem(),
            $envelope['hash'],
        );
    }

    public function test_every_refusal_carries_the_one_indistinguishable_message(): void
    {
        // Padding-oracle hardening: an RSA-OAEP failure, an integrity failure
        // and a bad padding byte must be indistinguishable to a caller, or the
        // endpoint becomes an oracle for which step failed.
        $envelope = $this->factory->seal(['secure_data' => [], 'nonce' => 'x']);
        $other = new SpecCredentialFactory;

        $refusals = [];

        // 1. wrong key → the RSA-OAEP step.
        $refusals[] = [
            $envelope['data'], $envelope['secret'], $other->privateKeyPem(), $envelope['hash'],
        ];
        // 2. a flipped hash → the integrity gate.
        $flipped = base64_decode($envelope['hash'], true);
        self::assertIsString($flipped);
        $flipped[0] = chr(ord($flipped[0]) ^ 0xFF);
        $refusals[] = [
            $envelope['data'], $envelope['secret'], $this->factory->privateKeyPem(), base64_encode($flipped),
        ];
        // 3. a first padding byte that is not a Telegram padding length.
        $bogus = $this->factory->sealPadded(chr(0x01).random_bytes(31).str_repeat('{}', 8));
        $refusals[] = [
            $bogus['data'], $bogus['secret'], $this->factory->privateKeyPem(), $bogus['hash'],
        ];

        foreach ($refusals as $index => $args) {
            try {
                PassportDecryptor::decryptCredentials(...$args);
                self::fail('refusal #'.$index.' must be refused');
            } catch (RuntimeException $e) {
                self::assertSame(PassportDecryptor::DECRYPT_ERROR, $e->getMessage(), 'refusal #'.$index);
            }
        }
    }

    /**
     * @param  array{data: string, secret: string, hash: string}  $envelope
     * @return array{0: string, 1: string, 2: string, 3: string}
     */
    private function envelopeArgs(array $envelope): array
    {
        return [
            $envelope['data'],
            $envelope['secret'],
            $this->factory->privateKeyPem(),
            $envelope['hash'],
        ];
    }
}
