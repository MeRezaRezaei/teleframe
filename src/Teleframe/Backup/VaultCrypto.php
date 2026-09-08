<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Backup;

use InvalidArgumentException;
use RuntimeException;
use SodiumException;

/**
 * Vault key derivation + per-chunk AEAD. Port of the owner's sdd/backup
 * Crypto design (fork src/Backup/Crypto.php) onto in-memory chunks instead
 * of file streams.
 *
 * Key derivation mirrors the fork's parameters exactly:
 * sodium_crypto_pwhash → Argon2id (SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13),
 * 32 raw output bytes, SODIUM_CRYPTO_PWHASH_OPSLIMIT_MODERATE (3 passes)
 * and SODIUM_CRYPTO_PWHASH_MEMLIMIT_MODERATE (268435456 bytes = 256 MiB).
 *
 * Chunk encryption uses one XChaCha20-Poly1305 secretstream per chunk:
 * init_push header (24 bytes) is prepended to the single push output which
 * is sealed with TAG_FINAL, so any truncation, reordering, or tampering of
 * the blob fails loud in decryptChunk.
 */
final class VaultCrypto
{
    private const KEY_BYTES = 32;

    private const SALT_BYTES = 16;

    public static function newSalt(): string
    {
        return random_bytes(self::SALT_BYTES);
    }

    /**
     * Raw 32-byte Argon2id key (see class docblock for the fork-mirrored
     * opslimit/memlimit). Salt must be exactly 16 raw bytes (newSalt).
     */
    public static function deriveKey(string $passphrase, string $salt): string
    {
        if (strlen($salt) !== self::SALT_BYTES) {
            throw new InvalidArgumentException(
                'salt must be exactly ' . self::SALT_BYTES . ' raw bytes, got ' . strlen($salt),
            );
        }

        return sodium_crypto_pwhash(
            self::KEY_BYTES,
            $passphrase,
            $salt,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_MODERATE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_MODERATE,
            SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13,
        );
    }

    /**
     * Encrypt one chunk into a self-contained binary blob:
     * secretstream header (24 B) || push(plaintext, TAG_FINAL).
     *
     * Empty plaintext is rejected by contract: content-addressed chunking
     * (see Chunker::split) never yields empty chunks, so an empty payload
     * always indicates a caller bug, not a vault payload.
     */
    public static function encryptChunk(string $key, string $plaintext): string
    {
        self::assertKey($key);

        if ($plaintext === '') {
            throw new InvalidArgumentException('empty plaintext: chunks are never empty by contract');
        }

        [$state, $header] = sodium_crypto_secretstream_xchacha20poly1305_init_push($key);
        $cipher = sodium_crypto_secretstream_xchacha20poly1305_push(
            $state,
            $plaintext,
            '',
            SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL,
        );

        return $header . $cipher;
    }

    /**
     * Decrypt a blob produced by encryptChunk. Throws RuntimeException on
     * truncated header/body, tampering, wrong key, or a missing FINAL tag.
     */
    public static function decryptChunk(string $key, string $blob): string
    {
        self::assertKey($key);

        if (strlen($blob) <= SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES) {
            throw new RuntimeException('Decryption failed: truncated header');
        }

        $header = substr($blob, 0, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES);

        try {
            $state = sodium_crypto_secretstream_xchacha20poly1305_init_pull($header, $key);
            $result = sodium_crypto_secretstream_xchacha20poly1305_pull(
                $state,
                substr($blob, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES),
            );
        } catch (SodiumException $e) {
            throw new RuntimeException('Decryption failed: ' . $e->getMessage(), 0, $e);
        }

        if ($result === false) {
            throw new RuntimeException('Decryption failed: corrupted chunk');
        }

        [$plain, $tag] = $result;

        if ($tag !== SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL) {
            throw new RuntimeException('Decryption failed: chunk stream is not FINAL');
        }

        return $plain;
    }

    private static function assertKey(string $key): void
    {
        if (strlen($key) !== self::KEY_BYTES) {
            throw new InvalidArgumentException(
                'key must be exactly ' . self::KEY_BYTES . ' raw bytes, got ' . strlen($key),
            );
        }
    }
}
