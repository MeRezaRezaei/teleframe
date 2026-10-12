<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Tests;

use MeRezaRezaei\Teleframe\Core\Passport\PassportDecryptor;
use MeRezaRezaei\Teleframe\Tests\Passport\SpecCredentialFactory;
use PHPUnit\Framework\TestCase;

/**
 * The `Credentials` envelope round-trip, at the engine's own floor
 * (`src/Core/Passport/PassportDecryptor.php`), on a fixture built from
 * upstream's build order rather than from this repository's implementation.
 *
 * This test previously hashed the PLAINTEXT of an UNPADDED payload and let
 * OpenSSL add PKCS#7 to the ciphertext — a fixture describing an algorithm
 * Telegram never uses, which passed only because the implementation made the
 * same three mistakes. The fixture is now `SpecCredentialFactory`'s; the
 * per-step conformance proofs live in
 * `tests/Passport/PassportSpecConformanceTest.php`.
 */
class PassportDecryptorTest extends TestCase
{
    public function test_passport_decryption_and_verification(): void
    {
        $factory = new SpecCredentialFactory;

        $passportData = [
            'secure_data' => [
                'personal_details' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'birth_date' => '01.01.1990',
                    'country_code' => 'US',
                ],
            ],
            'nonce' => '5f2b1c9d4e7a8b0c1d2e3f4a5b6c7d8e',
        ];

        $envelope = $factory->seal($passportData);

        $decrypted = PassportDecryptor::decryptCredentials(
            $envelope['data'],
            $envelope['secret'],
            $factory->privateKeyPem(),
            $envelope['hash']
        );

        $this->assertSame($passportData, $decrypted);
        $this->assertEquals('John', $decrypted['secure_data']['personal_details']['first_name']);
        $this->assertEquals('Doe', $decrypted['secure_data']['personal_details']['last_name']);
        $this->assertEquals('US', $decrypted['secure_data']['personal_details']['country_code']);
    }
}
