<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backup;

use MeRezaRezaei\Teleframe\Backup\BackupRunner;
use MeRezaRezaei\Teleframe\Backup\InMemoryVault;
use MeRezaRezaei\Teleframe\Backup\Restorer;
use MeRezaRezaei\Teleframe\Backup\VaultInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RestorerTest extends TestCase
{
    public function test_restore_rebuilds_byte_identical_files_including_nested_and_empty(): void
    {
        $root = sys_get_temp_dir() . '/p4-restore-src-' . uniqid();
        mkdir($root . '/sub/deep', 0777, true);
        $files = [
            '/a.txt' => 'first file',
            '/sub/binary.bin' => random_bytes(1000),
            '/sub/deep/nested.txt' => 'nested content',
            '/empty.txt' => '',
        ];
        $expected = [];
        foreach ($files as $relative => $content) {
            file_put_contents($root . $relative, $content);
            $expected[ltrim($relative, '/')] = $content;
        }

        $vault = new InMemoryVault();
        $stats = BackupRunner::run(['paths' => [$root], 'set' => 'docs', 'chunk_size' => 64], $vault, 'correct horse');
        self::assertSame(count($files), $stats['files']);

        $target = sys_get_temp_dir() . '/p4-restore-dst-' . uniqid();
        $restored = Restorer::restore($vault, 'correct horse', $target);

        $expectedBytes = array_sum(array_map('strlen', $expected));
        self::assertSame(['files' => count($files), 'bytes' => $expectedBytes], $restored);

        foreach ($expected as $relative => $content) {
            $restoredPath = $target . '/' . $relative;
            self::assertFileExists($restoredPath);
            self::assertSame(hash('sha256', $content), hash_file('sha256', $restoredPath), $relative);
        }
    }

    public function test_restore_without_manifest_fails_loud(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no manifest');

        Restorer::restore(new InMemoryVault(), 'pw', sys_get_temp_dir() . '/p4-restore-none-' . uniqid());
    }

    public function test_wrong_passphrase_fails_with_decryption_error(): void
    {
        $root = self::singleFileSet('secret payload');

        $vault = new InMemoryVault();
        BackupRunner::run(['paths' => [$root]], $vault, 'right passphrase');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Decryption failed');

        Restorer::restore($vault, 'wrong passphrase', sys_get_temp_dir() . '/p4-restore-wrong-' . uniqid());
    }

    public function test_tampered_chunk_fails_decryption(): void
    {
        $root = self::singleFileSet('secret payload');

        $inner = new InMemoryVault();
        $vault = new CorruptingVault($inner);
        BackupRunner::run(['paths' => [$root]], $vault, 'right passphrase');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Decryption failed');

        Restorer::restore($inner, 'right passphrase', sys_get_temp_dir() . '/p4-restore-tamper-' . uniqid());
    }

    public function test_manifest_path_traversal_is_rejected_before_any_write(): void
    {
        $root = self::singleFileSet('innocent');

        $vault = new InMemoryVault();
        BackupRunner::run(['paths' => [$root]], $vault, 'pw');

        $manifest = $vault->getLatestManifest();
        self::assertNotNull($manifest);
        $manifest['files'][0]['path'] = '../pwned.txt';
        $vault->putManifest((string) json_encode($manifest, JSON_THROW_ON_ERROR));

        $base = sys_get_temp_dir() . '/p4-restore-travel-' . uniqid();
        $target = $base . '/target';

        try {
            Restorer::restore($vault, 'pw', $target);
            self::fail('traversal manifest must be rejected');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('unsafe manifest path', $e->getMessage());
        }

        self::assertFileDoesNotExist($base . '/pwned.txt');
        self::assertDirectoryDoesNotExist($target);
    }

    private static function singleFileSet(string $content): string
    {
        $root = sys_get_temp_dir() . '/p4-restore-one-' . uniqid();
        mkdir($root, 0777, true);
        file_put_contents($root . '/only.txt', $content);

        return $root;
    }
}

/**
 * Vault double that flips the first ciphertext byte of every stored chunk
 * (corrupts the secretstream header) to simulate in-transit tampering.
 */
final class CorruptingVault implements VaultInterface
{
    private VaultInterface $inner;

    public function __construct(VaultInterface $inner)
    {
        $this->inner = $inner;
    }

    public function putChunk(string $hash, string $bytes): string
    {
        return $this->inner->putChunk($hash, "\xff" . substr($bytes, 1));
    }

    public function getChunk(string $hash): string
    {
        return $this->inner->getChunk($hash);
    }

    public function putManifest(string $json): string
    {
        return $this->inner->putManifest($json);
    }

    public function getLatestManifest(): ?array
    {
        return $this->inner->getLatestManifest();
    }

    public function findMessagesByName(string $namePrefix): array
    {
        return $this->inner->findMessagesByName($namePrefix);
    }

    public function delete(string $name): void
    {
        $this->inner->delete($name);
    }
}
