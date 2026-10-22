<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

use RuntimeException;

/**
 * PassportCredentialsVerifier — THE TRANSPORT-INTEGRITY CHECK, in one place,
 * for all three envelope levels Telegram Passport defines.
 *
 * ══ WHAT IS BEING CHECKED, AND WHY IT IS COMPUTABLE ═════════════════════
 *
 * core.telegram.org/passport states, in the operator's own imperative:
 *
 *   > "make sure that the credentials hash is equal to
 *    >  SHA256( credentials_data )"
 *   > "all hashes represent as raw binary data, not hexits"
 *
 * That gives exactly three bot-computable checks, and all three are
 * byte-exact and deterministic:
 *
 *   1. `EncryptedCredentials.hash`          == SHA256(the AES input)
 *   2. `DataCredentials.data_hash`          == SHA256(the AES input of `SecureData.data`)
 *   3. `FileCredentials.file_hash`          == SHA256(the AES input of the downloaded bytes)
 *
 * `credentials` / `data` / `file` in those sentences is the PADDED PLAINTEXT,
 * not the ciphertext: the AES key is derived from that hash, so a
 * "hash = SHA256(ciphertext)" reading asks for a fixed point — a ciphertext
 * whose own digest seeds the key that produces it — which is not a thing any
 * client can compute. The same reasoning fixes the ORDER here: pad → hash →
 * derive key/iv → encrypt. `PassportDecryptor` says the same thing at
 * `src/Core/Passport/PassportDecryptor.php` §3.3 and enforces it at line 263.
 *
 * ══ WHAT IS DELIBERATELY NOT CHECKED, AND WHY ═════════════════════════
 *
 * `EncryptedPassportElement.hash` is NOT verified, and this class is the place
 * that says so, because "the per-element hash is never verified" was recorded
 * in this repo as an unfixable Critical and that reading was wrong. It is
 * carried by Telegram and derived by nobody:
 *
 *   - `telegram-bot-api/Client.cpp:3680` — the ONLY line in Telegram's own Bot
 *     API server that produces the field — is
 *     `object("hash", td::base64_encode(element_->hash_));`. A base64
 *     PASSTHROUGH. It hashes nothing.
 *   - its only consumer, `Client::get_passport_element_hash()`
 *     (`Client.cpp:11754-11759`), base64-decodes it and echoes it back so
 *     Telegram can name the element in a `PassportElementErrorUnspecified`.
 *     That is the server's entire "understanding" of the field: no HMAC, no
 *     SHA-256, no concatenation.
 *   - the origin is the MTProto `SecureValue.hash` "Data hash", copied
 *     verbatim out of Telegram's datacenter payload by tdlib
 *     (`td/telegram/SecureValue.cpp:584-593`).
 *
 * `data_check_key` — the `bot_id + type + value + bot_token` construction the
 * old note assumed — returns ZERO hits across GitHub; the ~750 `data_check`
 * hits that do exist are the unrelated Mini-App initData / Login Widget
 * feature. Four independent sources agree the hash is CARRIED, never DERIVED.
 * `botapi-spec.json:28941` ("Base64-encoded element hash for using in
 * PassportElementErrorUnspecified") is therefore CORRECT, not deficient.
 *
 * ══ WHAT THIS IS NOT: IT IS NOT IDENTITY AUTHENTICATION ═════════════════
 *
 * This is a TRANSPORT-INTEGRITY check. It authenticates the BLOB — that the
 * ciphertext Telegram's datacenter sent is the ciphertext Telegram's
 * datacenter vouched for, unaltered in flight. It authenticates NOTHING about
 * the IDENTITY CLAIM the blob carries. "The email inside is really Ada's" is
 * Telegram's claim, not this class's, and no amount of SHA-256 over the
 * ciphertext makes it so. Only Telegram can attest to the identity, via the
 * `updateBotPassportData` sender edge and the one-time-nonce binding this
 * module already carries. Do not read a green result from this class as "the
 * Critical is fixed".
 *
 * ══ THE RAW-BINARY TRAP, AND WHY IT IS CLOSED EXPLICITLY ════════════════
 *
 * Every hash Telegram sends is RAW BINARY, 32 bytes, never hexits and never a
 * base64 STRING. Getting this wrong does not weaken the check — it inverts it
 * into a denial of service, which is worse in practice and invisible in
 * review:
 *
 *   - `hash_equals(hex_sha256($data), $rawHash)`  — 64 chars vs 32 bytes:
 *     `hash_equals` returns false on a length mismatch IMMEDIATELY, so every
 *     genuine login is refused. A check that is always false is not a check.
 *   - `hash_equals(base64_sha256($data), $rawHash)` — 44 chars vs 32 bytes:
 *     the same immediate false, by the same mechanism.
 *   - `hash_equals(hash('sha256', $data, true), $hashB64)` — comparing a raw
 *     digest against the ENCODED STRING it arrived as. Also always false.
 *
 * So every comparison below is made against 32 RAW bytes that were decoded (or
 * retained raw) BEFORE the call, and `assertRawDigestLength()` refuses
 * anything that is not exactly 32 bytes — which is what rejects a hex digest
 * and a base64 string at the boundary instead of deep inside `hash_equals`,
 * where the failure would be indistinguishable from a genuine tamper.
 *
 * ══ THE ENCODING SPLIT, WHICH IS NOT INTERCHANGEABLE ════════════════════
 *
 *   - the ENVELOPE `hash` arrives BASE64 (Bot API `EncryptedCredentials`),
 *     so it is decoded here, once, strictly;
 *   - the NESTED `data_hash` / `file_hash` arrive RAW (§1: MTProto fields are
 *     raw binary, not base64), so they are NOT decoded — decoding them a
 *     second time would corrupt a correct digest into a wrong one, which is
 *     the same always-false failure wearing a different hat.
 *
 * The two are different doors and get different treatment. `assertData*` /
 * `assertFile*` therefore take RAW digests and this class never guesses which
 * encoding it was handed.
 *
 * ══ ONE ERROR, ALWAYS ═══════════════════════════════════════════════════
 *
 * {@see self::INTEGRITY_ERROR} is the only message any refusal produces, for
 * the same reason `PassportDecryptor::DECRYPT_ERROR` is: neither the text nor
 * the failing STEP may distinguish itself, or the error becomes an oracle that
 * tells a prober whether the RSA leg succeeded, whether the base64 was well
 * formed, whether the AES leg ran, or whether only the digest differed. A
 * `RuntimeException` — deliberately the same class the decryptor throws, not a
 * `PassportLoginException` — because a transport failure is not a policy
 * refusal: both login chains already wrap it into `PassportLoginException`
 * with the original cause preserved, exactly as they do for the decryptor.
 *
 * SCOPE, precisely: this class verifies DIGESTS. It does not strip Telegram's
 * 32..255 padding (the decryptor owns that), does not decrypt documents, does
 * not read an identity out of anything, and never logs a plaintext, a digest,
 * a nonce or key material. Each `assert*` method answers one question —
 * "did the bytes this envelope decrypt to hash to the digest it arrived
 * with?" — and returns nothing.
 *
 * Zero regex (this file lives in src/Teleframe): strlen(), substr(), hash().
 * Stateless, no container, no config — plain-PHP constructible (the
 * standalone-smoke gate), like `SessionFingerprint` beside it.
 */
final class PassportCredentialsVerifier
{
    /**
     * THE single failure message for every transport-integrity refusal. Never
     * varies with the failing step — see the class docblock.
     */
    public const INTEGRITY_ERROR = 'Passport credentials failed the transport integrity check.';

    /** SHA-256, raw: exactly 32 bytes. Never 64 hexits, never a 44-char base64 string. */
    private const DIGEST_BYTES = 32;

    private function __construct() {}

    /**
     * CHECK 1 — the `EncryptedCredentials` envelope {data, hash, secret}.
     *
     * The three fields are the Bot API / MTProto `SecureCredentialsEncrypted`
     * trio: `data` and `secret` base64 (RSA-OAEP-sealed), `hash` base64 over
     * the padded plaintext. All three are decoded strictly before anything is
     * compared, and the decoded digest is used BOTH as the KDF input and as
     * the comparison operand — deriving the AES key from one encoding of the
     * hash and comparing against another is a check that can never pass.
     *
     * This is a RE-VERIFICATION at the consumer boundary, not the only line
     * between a tampered blob and `json_decode`: the shipped
     * `PassportDecryptor::decryptAesPayload()` performs the same
     * `hash_equals` at `src/Core/Passport/PassportDecryptor.php:263` and
     * throws on a mismatch, so a caller who reaches this method and then
     * calls the decryptor has two independent assertions, not one. What this
     * class adds is that the login chain states the contract by NAME at the
     * point the envelope is accepted, instead of inheriting it implicitly from
     * a property of a call three lines away — and it is the anchor the two
     * nested checks below hang off, neither of which any production code path
     * calls today. (The DECRYPTOR is reached, and has been all along:
     * `PassportDataEnvelope::decryptWith()` calls
     * `PassportDecryptor::decryptDataCredentials()` at
     * src/Teleframe/Passport/PassportDataEnvelope.php:103, which
     * `PassportDocumentResolver.php:191,330` reach. An earlier version of this
     * comment claimed "nothing in `src/` ever invokes
     * `decryptDataCredentials()` or `decryptFileCredentials()`", which was
     * false, and would have licensed deleting a live decrypt path as dead
     * code. What is genuinely never exercised is the NESTED per-field
     * verification: `SecureValueTraversal` only COUNTS the nested
     * `SecureData`/files and never decrypts them, so the two checks below stay
     * unproven by anything but their own tests.)
     *
     * @throws RuntimeException with {@see self::INTEGRITY_ERROR} on every refusal
     */
    public static function assertCredentialsEnvelope(
        EncryptedCredentials $credentials,
        string $privateKeyPem,
    ): void {
        $rawHash = self::decodeBase64($credentials->hash);
        self::assertRawDigestLength($rawHash);

        $padded = self::recoverPaddedPlaintext(
            self::decodeBase64($credentials->data),
            $rawHash,
            self::decodeBase64($credentials->secret),
            $privateKeyPem,
        );

        self::assertRawDigest($padded, $rawHash);
    }

    /**
     * CHECK 2 — §4 `DataCredentials { data_hash, secret }` over a
     * `SecureData.data` field.
     *
     * EVERY ARGUMENT IS RAW (§1: MTProto fields are raw binary, not base64),
     * and none of them is decoded here. `assertRawDigestLength()` is what
     * refuses a hex digest or a base64 string, so the trap fails at the
     * boundary with this one message instead of inside `hash_equals` where it
     * would be indistinguishable from a tamper.
     *
     * The parameter order is `PassportDecryptor::decryptDataCredentials()`'s,
     * deliberately: the two are the same envelope at the same level, and a
     * reader must be able to map one signature onto the other without
     * re-deriving it.
     *
     * @param  string  $ciphertext  the raw `SecureData.data` bytes
     * @param  string  $rawDataHash  the raw 32-byte `data_hash`
     * @param  string  $rawSecret  the raw RSA-OAEP-sealed secret
     *
     * @throws RuntimeException with {@see self::INTEGRITY_ERROR} on every refusal
     */
    public static function assertDataCredentials(
        string $ciphertext,
        string $rawDataHash,
        string $rawSecret,
        string $privateKeyPem,
    ): void {
        self::assertRawDigestLength($rawDataHash);

        $padded = self::recoverPaddedPlaintext($ciphertext, $rawDataHash, $rawSecret, $privateKeyPem);

        self::assertRawDigest($padded, $rawDataHash);
    }

    /**
     * CHECK 3 — §4 `FileCredentials { file_hash, secret }` over the DOWNLOADED
     * ciphertext bytes. Identical to {@see self::assertDataCredentials()} at a
     * different level of the same envelope, and identical in every argument's
     * encoding, which is the point: `file_hash` and `data_hash` are both raw
     * and are treated the same way.
     *
     * The `getFile` download itself is NOT here — a caller downloads and
     * passes the bytes in, exactly as `PassportDecryptor` documents.
     *
     * @param  string  $ciphertext  the raw downloaded bytes
     * @param  string  $rawFileHash  the raw 32-byte `file_hash`
     * @param  string  $rawSecret  the raw RSA-OAEP-sealed secret
     *
     * @throws RuntimeException with {@see self::INTEGRITY_ERROR} on every refusal
     */
    public static function assertFileCredentials(
        string $ciphertext,
        string $rawFileHash,
        string $rawSecret,
        string $privateKeyPem,
    ): void {
        self::assertRawDigestLength($rawFileHash);

        $padded = self::recoverPaddedPlaintext($ciphertext, $rawFileHash, $rawSecret, $privateKeyPem);

        self::assertRawDigest($padded, $rawFileHash);
    }

    /**
     * THE COMPARISON. Raw digest against raw digest, constant-time, `hash_equals`
     * and never `===`.
     *
     * Both operands are 32 bytes by the time they arrive: `$payload` because
     * SHA-256 is 32 bytes, `$rawDigest` because `assertRawDigestLength()`
     * already refused anything else. That is what makes `hash_equals` a real
     * comparison here rather than an immediate length-mismatch false — the
     * failure mode that makes a wrong-encoding check refuse every genuine
     * login while still reading like a check.
     *
     * @throws RuntimeException with {@see self::INTEGRITY_ERROR}
     */
    private static function assertRawDigest(string $payload, string $rawDigest): void
    {
        if (! hash_equals(hash('sha256', $payload, true), $rawDigest)) {
            throw new RuntimeException(self::INTEGRITY_ERROR);
        }
    }

    /**
     * THE 32-BYTE GATE — the raw-binary trap, closed.
     *
     * A hex digest is 64 characters and a base64 digest is 44; neither is 32
     * bytes. Refusing both HERE, with this one message, is what stops the
     * "compare the encoded string to the raw bytes" mistake from reaching
     * `hash_equals` — where it would fail identically on a real tamper and on a
     * correct payload, and be impossible to tell apart from the outside.
     *
     * @throws RuntimeException with {@see self::INTEGRITY_ERROR}
     */
    private static function assertRawDigestLength(string $digest): void
    {
        if (strlen($digest) !== self::DIGEST_BYTES) {
            throw new RuntimeException(self::INTEGRITY_ERROR);
        }
    }

    /**
     * Strict base64 on the ENVELOPE path only — never on the nested checks,
     * whose fields are already raw (§1). A non-strict decode would silently
     * drop invalid characters and produce a 32-byte "digest" that is simply
     * the wrong bytes, so strictness is the whole point: `false` is refused
     * rather than coerced to "".
     *
     * @throws RuntimeException with {@see self::INTEGRITY_ERROR}
     */
    private static function decodeBase64(string $value): string
    {
        $decoded = base64_decode($value, true);

        if ($decoded === false || $decoded === '') {
            throw new RuntimeException(self::INTEGRITY_ERROR);
        }

        return $decoded;
    }

    /**
     * Re-derive the AES key/iv from the sealed secret and the RAW digest and
     * recover the padded plaintext, so the caller can hash it and compare.
     *
     * §3.2 — key = SHA512(secret + hash)[0..32], iv = [32..48], concatenated
     * input, RAW (non-hex) digest. §3.1 — RSA-**OAEP** only; a PKCS#1 v1.5
     * fallback would let a caller distinguish padding modes. §3 — AES-256-CBC
     * with NO implicit padding (`OPENSSL_ZERO_PADDING`), because Telegram pads
     * to a multiple of 16 itself and writes no PKCS#7 block; plain
     * `OPENSSL_RAW_DATA` would validate-and-strip a block that was never
     * written and fail outright on the random padding tail.
     *
     * The padding is deliberately NOT stripped here and not validated here:
     * stripping is `PassportDecryptor`'s concern and this class verifies
     * DIGESTS only, so the bytes that get hashed are the AES input verbatim —
     * padding included — which is what upstream hashes.
     *
     * @param  string  $rawDigest  the raw 32-byte digest, used as the KDF input
     *
     * @throws RuntimeException with {@see self::INTEGRITY_ERROR} on every refusal
     */
    private static function recoverPaddedPlaintext(
        string $ciphertext,
        string $rawDigest,
        string $encryptedSecret,
        string $privateKeyPem,
    ): string {
        $secret = '';
        if (! openssl_private_decrypt(
            $encryptedSecret,
            $secret,
            $privateKeyPem,
            OPENSSL_PKCS1_OAEP_PADDING,
        ) || $secret === '') {
            throw new RuntimeException(self::INTEGRITY_ERROR);
        }

        $secretHash = hash('sha512', $secret.$rawDigest, true);

        $padded = openssl_decrypt(
            $ciphertext,
            'aes-256-cbc',
            substr($secretHash, 0, 32),
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            substr($secretHash, 32, 16),
        );

        if ($padded === false) {
            throw new RuntimeException(self::INTEGRITY_ERROR);
        }

        return $padded;
    }
}
