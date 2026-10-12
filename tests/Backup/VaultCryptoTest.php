<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backup;

use MeRezaRezaei\Teleframe\Backup\VaultCrypto;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class VaultCryptoTest extends TestCase
{
    private const PASSPHRASE = 'correct horse battery staple';

    private static ?string $cachedKey = null;

    private static function key(): string
    {
        if (self::$cachedKey === null) {
            self::$cachedKey = VaultCrypto::deriveKey(self::PASSPHRASE, str_repeat("\x00", 16));
        }

        return self::$cachedKey;
    }

    public function test_new_salt_is_16_random_bytes(): void
    {
        $a = VaultCrypto::newSalt();
        $b = VaultCrypto::newSalt();

        self::assertSame(16, strlen($a));
        self::assertSame(16, strlen($b));
        self::assertNotSame($a, $b);
    }

    public function test_derive_key_yields_32_raw_bytes(): void
    {
        $key = VaultCrypto::deriveKey(self::PASSPHRASE, str_repeat("\x01", 16));

        self::assertSame(32, strlen($key));
    }

    public function test_derive_key_is_deterministic_per_salt(): void
    {
        $salt = str_repeat("\x02", 16);

        self::assertSame(
            VaultCrypto::deriveKey(self::PASSPHRASE, $salt),
            VaultCrypto::deriveKey(self::PASSPHRASE, $salt),
        );
    }

    public function test_salt_or_passphrase_change_changes_key(): void
    {
        $saltA = str_repeat("\x03", 16);
        $saltB = str_repeat("\x04", 16);
        $base = VaultCrypto::deriveKey(self::PASSPHRASE, $saltA);

        self::assertNotSame($base, VaultCrypto::deriveKey(self::PASSPHRASE, $saltB));
        self::assertNotSame($base, VaultCrypto::deriveKey('different passphrase', $saltA));
    }

    public function test_derive_key_rejects_wrong_salt_length(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        VaultCrypto::deriveKey(self::PASSPHRASE, 'too short');
    }

    /**
     * @return list<array{string:int, data:string}>
     */
    public static function plaintextProvider(): array
    {
        return [
            ['size' => 1, 'data' => 'x'],
            ['size' => 64, 'data' => str_repeat('a', 64)],
            ['size' => 65536, 'data' => str_repeat("b\0c", 21846) . 'b'],
            ['size' => 65537, 'data' => str_repeat('z', 65537)],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('plaintextProvider')]
    public function test_encrypt_chunk_roundtrips(int $size, string $data): void
    {
        $key = self::key();
        $blob = VaultCrypto::encryptChunk($key, $data);

        self::assertGreaterThan(24 + $size, strlen($blob));
        self::assertNotSame($data, substr($blob, 24));
        self::assertSame($data, VaultCrypto::decryptChunk($key, $blob));
    }

    public function test_encrypt_chunk_rejects_empty_plaintext(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('empty');

        VaultCrypto::encryptChunk(self::key(), '');
    }

    public function test_encrypt_chunk_rejects_wrong_key_size(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        VaultCrypto::encryptChunk('short key', 'data');
    }

    public function test_decrypt_chunk_rejects_wrong_key_size(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        VaultCrypto::decryptChunk('short key', str_repeat("\0", 40));
    }

    public function test_encrypt_chunk_output_is_nondeterministic(): void
    {
        $key = self::key();

        self::assertNotSame(
            VaultCrypto::encryptChunk($key, 'same plaintext'),
            VaultCrypto::encryptChunk($key, 'same plaintext'),
        );
    }

    public function test_decrypt_chunk_with_wrong_key_throws(): void
    {
        $blob = VaultCrypto::encryptChunk(self::key(), 'secret payload');
        $wrongKey = VaultCrypto::deriveKey(self::PASSPHRASE, str_repeat("\x05", 16));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Decryption failed');

        VaultCrypto::decryptChunk($wrongKey, $blob);
    }

    public function test_decrypt_chunk_with_tampered_ciphertext_throws(): void
    {
        $key = self::key();
        $blob = VaultCrypto::encryptChunk($key, 'secret payload');
        $blob[strlen($blob) - 1] = chr((ord($blob[strlen($blob) - 1]) + 1) % 256);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Decryption failed');

        VaultCrypto::decryptChunk($key, $blob);
    }

    public function test_decrypt_chunk_with_tampered_header_throws(): void
    {
        $key = self::key();
        $blob = VaultCrypto::encryptChunk($key, 'secret payload');
        $blob[0] = chr((ord($blob[0]) + 1) % 256);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Decryption failed');

        VaultCrypto::decryptChunk($key, $blob);
    }

    /**
     * @return list<array{string:int, blob:string}>
     */
    public static function truncationProvider(): array
    {
        return [
            ['len' => 0, 'blob' => ''],
            ['len' => 1, 'blob' => 'h'],
            ['len' => 23, 'blob' => str_repeat('h', 23)],
            ['len' => 24, 'blob' => str_repeat('h', 24)],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('truncationProvider')]
    public function test_decrypt_chunk_with_truncated_blob_throws(int $len, string $blob): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Decryption failed');

        VaultCrypto::decryptChunk(self::key(), $blob);
    }
}
