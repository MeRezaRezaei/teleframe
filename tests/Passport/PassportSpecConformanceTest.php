<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use MeRezaRezaei\Teleframe\Core\Passport\PassportDecryptor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * PassportSpecConformanceTest — ONE test per NORMATIVE STEP of
 * `docs/reference/telegram-passport-official.md`, with fixtures transcribed
 * from upstream's own words.
 *
 * The rule this file exists to enforce: a fixture is built from the SPEC, and
 * where the spec and the implementation disagree the fixture is RIGHT and the
 * implementation is wrong. The previous suite in this repository had the
 * opposite polarity — its fixtures hashed an unpadded plaintext and let
 * OpenSSL add PKCS#7, which is not an algorithm Telegram uses, so it agreed
 * with the implementation by construction. That is how two spec violations
 * reached green CI, and it is why "the tests pass" was not evidence of
 * anything.
 *
 * Every test here names the step it proves, and the three that failed against
 * real Telegram data are marked BEFORE-THIS-FIX:
 *
 *   §3.4 the 32–255 byte padding is stripped.           (BEFORE-THIS-FIX)
 *   §3   AES-CBC carries no implicit block padding.       (BEFORE-THIS-FIX)
 *   §3.1 the secret is RSA-OAEP and nothing else.
 *   §3.2 the key/iv derivation, re-derived here independently.
 *   §3.3 the integrity hash, over the AES INPUT.
 *   §4   the nested DataCredentials / FileCredentials decryptions.
 */
final class PassportSpecConformanceTest extends TestCase
{
    private SpecCredentialFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new SpecCredentialFactory;
    }

    // ── §3.1 the secret ──────────────────────────────────────────────────

    public function test_step1_the_secret_is_sealed_with_rsa_oaep(): void
    {
        $json = '{"secure_data":{},"nonce":"0123456789abcdef"}';
        $padded = $this->factory->pad($json);
        $secret = random_bytes(32);
        $hash = $this->factory->credentialsHash($padded);
        $envelope = [
            'data' => base64_encode($this->factory->encrypt($padded, $secret, $hash)),
            'hash' => base64_encode($hash),
        ];

        $oaep = '';
        self::assertTrue(openssl_public_encrypt($secret, $oaep, $this->factory->publicKeyPem(), OPENSSL_PKCS1_OAEP_PADDING));
        $envelope['secret'] = base64_encode($oaep);

        $decrypted = PassportDecryptor::decryptCredentials(
            $envelope['data'],
            $envelope['secret'],
            $this->factory->privateKeyPem(),
            $envelope['hash'],
        );

        self::assertSame(json_decode($json, true), $decrypted);
    }

    public function test_step1_a_pkcs1_v15_sealed_secret_is_refused(): void
    {
        // "credentials_secret is encrypted with the public RSA-key of the
        // service with OPENSSL_PKCS1_OAEP_PADDING." The previous
        // implementation once FELL BACK to PKCS#1 v1.5, which both downgraded
        // the pad and let a caller distinguish the two modes. There is no
        // fallback now, and this proves it: a v1.5 seal is refused.
        $json = '{"secure_data":{},"nonce":"0123456789abcdef"}';
        $padded = $this->factory->pad($json);
        $secret = random_bytes(32);
        $hash = $this->factory->credentialsHash($padded);

        $legacy = '';
        self::assertTrue(openssl_public_encrypt($secret, $legacy, $this->factory->publicKeyPem(), OPENSSL_PKCS1_PADDING));

        $this->expectException(RuntimeException::class);
        PassportDecryptor::decryptCredentials(
            base64_encode($this->factory->encrypt($padded, $secret, $hash)),
            base64_encode($legacy),
            $this->factory->privateKeyPem(),
            base64_encode($hash),
        );
    }

    // ── §3.2 the key derivation ──────────────────────────────────────────

    public function test_step2_key_and_iv_are_sha512_of_secret_plus_hash_sliced(): void
    {
        // Re-derived HERE, independently of the implementation:
        //   credentials_secret_hash = SHA512(secret + hash)   (raw)
        //   key = [0,32]   iv = [32,16]
        $json = '{"secure_data":{"personal_details":{"first_name":"Grace"}},"nonce":"1122334455667788"}';
        $padded = $this->factory->pad($json);
        $secret = random_bytes(32);
        $hash = $this->factory->credentialsHash($padded);

        $derived = hash('sha512', $secret.$hash, true);
        self::assertSame(substr($derived, 0, 32), $this->factory->keyFor($secret, $hash));
        self::assertSame(substr($derived, 32, 16), $this->factory->ivFor($secret, $hash));
        self::assertNotSame($derived, hash('sha512', $hash.$secret, true), 'concatenation order is secret + hash');

        $envelope = [
            'data' => base64_encode(openssl_encrypt(
                $padded,
                'aes-256-cbc',
                substr($derived, 0, 32),
                OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
                substr($derived, 32, 16)
            )),
            'hash' => base64_encode($hash),
            'secret' => base64_encode($this->factory->sealSecret($secret)),
        ];

        self::assertSame(
            json_decode($json, true),
            PassportDecryptor::decryptCredentials(
                $envelope['data'],
                $envelope['secret'],
                $this->factory->privateKeyPem(),
                $envelope['hash'],
            )
        );
    }

    // ── §3.3 the integrity hash ──────────────────────────────────────────

    public function test_step3_the_hash_is_sha256_of_the_padded_cipher_input_not_of_the_ciphertext(): void
    {
        // "A hash of the padded credentials credentials_hash is calculated:
        //  credentials_hash = SHA256( credentials )" — the padding paragraph
        // comes first and the variable that holds the ciphertext is named
        // encrypted_credentials, so `credentials` is the AES INPUT.
        //
        // The two readings are provably different values for the SAME
        // envelope, and only one of them is constructible at all: the key is
        // derived from this hash, so hashing the ciphertext would demand a
        // fixed point (a ciphertext whose own digest seeds the key that
        // produces it). So this test asserts BOTH halves — the hash equals
        // SHA-256(padded plaintext) and does NOT equal SHA-256(ciphertext) —
        // because an implementation that "verifies" the ciphertext reading
        // cannot even be handed a fixture.
        $json = '{"secure_data":{},"nonce":"cafebabe"}';
        $padded = $this->factory->pad($json);
        $secret = random_bytes(32);
        $hash = $this->factory->credentialsHash($padded);
        $ciphertext = $this->factory->encrypt($padded, $secret, $hash);

        self::assertSame(hash('sha256', $padded, true), $hash, 'the hash is over the padded plaintext');
        self::assertNotSame(hash('sha256', $ciphertext, true), $hash, 'the two readings are not the same value');
    }

    public function test_step3_a_hash_over_a_different_ciphertext_is_refused(): void
    {
        $envelope = $this->factory->seal(['secure_data' => [], 'nonce' => 'x']);

        $other = $this->factory->seal(['secure_data' => [], 'nonce' => 'y']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(PassportDecryptor::DECRYPT_ERROR);

        // Data from one envelope with the hash of another.
        PassportDecryptor::decryptCredentials(
            $other['data'],
            $envelope['secret'],
            $this->factory->privateKeyPem(),
            $envelope['hash'],
        );
    }

    public function test_step3_the_previous_ciphertext_shaped_fixture_is_no_longer_an_envelope(): void
    {
        // BEFORE-THIS-FIX, in one assertion: the envelope this repository's
        // own suite used to build — an unpadded plaintext hashed directly,
        // with OpenSSL's PKCS#7 block on the ciphertext — IS NOT a Telegram
        // envelope, and is refused. The old implementation accepted it, which
        // is precisely why the suite was green and the login was broken.
        $json = (string) json_encode(['secure_data' => [], 'nonce' => 'legacy']);
        $legacyHash = hash('sha256', $json, true);
        $secret = random_bytes(32);
        $derived = hash('sha512', $secret.$legacyHash, true);

        $legacyCiphertext = openssl_encrypt(
            $json,
            'aes-256-cbc',
            substr($derived, 0, 32),
            OPENSSL_RAW_DATA, // ← the implicit PKCS#7 upstream never writes
            substr($derived, 32, 16)
        );
        self::assertIsString($legacyCiphertext);

        $this->expectException(RuntimeException::class);
        PassportDecryptor::decryptCredentials(
            base64_encode($legacyCiphertext),
            base64_encode($this->factory->sealSecret($secret)),
            $this->factory->privateKeyPem(),
            base64_encode($legacyHash),
        );
    }

    public function test_step3_a_pkcs7_shaped_ciphertext_is_refused(): void
    {
        // §3: the payload is already a multiple of 16, so there is no PKCS#7
        // block to strip. An envelope that carries one is not an envelope.
        $json = '{"secure_data":{},"nonce":"pkcs7"}';
        $padded = $this->factory->pad($json);
        $secret = random_bytes(32);
        $hash = $this->factory->credentialsHash($padded);

        $withPkcs7 = openssl_encrypt(
            $padded,
            'aes-256-cbc',
            $this->factory->keyFor($secret, $hash),
            OPENSSL_RAW_DATA, // adds a PKCS#7 block
            $this->factory->ivFor($secret, $hash)
        );
        self::assertIsString($withPkcs7);

        $this->expectException(RuntimeException::class);
        PassportDecryptor::decryptCredentials(
            base64_encode($withPkcs7),
            base64_encode($this->factory->sealSecret($secret)),
            $this->factory->privateKeyPem(),
            base64_encode($hash),
        );
    }

    // ── §3.4 the padding ─────────────────────────────────────────────────

    public static function paddingLengths(): array
    {
        // The JSON lengths differ because the padded TOTAL must be divisible
        // by 16: 32 is a multiple of 16, so its payload must be too; 255 is
        // not (255 = 16*15 + 15), so its payload must be 1 mod 16. This is
        // the "if a fixture cannot satisfy the spec, that is the finding"
        // rule, satisfied by choosing lengths rather than by loosening the
        // padding rule.
        return [
            'minimum 32' => [32, 64],
            'maximum 255' => [255, 65],
        ];
    }

    /**
     * BEFORE-THIS-FIX. "32 to 255 bytes are added at the beginning, where the
     * first byte always holds the number of added bytes and the rest are
     * random."
     */
    #[DataProvider('paddingLengths')]
    public function test_step4_padding_of_32_and_of_255_both_round_trip(int $paddingLength, int $jsonLength): void
    {
        $json = $this->factory->credentialsJsonOfLength($jsonLength);
        $padded = $this->factory->pad($json, $paddingLength);

        // The fixture itself is checked against the spec BEFORE it is used: if
        // this ever throws, the FIXTURE is wrong, not the implementation.
        self::assertSame(0, strlen($padded) % 16, 'the padded payload is divisible by 16');
        self::assertSame($paddingLength, ord($padded[0]), 'the first byte IS the padding length');
        self::assertSame($json, substr($padded, $paddingLength));

        $envelope = $this->factory->sealPadded($padded);

        $decrypted = PassportDecryptor::decryptCredentials(
            $envelope['data'],
            $envelope['secret'],
            $this->factory->privateKeyPem(),
            $envelope['hash'],
        );

        self::assertSame(json_decode($json, true), $decrypted);
    }

    public function test_step4_a_padding_fixture_that_cannot_satisfy_the_spec_is_reported(): void
    {
        // A length that cannot make the payload divisible by 16 is a broken
        // FIXTURE, and says so instead of being silently adjusted.
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('divisible by 16');

        // 48 + 1 is not a multiple of 16, so no 48-byte padding exists for a
        // 1-byte payload.
        $this->factory->pad('x', 48);
    }

    /**
     * BEFORE-THIS-FIX. A first byte that is not a real padding length must be
     * REFUSED. A naive `substr($padded, ord($padded[0]))` does not refuse it —
     * it strips whatever number happens to be there and hands the caller a
     * payload one byte short, which looks like it worked.
     *
     */
    #[DataProvider('invalidPaddingFirstBytes')]
    public function test_step4_a_first_byte_that_is_not_its_own_padding_length_is_refused(int $firstByte): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(PassportDecryptor::DECRYPT_ERROR);

        // 0x00 = "no padding", 0x01 and 0x1F = below the 32 floor, 0xFE =
        // longer than the 48-byte payload. None of them is a Telegram padding
        // length; all of them are what a stripped-everything implementation
        // would happily truncate by.
        $padded = chr($firstByte).str_repeat("\xAB", 47);
        $envelope = $this->factory->sealPadded($padded);

        PassportDecryptor::decryptCredentials(
            $envelope['data'],
            $envelope['secret'],
            $this->factory->privateKeyPem(),
            $envelope['hash'],
        );
    }

    /** @return array<string, array{0: int}> */
    public static function invalidPaddingFirstBytes(): array
    {
        return [
            'zero (no padding)' => [0x00],
            'one (the classic off-by-one)' => [0x01],
            'one below the floor' => [0x1F],
            'longer than the payload' => [0xFE],
        ];
    }

    public function test_step4_a_first_byte_of_32_on_a_48_byte_padding_is_refused_not_truncated(): void
    {
        // The self-consistency case a length check alone would wave through:
        // the first byte claims 32, but the payload really begins 48 bytes in.
        // Stripping 32 leaves 16 bytes of padding glued to the front of the
        // JSON, which must NOT be returned as a payload. (A JSON decoder would
        // have raised here; the file path in §4 would have returned the bytes.)
        $json = $this->factory->credentialsJsonOfLength(32, 'liar');
        $realPadding = 48;
        $padded = chr($realPadding).random_bytes($realPadding - 1).$json;
        self::assertSame(0, strlen($padded) % 16);

        // Re-seal with the FIRST BYTE LYING about the length.
        $liar = chr(32).substr($padded, 1);
        $envelope = $this->factory->sealPadded($liar);

        try {
            $decrypted = PassportDecryptor::decryptCredentials(
                $envelope['data'],
                $envelope['secret'],
                $this->factory->privateKeyPem(),
                $envelope['hash'],
            );
            self::fail('a lying padding length must be refused, not truncated: '.json_encode($decrypted));
        } catch (RuntimeException $e) {
            self::assertSame(PassportDecryptor::DECRYPT_ERROR, $e->getMessage());
        }
    }

    public function test_step4_the_padding_is_removed_not_merely_tolerated(): void
    {
        // The strongest observable form of the fix, and the reason the bug
        // survived: json_decode IGNORES trailing bytes, so a padded payload
        // decoded to the right array even when nothing stripped the padding.
        // The file path (§4) returns raw bytes, so there is nothing to hide
        // behind.
        $file = "\xFF\xD8\xFF\xE0".random_bytes(64)."\xFF\xD9";
        $sealed = $this->factory->sealFileCredentials($file);

        $plain = PassportDecryptor::decryptFileCredentials(
            $sealed['content'],
            $sealed['hash'],
            $sealed['secret'],
            $this->factory->privateKeyPem(),
        );

        self::assertSame($file, $plain);
        self::assertSame(strlen($file), strlen($plain), 'no padding byte survived');
    }

    // ── §4 the nested decryptions ────────────────────────────────────────

    public function test_step4_data_credentials_round_trip_a_personal_details_object(): void
    {
        // §4/§9 — the plaintext of a SecureData.data field is a JSON
        // PersonalDetails, and the nested envelope has the same shape: hash
        // over the AES input, SHA512(secret+hash) key/iv, 32–255 byte padding.
        $personalDetails = (string) json_encode([
            'first_name' => 'Marie',
            'last_name' => 'Curie',
            'middle_name' => 'Skłodowska',
            'birth_date' => '07.11.1867',
            'gender' => 'female',
            'country_code' => 'FR',
            'residence_country_code' => 'FR',
            'first_name_native' => 'Мария',
            'last_name_native' => 'Кюри',
        ], JSON_THROW_ON_ERROR);

        $sealed = $this->factory->sealDataCredentials($personalDetails);

        $plain = PassportDecryptor::decryptDataCredentials(
            $sealed['data'],
            $sealed['hash'],
            $sealed['secret'],
            $this->factory->privateKeyPem(),
        );

        self::assertSame($personalDetails, $plain);
        self::assertSame('Curie', json_decode($plain, true)['last_name']);
    }

    public function test_step4_data_credentials_refuse_a_wrong_data_hash(): void
    {
        $sealed = $this->factory->sealDataCredentials('{"first_name":"Marie"}');
        $wrong = $sealed['hash'];
        $wrong[0] = chr(ord($wrong[0]) ^ 0x01);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(PassportDecryptor::DECRYPT_ERROR);

        PassportDecryptor::decryptDataCredentials(
            $sealed['data'],
            $wrong,
            $sealed['secret'],
            $this->factory->privateKeyPem(),
        );
    }

    public function test_step4_data_credentials_refuse_a_lying_padding_length(): void
    {
        $json = '{"document_no":"123456789","expiry_date":"01.01.2030","country_code":"GB"}';
        $padded = $this->factory->pad($json);
        $secret = random_bytes(32);
        $hash = $this->factory->credentialsHash($padded);
        $liar = chr(32).substr($padded, 1);

        $this->expectException(RuntimeException::class);
        PassportDecryptor::decryptDataCredentials(
            $this->factory->encrypt($liar, $secret, $hash),
            $hash,
            $this->factory->sealSecret($secret),
            $this->factory->privateKeyPem(),
        );
    }

    public function test_step4_file_credentials_refuse_a_wrong_file_hash(): void
    {
        $sealed = $this->factory->sealFileCredentials("\xFF\xD8\xFF\xE0".random_bytes(32));
        $wrong = $sealed['hash'];
        $wrong[31] = chr(ord($wrong[31]) ^ 0x80);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(PassportDecryptor::DECRYPT_ERROR);

        PassportDecryptor::decryptFileCredentials(
            $sealed['content'],
            $wrong,
            $sealed['secret'],
            $this->factory->privateKeyPem(),
        );
    }

    public function test_step4_file_credentials_refuse_a_tampered_download(): void
    {
        $sealed = $this->factory->sealFileCredentials("\xFF\xD8\xFF\xE0".random_bytes(32));
        $tampered = $sealed['content'];
        $tampered[0] = chr(ord($tampered[0]) ^ 0xFF);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(PassportDecryptor::DECRYPT_ERROR);

        PassportDecryptor::decryptFileCredentials(
            $tampered,
            $sealed['hash'],
            $sealed['secret'],
            $this->factory->privateKeyPem(),
        );
    }

    public function test_step4_the_nested_hash_is_over_the_aes_input_not_the_ciphertext(): void
    {
        $json = '{"first_name":"Hans"}';
        $padded = $this->factory->pad($json);
        $secret = random_bytes(32);
        $hash = $this->factory->credentialsHash($padded);
        $data = $this->factory->encrypt($padded, $secret, $hash);

        self::assertSame(hash('sha256', $padded, true), $hash);
        self::assertNotSame(hash('sha256', $data, true), $hash);

        // And a nested envelope handed the CIPHERTEXT hash is refused: the
        // key derives from the hash, so the ciphertext reading is a fixed
        // point — it cannot be built, and an implementation that expected it
        // could never be given a fixture. Asserted as the reason the reading
        // is settled rather than a matter of taste.
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(PassportDecryptor::DECRYPT_ERROR);
        PassportDecryptor::decryptDataCredentials(
            $data,
            hash('sha256', $data, true),
            $this->factory->sealSecret($secret),
            $this->factory->privateKeyPem(),
        );
    }
}
