<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backup;

use MeRezaRezaei\Teleframe\Backup\BackupRunner;
use MeRezaRezaei\Teleframe\Backup\InMemoryVault;
use MeRezaRezaei\Teleframe\Backup\Pruner;
use MeRezaRezaei\Teleframe\Backup\Restorer;
use MeRezaRezaei\Teleframe\Backup\VaultCrypto;
use PHPUnit\Framework\TestCase;

/**
 * Two medium findings in the backup writer, which between them decided
 * what an attacker who can LIST the channel learns:
 *
 *  1. Chunks used to be named by their PLAINTEXT sha256. A document named
 *     by its plaintext hash is a block-level chosen-plaintext confirmation
 *     oracle at public 4 MiB boundaries — you can ask "does this channel
 *     contain this exact 4 MiB block?" and get a yes/no. TStore has always
 *     documented the opposite rule (ObjectManifest carries wire_sha256
 *     "because the sealed document must be named by its ciphertext, never
 *     by plaintext"); Backup did not. Chunks are now named by their
 *     CIPHERTEXT hash, and the plaintext address travels only inside the
 *     sealed manifest's `wire_chunks`.
 *  2. The manifest had no generation counter, so "highest message id wins"
 *     made a rollback indistinguishable from a fresh backup.
 *
 * These are end-to-end: run → inspect the wire names → restore → prune.
 */
final class BackupAddressingTest extends TestCase
{
    private const PASSPHRASE = 'correct horse battery staple';

    private const CONTENT = 'the quick brown fox jumps over the lazy dog';

    protected function tearDown(): void
    {
        BackupRunner::setLockConnection(null);
        BackupRunner::setLockLogger(null);

        parent::tearDown();
    }

    private static function makeSet(string $content = self::CONTENT): string
    {
        $root = sys_get_temp_dir().'/tf-bk-'.bin2hex(random_bytes(6));
        mkdir($root.'/sub', 0700, true);
        file_put_contents($root.'/a.txt', $content);
        file_put_contents($root.'/sub/b.txt', $content.' (second)');

        return $root;
    }

    private static function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir.'/'.$entry;
            if (is_link($path) || is_file($path)) {
                unlink($path);
            } elseif (is_dir($path)) {
                self::rrmdir($path);
            }
        }
        rmdir($dir);
    }

    /** @return list<string> the document names the vault actually stores */
    private static function wireNames(InMemoryVault $vault): array
    {
        $names = [];
        foreach ($vault->findMessagesByName('') as $entry) {
            $names[] = (string) $entry['name'];
        }

        return $names;
    }

    public function test_chunks_are_named_by_ciphertext_never_by_plaintext(): void
    {
        $root = self::makeSet();
        $vault = new InMemoryVault;

        try {
            BackupRunner::run(['paths' => [$root], 'set' => 'docs'], $vault, self::PASSPHRASE);

            $plainHashes = [];
            foreach ($vault->getLatestManifest()['files'] as $file) {
                foreach ($file['chunks'] as $hash) {
                    $plainHashes[] = (string) $hash;
                }
            }
            self::assertNotEmpty($plainHashes);

            foreach (self::wireNames($vault) as $name) {
                self::assertNotContains(
                    $name,
                    $plainHashes,
                    'a document named by its plaintext content address is a chosen-plaintext confirmation oracle',
                );
            }

            // The manifest itself still carries the plaintext addresses, and
            // the address book that maps them to the wire names.
            $manifest = $vault->getLatestManifest();
            self::assertArrayHasKey('wire_chunks', $manifest);
            foreach ($plainHashes as $plainHash) {
                self::assertArrayHasKey($plainHash, array_flip($manifest['wire_chunks']));
            }
        } finally {
            self::rrmdir($root);
        }
    }

    public function test_a_chunk_is_restorable_by_its_plaintext_address(): void
    {
        $root = self::makeSet();
        $vault = new InMemoryVault;
        $target = sys_get_temp_dir().'/tf-bk-out-'.bin2hex(random_bytes(6));

        try {
            BackupRunner::run(['paths' => [$root], 'set' => 'docs'], $vault, self::PASSPHRASE);

            $stats = Restorer::restore($vault, self::PASSPHRASE, $target);

            self::assertSame(2, $stats['files']);
            self::assertSame(self::CONTENT, file_get_contents($target.'/a.txt'));
            self::assertSame(self::CONTENT.' (second)', file_get_contents($target.'/sub/b.txt'));
        } finally {
            self::rrmdir($root);
            self::rrmdir($target);
        }
    }

    public function test_a_second_unchanged_run_uploads_nothing_and_keeps_the_address_book(): void
    {
        $root = self::makeSet();
        $vault = new InMemoryVault;

        try {
            BackupRunner::run(['paths' => [$root], 'set' => 'docs'], $vault, self::PASSPHRASE);
            $first = $vault->getLatestManifest();

            $stats = BackupRunner::run(['paths' => [$root], 'set' => 'docs'], $vault, self::PASSPHRASE);
            $second = $vault->getLatestManifest();

            self::assertSame(0, $stats['uploaded'], 'dedup is still keyed on the plaintext content address');
            self::assertGreaterThan(0, $stats['skipped']);
            self::assertSame($first['chunk_hashes'], $second['chunk_hashes']);
            self::assertSame(
                $first['wire_chunks'],
                $second['wire_chunks'],
                'a skipped chunk carries its wire name forward — its ciphertext is not recomputable',
            );
            self::assertSame($first['salt'], $second['salt'], 'the per-set salt is reused so chunk hashes stay stable');
        } finally {
            self::rrmdir($root);
        }
    }

    public function test_the_generation_increases_monotonically_across_runs(): void
    {
        $root = self::makeSet();
        $vault = new InMemoryVault;

        try {
            BackupRunner::run(['paths' => [$root], 'set' => 'docs'], $vault, self::PASSPHRASE);
            self::assertSame(1, $vault->getLatestManifest()['generation']);

            BackupRunner::run(['paths' => [$root], 'set' => 'docs'], $vault, self::PASSPHRASE);
            self::assertSame(2, $vault->getLatestManifest()['generation']);

            BackupRunner::run(['paths' => [$root], 'set' => 'docs'], $vault, self::PASSPHRASE);
            self::assertSame(3, $vault->getLatestManifest()['generation']);
        } finally {
            self::rrmdir($root);
        }
    }

    public function test_the_pruner_keeps_documents_named_by_their_ciphertext(): void
    {
        $root = self::makeSet();
        $vault = new InMemoryVault;

        try {
            BackupRunner::run(['paths' => [$root], 'set' => 'docs'], $vault, self::PASSPHRASE);
            $manifest = $vault->getLatestManifest();

            $before = count(self::wireNames($vault));
            $stats = Pruner::prune($vault, $manifest['chunk_hashes'], 'docs');

            self::assertSame(0, $stats['pruned'], 'every live chunk survives: the keep-set resolves to the wire names');
            self::assertSame($before, count(self::wireNames($vault)));

            // And the set is still restorable after the GC walked it.
            $target = sys_get_temp_dir().'/tf-bk-prune-'.bin2hex(random_bytes(6));
            Restorer::restore($vault, self::PASSPHRASE, $target);
            self::assertSame(self::CONTENT, file_get_contents($target.'/a.txt'));
            self::rrmdir($target);
        } finally {
            self::rrmdir($root);
        }
    }

    public function test_the_pruner_still_drops_orphans(): void
    {
        $root = self::makeSet();
        $vault = new InMemoryVault;

        try {
            BackupRunner::run(['paths' => [$root], 'set' => 'docs'], $vault, self::PASSPHRASE);
            $manifest = $vault->getLatestManifest();

            // An orphan from a previous set: a real document, not in any manifest.
            $orphan = hash('sha256', VaultCrypto::encryptChunk(
                VaultCrypto::deriveKey('other', VaultCrypto::newSalt()),
                'orphaned content',
            ));
            $vault->putChunk($orphan, 'orphaned ciphertext');

            $stats = Pruner::prune($vault, $manifest['chunk_hashes'], 'docs');

            self::assertSame(1, $stats['pruned']);
            self::assertNotContains($orphan, self::wireNames($vault));
        } finally {
            self::rrmdir($root);
        }
    }

    public function test_a_manifest_written_by_another_passphrase_cannot_be_read(): void
    {
        $root = self::makeSet();
        $vault = new InMemoryVault;

        try {
            BackupRunner::run(['paths' => [$root], 'set' => 'docs'], $vault, self::PASSPHRASE);

            $this->expectException(\RuntimeException::class);

            Restorer::restore($vault, 'the wrong passphrase', sys_get_temp_dir().'/tf-bk-wrong-'.bin2hex(random_bytes(6)));
        } finally {
            self::rrmdir($root);
        }
    }
}
