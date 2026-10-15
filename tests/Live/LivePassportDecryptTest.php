<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Live;

use MeRezaRezaei\Teleframe\Core\Passport\PassportDecryptor;
use MeRezaRezaei\Teleframe\Passport\EncryptedCredentials;
use MeRezaRezaei\Teleframe\Passport\PassportCredentialsVerifier;
use MeRezaRezaei\Teleframe\Passport\PassportKeyLoader;
use MeRezaRezaei\Teleframe\Tests\Concerns\RunsLiveTelegram;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * A REAL `message.passport_data.credentials` THROUGH THE REAL DECRYPTOR.
 *
 * ## WHAT IS ALREADY PROVEN, AND WHY IT IS NOT ENOUGH
 *
 * `tests/Passport/PassportDecryptorE2eTest.php` is a genuine end-to-end test of
 * the decryptor: 20+ assertions, a real AES-256-CBC + RSA-OAEP round trip, every
 * refusal path pinned. It is also, entirely, **synthetic**. `SpecCredentialFactory`
 * mints a fresh RSA keypair per run (`tests/Passport/SpecCredentialFactory.php:55-77`)
 * and seals material this repository produced itself.
 *
 * There is no captured Telegram payload anywhere in `tests/` — every credential
 * literal is a placeholder such as `'d'`, `'h'`, `'the-data'`
 * (`tests/Passport/EncryptedCredentialsTest.php:31`). So the honest summary is:
 * **the algorithm is proven against a faithful transcription of the spec, on
 * bytes this package invented. That it accepts the bytes Telegram actually
 * emits is unproven.** A spec transcription that got one padding detail wrong
 * would pass every offline test in the repository and fail a real user.
 *
 * ## WHY THE CAPTURE CANNOT BE ACQUIRED BY A TEST
 *
 * There is no method to pull passport credentials. The catalogue contains
 * `help.getPassportConfig` (a country/language profile, no user data) and the Bot
 * API's `setPassportDataErrors` (a writer, not a retriever) — nothing else. The
 * flow is user-initiated by construction: the host sends `{nonce, hash}` to
 * Telegram, the human approves in the app, and only then does Telegram push
 * `message.passport_data` inbound. There is no server-side pull to automate.
 *
 * So this step is a REPLAY of captured bytes, and the capture is the operator's.
 * That is stated here rather than worked around, because a test that implied it
 * could fetch a credential itself would be advertising a capability the package
 * does not have.
 *
 * ## WHAT IS AND IS NOT BEING TESTED
 *
 * The envelope decryptor (`decryptCredentials`) is what Telegram's
 * `EncryptedPassportElement` feeds, and that is the path tested here. The nested
 * §4 routines (`decryptDataCredentials` / `decryptFileCredentials`) take RAW
 * bytes inside a `values:Vector<SecureValue>` and are deliberately never invoked
 * by anything in `src/` — `SecureValueTraversal` only COUNTS them. Testing them
 * would need a different capture, and the claim is not extended to cover them.
 *
 * ## THE NONCE IS ALREADY SPENT — AND THAT IS FINE HERE
 *
 * The blob carries the nonce that was issued for the capture, and
 * `PassportLoginService::login()` step (f) consumes it
 * (`src/Teleframe/Passport/PassportLoginService.php:227-231`), so replaying
 * through the full login chain would be refused as "unknown or expired passport
 * nonce" — correctly. This step stops at the decryptor, which is what the
 * operator asked to have proven, and deliberately does not claim to prove the
 * login chain on real bytes.
 */
final class LivePassportDecryptTest extends TestCase
{
    use RunsLiveTelegram;

    /**
     * THE REAL BYTES, THROUGH THE REAL DECRYPTOR.
     *
     * The order matters and is the reverse of the JSON field order:
     * `decryptCredentials(data, secret, pem, hash)`
     * (`src/Core/Passport/PassportDecryptor.php:121-126`). Handing it the hash
     * where the secret belongs fails as a generic decrypt error, which is the
     * least diagnosable possible failure — hence the pinned call.
     */
    #[Test]
    public function a_real_credentials_envelope_decrypts_through_the_real_decryptor(): void
    {
        $this->requireLiveStep('passport_decrypt');

        $pem = $this->livePassportPrivateKeyPem();

        // Built through the shipped intake, not by indexing an array by hand:
        // this also proves EncryptedCredentials recognises the real shape.
        $credentials = EncryptedCredentials::fromArray([
            'data' => self::liveEnvRequired(
                'TELEFRAME_LIVE_PASSPORT_DATA',
                'the `data` field of a real message.passport_data.credentials, base64, captured verbatim',
            ),
            'secret' => self::liveEnvRequired(
                'TELEFRAME_LIVE_PASSPORT_SECRET',
                'the `secret` field of the same real credentials, base64',
            ),
            'hash' => self::liveEnvRequired(
                'TELEFRAME_LIVE_PASSPORT_HASH',
                'the `hash` field of the same real credentials, base64',
            ),
        ]);

        $decrypted = PassportDecryptor::decryptCredentials(
            $credentials->data,
            $credentials->secret,
            $pem,
            $credentials->hash,
        );

        self::assertIsArray($decrypted, 'the decryptor must return the decrypted credential JSON');
        self::assertArrayHasKey(
            'nonce',
            $decrypted,
            'Telegram\'s credential JSON always carries the nonce it was issued against; its absence '
            .'means the plaintext was not decrypted, whatever the decryptor claimed to return',
        );
        self::assertNotSame('', (string) $decrypted['nonce'], 'the nonce must not be empty');

        self::assertArrayHasKey(
            'secure_data',
            $decrypted,
            'a credential carries the encrypted values the user submitted',
        );
        self::assertIsArray($decrypted['secure_data']);

        self::liveAppendReport(
            'passport.decryptCredentials (real captured bytes)',
            sprintf(
                'A real Telegram `message.passport_data.credentials` was decrypted by the shipped '
                .'decryptor. Proven: the envelope decrypts, the SHA-256 over the PADDED plaintext '
                .'matched (`hash_equals`, so the integrity check passed, not merely the AES), and the '
                ."plaintext carried the expected `nonce` and `secure_data` keys.\n\n"
                ."secure_data entries: %d. Values are NOT decrypted or recorded.\n\n"
                .'NOT proven by this step: the nested §4 routines '
                .'(`decryptDataCredentials` / `decryptFileCredentials`), which take raw bytes inside '
                .'a `values:Vector<SecureValue>` and which nothing in `src/` invokes; the nonce '
                .'replay guard in `PassportLoginService`; and `help.getFile` document download, which '
                .'has no implementation at all.',
                count($decrypted['secure_data']),
            ),
        );
    }

    /**
     * THE SAME BYTES THROUGH THE INDEPENDENT VERIFIER.
     *
     * `PassportCredentialsVerifier::assertCredentialsEnvelope()`
     * (`src/Teleframe/Passport/PassportCredentialsVerifier.php:171-185`) is a
     * second implementation of the same envelope check that re-derives the
     * padded plaintext and compares the digest without decoding the JSON. If the
     * two disagree on real bytes, the disagreement is real — which is exactly
     * what a synthetic fixture cannot show.
     */
    #[Test]
    public function the_independent_verifier_agrees_with_the_decryptor_on_real_bytes(): void
    {
        $this->requireLiveStep('passport_decrypt');

        $pem = $this->livePassportPrivateKeyPem();

        $credentials = EncryptedCredentials::fromArray([
            'data' => self::liveEnvRequired(
                'TELEFRAME_LIVE_PASSPORT_DATA',
                'the `data` field of a real message.passport_data.credentials, base64, captured verbatim',
            ),
            'secret' => self::liveEnvRequired(
                'TELEFRAME_LIVE_PASSPORT_SECRET',
                'the `secret` field of the same real credentials, base64',
            ),
            'hash' => self::liveEnvRequired(
                'TELEFRAME_LIVE_PASSPORT_HASH',
                'the `hash` field of the same real credentials, base64',
            ),
        ]);

        // No assertion on success beyond "it did not throw": the throw IS the
        // result. A live envelope that this refuses is a real finding, and the
        // message names the step without quoting a byte of the payload.
        PassportCredentialsVerifier::assertCredentialsEnvelope($credentials, $pem);

        self::assertTrue(true, 'the verifier accepted the real envelope, independently of the decryptor');
    }

    /**
     * A SINGLE-BIT TAMPER IS REFUSED ON REAL BYTES TOO.
     *
     * The offline suite proves this on synthetic material. Doing it once on a
     * real capture proves the refusal is not an artefact of how the fixture was
     * built — and it is the cheapest possible use of a real credential, since
     * nothing is decrypted successfully and no value is disclosed.
     */
    #[Test]
    public function a_tampered_real_envelope_is_refused_rather_than_partly_decrypted(): void
    {
        $this->requireLiveStep('passport_decrypt');

        $pem = $this->livePassportPrivateKeyPem();

        $credentials = EncryptedCredentials::fromArray([
            'data' => self::liveEnvRequired(
                'TELEFRAME_LIVE_PASSPORT_DATA',
                'the `data` field of a real message.passport_data.credentials, base64, captured verbatim',
            ),
            'secret' => self::liveEnvRequired(
                'TELEFRAME_LIVE_PASSPORT_SECRET',
                'the `secret` field of the same real credentials, base64',
            ),
            'hash' => self::liveEnvRequired(
                'TELEFRAME_LIVE_PASSPORT_HASH',
                'the `hash` field of the same real credentials, base64',
            ),
        ]);

        // Flip one bit of the LAST byte of the ciphertext. Base64 is re-encoded
        // so the intake still accepts the shape and the tamper reaches the AES
        // rather than being refused by the base64 decoder.
        $raw = base64_decode($credentials->data, true);
        self::assertIsString($raw, 'the captured data must be valid base64 before it can be tampered with');
        self::assertNotSame('', $raw, 'the captured data must not be empty');
        $raw[strlen($raw) - 1] = chr(ord($raw[strlen($raw) - 1]) ^ 0x01);

        $tampered = EncryptedCredentials::fromArray([
            'data' => base64_encode($raw),
            'secret' => $credentials->secret,
            'hash' => $credentials->hash,
        ]);

        $thrown = null;
        try {
            PassportDecryptor::decryptCredentials(
                $tampered->data,
                $tampered->secret,
                $pem,
                $tampered->hash,
            );
        } catch (RuntimeException $e) {
            $thrown = $e;
        }

        self::assertInstanceOf(
            RuntimeException::class,
            $thrown,
            'a one-bit change to a real envelope must be refused, never decrypted to something plausible',
        );
        self::assertStringNotContainsString(
            $credentials->secret,
            $thrown->getMessage(),
            'the refusal must not echo any part of the credential back',
        );
    }

    /**
     * THE KEY MATERIAL IS READ THROUGH THE SHIPPED LOADER, NOT BYPASSED.
     *
     * `decryptCredentials()` takes a PEM string, so a test could `file_get_contents`
     * it and skip `PassportKeyLoader` entirely. That would be cheaper and would
     * prove less: the loader's owner-only permission check
     * (`src/Teleframe/Passport/PassportKeyLoader.php:83-88`) is a real control,
     * and a host that misconfigures `chmod` should hear about it here. So the
     * loader is used, and its refusal is allowed to be the failure.
     */
    private function livePassportPrivateKeyPem(): string
    {
        $path = self::liveEnvRequired(
            'TELEFRAME_PASSPORT_PRIVATE_KEY',
            'the path to the passport RSA private key PEM. Reuses the package\'s own config variable, '
            .'and the file must be owner-only (chmod 600) or PassportKeyLoader refuses it.',
        );

        return (new PassportKeyLoader($path))->key();
    }
}
