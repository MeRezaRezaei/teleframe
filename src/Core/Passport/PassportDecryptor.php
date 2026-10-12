<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Passport;

use RuntimeException;

/**
 * PassportDecryptor — Telegram Passport decryption, built against the
 * NORMATIVE upstream text (vendored at
 * `docs/reference/telegram-passport-official.md`, from core.telegram.org),
 * not against anyone's inference.
 *
 * The algorithm, in Telegram's own order (the "Passport Credentials" section
 * of core.telegram.org/passport/encryption, and the identical
 * "Data and File Encryption" section for the nested elements):
 *
 *   "Credentials are padded to a length which is divisible by 16 bytes. To
 *    achieve this, 32 to 255 bytes are added at the beginning, where the
 *    first byte always holds the number of added bytes and the rest are
 *    random."
 *
 *   "A hash of the padded credentials credentials_hash is calculated:
 *    credentials_hash = SHA256( credentials )"
 *
 *   "credentials_secret_hash = SHA512( credentials_secret + credentials_hash )
 *    credentials_key = slice( credentials_secret_hash, 0, 32 )
 *    iv = slice( credentials_secret_hash, 32, 16 )"
 *
 *   "encrypted_credentials = AES256-CBC-ENC(credentials, credentials_key, iv)"
 *   "encrypted_credentials_secret = RSA-ENC(credentials_secret, key,
 *                                            OPENSSL_PKCS1_OAEP_PADDING)"
 *
 * READ THAT ORDER CAREFULLY, because it is the whole dispute. The padding
 * paragraph comes FIRST and the hash is explicitly "a hash of the PADDED
 * CREDENTIALS"; `credentials` is the AES *input*, and the variable that holds
 * the ciphertext is `encrypted_credentials`. The hash is therefore over the
 * padded PLAINTEXT — the AES input — and NOT over the ciphertext.
 *
 * It cannot be over the ciphertext, and this is not a matter of taste: the
 * encryption KEY is derived from that hash, so "hash = SHA256(ciphertext)"
 * makes the envelope a hash fixed point — you would have to find a ciphertext
 * whose own SHA-256 seeds the key that produces it. Such a fixture cannot be
 * CONSTRUCTED, only searched for, which is why every real Telegram client
 * (and Telegram's own reference snippets) hash the padded input. A previous
 * revision of the vendored reference doc in this repo stated the ciphertext
 * reading; that is a misreading of the quote above and the doc now says so.
 * See `docs/reference/telegram-passport-official.md` §0.
 *
 * So the three things this class actually gets right, all load-bearing:
 *
 *   §3.1  `secret` decrypted with RSA-**OAEP** — the only padding mode. A
 *          PKCS#1 v1.5 fallback would let a caller distinguish padding modes
 *          (a bleed oracle) and downgrade probes to a weaker pad.
 *   §3.2  key = SHA512(secret + hash)[0..32], iv = [32..48]; concatenation
 *          first, RAW (non-hex) digest.
 *   §3.3  the hash is verified over the AES INPUT with `hash_equals`, and
 *          BEFORE the padding is removed — so the padding bytes are covered by
 *          the integrity check, not stripped out from under it.
 *   §3.4  the 32–255 byte padding is STRIPPED, first byte = the number of
 *          added bytes INCLUDING ITSELF, so the payload is
 *          `substr($padded, ord($padded[0]))`. Handing the padded text
 *          straight to `json_decode` "works", because a JSON decoder ignores
 *          trailing bytes — which is exactly why the omission survived: it
 *          was invisible, and would have stayed invisible to any test whose
 *          fixture had no padding.
 *   §3    AES-256-CBC with **no implicit padding** (OPENSSL_ZERO_PADDING).
 *          The payload is already a multiple of 16 by construction, so plain
 *          OPENSSL_RAW_DATA makes OpenSSL validate-and-strip a PKCS#7 block
 *          that was never written, and fail outright on the random tail. On
 *          real Telegram ciphertext that rejects every login.
 *
 * THE NESTED DECRYPTIONS (§4) are the same shape, twice more — same padded-
 * input hash, same padding, same single error — so they live here too:
 * `decryptDataCredentials()` over the `SecureData.data` field, and
 * `decryptFileCredentials()` over the DOWNLOADED file bytes. Both take RAW
 * bytes (§1: MTProto fields are raw binary, not base64).
 *
 * WHAT IS NOT HERE, and must not be read into those two signatures: the
 * `getFile` download, the `SecureValue` traversal that finds front_side /
 * reverse_side / selfie / translation / files, and the JPG/size validation.
 * A caller does those and passes the bytes in. This is the missing
 * cryptographic primitive, NOT a claim that the document surface works.
 *
 * ONE ERROR, ALWAYS (padding-oracle hardening). {@see self::DECRYPT_ERROR}
 * is the only message any refusal produces: bad base64, a bad hash, an
 * RSA-OAEP failure, an AES failure, a padding byte outside 32..255, or a
 * stripped result that is not JSON. Neither the text nor the failing STEP
 * distinguishes them, so an attacker probing the endpoint cannot use the
 * error as an oracle. Nothing here ever logs a plaintext, a nonce, a field
 * value or key material.
 *
 * Zero regex (this file lives in src/Core): ord(), substr(), strlen().
 * Plain-PHP constructible: static methods, no container, no state.
 */
class PassportDecryptor
{
    /**
     * THE single failure message for every decryption failure path.
     */
    public const DECRYPT_ERROR = 'Failed to decrypt Passport credentials.';

    /** Padding length floor, per upstream, INCLUDING the length byte. */
    public const PADDING_MIN = 32;

    /** Padding length ceiling, per upstream, INCLUDING the length byte. */
    public const PADDING_MAX = 255;

    /**
     * Decrypts the `Credentials` envelope.
     *
     * @param  string  $encryptedData  base64 of the AES-CBC `data` field
     * @param  string  $encryptedSecret  base64 of `secret`, RSA-OAEP-sealed to the service key
     * @param  string  $privateKeyPem  the service's RSA private key, PEM
     * @param  string  $hash  base64 of SHA-256 over the PADDED credentials
     * @return array<string, mixed> the decrypted `Credentials` JSON ({secure_data, nonce})
     *
     * @throws RuntimeException with {@see self::DECRYPT_ERROR} on every refusal
     */
    public static function decryptCredentials(
        string $encryptedData,
        string $encryptedSecret,
        string $privateKeyPem,
        string $hash
    ): array {
        $rawEncryptedData = base64_decode($encryptedData, true);
        $rawEncryptedSecret = base64_decode($encryptedSecret, true);

        if ($rawEncryptedData === false || $rawEncryptedSecret === false) {
            throw new RuntimeException('Invalid base64 passport payload.');
        }

        $plaintext = self::decryptAesPayload(
            $rawEncryptedData,
            self::decodeBase64Hash($hash),
            $rawEncryptedSecret,
            $privateKeyPem
        );

        // The plaintext is the `Credentials` JSON. Anything else is a
        // refusal, not an empty payload: returning [] would hand the caller a
        // "success" whose only symptom is a missing nonce further up — a
        // distinguishable outcome, which is both wrong and a bad oracle.
        $credentials = json_decode($plaintext, true);
        if (! is_array($credentials)) {
            throw new RuntimeException(self::DECRYPT_ERROR);
        }

        return $credentials;
    }

    /**
     * §4 — `DataCredentials { data_hash, secret }`, the `SecureData.data`
     * field. RAW bytes both ways (§1: MTProto fields are not base64).
     *
     * Returns the decrypted JSON string, undecoded: the plaintext is one of
     * `PersonalDetails`, `IdDocumentData` or `ResidentialAddress` (§4/§9),
     * and this class has no business deciding which.
     *
     * @throws RuntimeException with {@see self::DECRYPT_ERROR} on every refusal
     */
    public static function decryptDataCredentials(
        string $ciphertext,
        string $dataHash,
        string $encryptedSecret,
        string $privateKeyPem
    ): string {
        return self::decryptAesPayload(
            $ciphertext,
            $dataHash,
            $encryptedSecret,
            $privateKeyPem
        );
    }

    /**
     * §4 — `FileCredentials { file_hash, secret }`, over the DOWNLOADED
     * ciphertext bytes. Returns the decrypted file bytes.
     *
     * NOT IMPLEMENTED AROUND THIS: the `getFile` download, the `SecureFile`
     * traversal, and the JPG / 10 MB validation — see the class docblock.
     *
     * @throws RuntimeException with {@see self::DECRYPT_ERROR} on every refusal
     */
    public static function decryptFileCredentials(
        string $fileContent,
        string $fileHash,
        string $encryptedSecret,
        string $privateKeyPem
    ): string {
        return self::decryptAesPayload(
            $fileContent,
            $fileHash,
            $encryptedSecret,
            $privateKeyPem
        );
    }

    /**
     * The shared body — the spec defines the envelope and both nested
     * decryptions identically, so they are one function:
     *
     *   1. the hash is 32 raw bytes; the base64 decode (envelope only)
     *      already happened in the caller, and a `false` from a strict decode
     *      must never be allowed to coerce to "" and derail the KDF.
     *   2. `secret` via RSA-**OAEP** only.
     *   3. key/iv = SHA512(secret + hash)[0..32] / [32..48] — concatenated
     *      input, raw (non-hex) digest.
     *   4. AES-256-CBC, RAW, no implicit block padding.
     *   5. the hash is verified over that AES INPUT, constant-time, with the
     *      padding still attached.
     *   6. the padding is removed, strictly.
     *
     * @param  string  $rawHash  the 32 RAW hash bytes — base64 already decoded
     *                           by the caller, per the encoding split above
     *
     * @throws RuntimeException with {@see self::DECRYPT_ERROR} on every refusal
     */
    private static function decryptAesPayload(
        string $ciphertext,
        string $rawHash,
        string $encryptedSecret,
        string $privateKeyPem
    ): string {
        if (strlen($rawHash) !== 32) {
            throw new RuntimeException(self::DECRYPT_ERROR);
        }

        $secret = '';
        if (! openssl_private_decrypt(
            $encryptedSecret,
            $secret,
            $privateKeyPem,
            OPENSSL_PKCS1_OAEP_PADDING
        ) || $secret === '') {
            throw new RuntimeException(self::DECRYPT_ERROR);
        }

        $secretHash = hash('sha512', $secret.$rawHash, true);
        $aesKey = substr($secretHash, 0, 32);
        $aesIv = substr($secretHash, 32, 16);

        // OPENSSL_ZERO_PADDING is load-bearing, not tidiness — see the class
        // docblock. Telegram pads the payload itself to a multiple of 16 and
        // writes no PKCS#7 block at all.
        $padded = openssl_decrypt(
            $ciphertext,
            'aes-256-cbc',
            $aesKey,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            $aesIv
        );

        if ($padded === false) {
            throw new RuntimeException(self::DECRYPT_ERROR);
        }

        // §3.3 — over the AES INPUT, i.e. the padded credentials, exactly as
        // upstream words it. Checked BEFORE the padding is stripped so the
        // padding bytes are inside the integrity check rather than removed
        // from under it. hash_equals, never ===.
        if (! hash_equals(hash('sha256', $padded, true), $rawHash)) {
            throw new RuntimeException(self::DECRYPT_ERROR);
        }

        return self::stripPadding($padded);
    }

    /**
     * The Bot API envelope's `hash` is base64; the MTProto nested fields'
     * hashes are raw. The base64 decode happens ONCE, here, on the envelope
     * path only, and everything downstream takes raw bytes — so the shared
     * body never has to guess which encoding it was handed, and cannot accept
     * a raw 32-byte field by accident.
     *
     * A `false` from the strict decode must not be allowed to coerce to ""
     * and derail the KDF, hence the length check.
     *
     * @throws RuntimeException with {@see self::DECRYPT_ERROR}
     */
    private static function decodeBase64Hash(string $hash): string
    {
        $rawHash = base64_decode($hash, true);

        if ($rawHash === false || strlen($rawHash) !== 32) {
            throw new RuntimeException(self::DECRYPT_ERROR);
        }

        return $rawHash;
    }

    /**
     * §3.4 — remove the padding, strictly.
     *
     * "32 to 255 bytes are added at the beginning, where the first byte
     *  always holds the number of added bytes and the rest are random."
     *
     * The first byte IS the total padding length, so the payload is
     * everything from that offset on, and the value is only credible inside
     * 32..255. Every way this can be wrong is a REFUSAL, never a silent
     * truncation:
     *   - a first byte outside 32..255 is not a Telegram padding length. This
     *     is the check a naive `substr($p, ord($p[0]))` skips, and skipping
     *     it mangles a bogus 1-byte "padding" into a payload one byte short
     *     while looking like it worked;
     *   - a length longer than the payload, or a payload that is not a whole
     *     number of AES blocks, means these bytes are not what they claim;
     *   - the remainder must be non-empty: the padding exists to make the
     *     length divisible, so an empty remainder is a malformed envelope,
     *     not a valid empty payload.
     *
     * Zero regex: ord() + substr().
     *
     * @throws RuntimeException with {@see self::DECRYPT_ERROR} on every refusal
     */
    private static function stripPadding(string $padded): string
    {
        $length = strlen($padded);

        if ($length === 0 || $length % 16 !== 0) {
            throw new RuntimeException(self::DECRYPT_ERROR);
        }

        $paddingLength = ord($padded[0]);

        // The 32..255 window, lower half: the ceiling is STRUCTURAL — the
        // length lives in one byte, so a value above 255 is not
        // representable and there is nothing to check. The floor is the check
        // that matters, and it is the one a naive
        // `substr($padded, ord($padded[0]))` skips.
        if ($paddingLength < self::PADDING_MIN) {
            throw new RuntimeException(self::DECRYPT_ERROR);
        }

        if ($paddingLength > $length) {
            throw new RuntimeException(self::DECRYPT_ERROR);
        }

        $payload = substr($padded, $paddingLength);

        if ($payload === '') {
            throw new RuntimeException(self::DECRYPT_ERROR);
        }

        return $payload;
    }
}
