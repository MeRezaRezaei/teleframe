<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

use MeRezaRezaei\Teleframe\Core\Passport\PassportDecryptor;

/**
 * PassportDataEnvelope — §4 `DataCredentials { data_hash, secret }` over one
 * `SecureData.data` field, carrying RAW bytes on both legs.
 *
 * ══ WHY THE BYTES ARE RAW HERE AND NOT AT INTAKE ═════════════════════════
 *
 * The Bot API and MTProto disagree about the encoding of exactly these three
 * fields, and the disagreement is invisible if you read past it:
 *
 *   - the Bot API `EncryptedPassportElement.data` is documented as
 *     "**Base64-encoded** encrypted Telegram Passport element data", and
 *     `secure_data[type].hash` / `.secret` are the base64 `hash` / `secret`
 *     strings the `Credentials` JSON carries;
 *   - the MTProto `secureData#8aeabec3 data:bytes data_hash:bytes
 *     secret:bytes` is three RAW `bytes` fields — upstream states "All hashes
 *     are raw binary, not hexits", which is exactly why
 *     `PassportCredentialsVerifier` refuses a hex or base64 digest at the
 *     boundary rather than letting it reach `hash_equals`.
 *
 * Getting this wrong in either direction is not a soft failure. Feed a base64
 * STRING to the raw leg and `hash_equals` returns false on a length mismatch
 * immediately, so every genuine login is refused. Feed raw bytes to the base64
 * leg and you get the same always-false. Neither is distinguishable, from
 * outside, from a real tamper — which is why the encoding is resolved ONCE,
 * here, by {@see PassportDocumentSource} (which knows which shape it read), and
 * every consumer downstream takes raw bytes and never guesses.
 *
 * This is the same split `PassportCredentialsVerifier`'s class docblock calls
 * "two different doors [that] get different treatment", lifted from prose into
 * a type, so the compiler and the reader both see it.
 *
 * ══ CONSTRUCTION IS A REFUSAL, NOT A PARSE ════════════════════════════════
 *
 * The static factories return `self|null` — null meaning "this shape did not
 * carry a §4 `DataCredentials` for this type at all", which the caller turns
 * into a NAMED {@see PassportDocumentRefusal}. A field that is present but
 * unusable (wrong length, undecodable) is not null and not an exception: it is
 * a refusal with code {@see PassportDocumentRefusal::ENCODING_INVALID}, because
 * "the bytes were there and they were not a digest" is a fact a host can act on
 * and "absent" is not. `src/Teleframe/Passport/PassportDocumentSource` is what
 * draws that line and why.
 *
 * Plain data, zero-regex (`src/Teleframe`), no container.
 */
final class PassportDataEnvelope
{
    private function __construct(
        private readonly string $type,
        private readonly string $ciphertext,
        private readonly string $rawHash,
        private readonly string $rawSecret,
    ) {}

    /**
     * From already-RAW bytes (the MTProto shape, and every shape after
     * `PassportDocumentSource` has decoded a base64 one). Null when the digest
     * is not exactly 32 raw bytes — the boundary refusal, named by the caller.
     */
    public static function fromRaw(string $type, string $ciphertext, string $rawHash, string $rawSecret): ?self
    {
        if ($ciphertext === '' || strlen($rawHash) !== 32 || $rawSecret === '') {
            return null;
        }

        return new self($type, $ciphertext, $rawHash, $rawSecret);
    }

    /** The §5 `allowed_types` value this envelope answers. */
    public function type(): string
    {
        return $this->type;
    }

    /** The raw `SecureData.data` bytes — the AES INPUT's ciphertext. */
    public function ciphertext(): string
    {
        return $this->ciphertext;
    }

    /** The raw 32-byte `data_hash`. Never hexits, never a base64 string. */
    public function rawHash(): string
    {
        return $this->rawHash;
    }

    /** The raw RSA-OAEP-sealed `data_secret`. */
    public function rawSecret(): string
    {
        return $this->rawSecret;
    }

    /** The exact argument order of `PassportDecryptor::decryptDataCredentials()`. */
    public function decryptWith(string $privateKeyPem): string
    {
        return PassportDecryptor::decryptDataCredentials(
            $this->ciphertext,
            $this->rawHash,
            $this->rawSecret,
            $privateKeyPem,
        );
    }
}
