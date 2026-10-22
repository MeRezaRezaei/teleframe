<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\BotMap;

use MeRezaRezaei\Teleframe\Backup\VaultCrypto;
use MeRezaRezaei\Teleframe\Backup\VaultInterface;
use RuntimeException;

/**
 * Per-bot token vault (Phase 5f): stores bot tokens inside the shared Backup
 * vault seam (VaultInterface) wrapped in the standard VaultCrypto AEAD, so a
 * token registered with vault_key never sits in plaintext memory after
 * registration and is DECRYPTED again only when a BotEntry is resolved.
 *
 * Each token lands as one chunk named "tfbintoken:{key}". The stored blob is
 * the raw 16-byte salt, then a delimiter byte, then the AEAD ciphertext —
 * VaultCrypto derives the 32-byte key from the passphrase + the per-token
 * salt, so re-encrypting the same token is a fresh blob every time and a
 * leaked chunk is useless without the passphrase.
 */
final class TokenVault
{
    public const CHUNK_PREFIX = 'tfbintoken:';

    private const SALT_BYTES = 16;

    private const DELIMITER = "\x00";

    public function __construct(
        private VaultInterface $vault,
        private string $passphrase,
    ) {
    }

    /**
     * Store a token under $key (overwrites any prior value for the key).
     */
    public function store(string $key, string $token): void
    {
        if ($token === '') {
            throw new RuntimeException('Cannot vault an empty bot token.');
        }

        $salt = VaultCrypto::newSalt();
        $material = VaultCrypto::deriveKey($this->passphrase, $salt);
        $blob = VaultCrypto::encryptChunk($material, $token);

        $this->vault->putChunk(self::name($key), $salt . self::DELIMITER . $blob);
    }

    /**
     * Decrypt and return the token stored under $key.
     *
     * @throws RuntimeException when the chunk is absent, malformed, or the
     *                          passphrase does not match (VaultCrypto fails loud)
     */
    public function retrieve(string $key): string
    {
        $stored = $this->vault->getChunk(self::name($key));

        if (strlen($stored) <= self::SALT_BYTES) {
            throw new RuntimeException("Stored bot token [{$key}] blob is malformed (too short).");
        }
        if ($stored[self::SALT_BYTES] !== self::DELIMITER) {
            throw new RuntimeException("Stored bot token [{$key}] blob is malformed (bad delimiter).");
        }

        $salt = substr($stored, 0, self::SALT_BYTES);
        $cipher = substr($stored, self::SALT_BYTES + 1);
        $material = VaultCrypto::deriveKey($this->passphrase, $salt);

        return VaultCrypto::decryptChunk($material, $cipher);
    }

    /**
     * True when a token chunk exists under $key (does not decrypt).
     */
    public function has(string $key): bool
    {
        return $this->vault->findMessagesByName(self::name($key)) !== [];
    }

    private static function name(string $key): string
    {
        return self::CHUNK_PREFIX . $key;
    }
}