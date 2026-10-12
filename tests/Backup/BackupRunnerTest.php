<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backup;

use MeRezaRezaei\Teleframe\Backup\BackupRunner;
use MeRezaRezaei\Teleframe\Backup\InMemoryVault;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class BackupRunnerTest extends TestCase
{
    private const A_CONTENT = 'hello backup world';

    private const C_CONTENT = 'totally different payload';

    protected function tearDown(): void
    {
        // Unpin the static lock seams so no lock state leaks across tests.
        BackupRunner::setLockConnection(null);
        BackupRunner::setLockLogger(null);

        parent::tearDown();
    }

    public function test_run_uploads_unique_chunks_and_posts_manifest_last(): void
    {
        $root = self::makeSet();
        $vault = new InMemoryVault;

        $stats = BackupRunner::run(['paths' => [$root], 'set' => 'docs'], $vault, 'correct horse');

        // a.txt + b.txt share content -> one chunk uploaded once, one skipped.
        self::assertSame(
            [
                'uploaded' => 2,
                'skipped' => 1,
                'files' => 3,
                'bytes' => strlen(self::A_CONTENT) * 2 + strlen(self::C_CONTENT),
            ],
            $stats,
        );

        $manifest = $vault->getLatestManifest();
        self::assertNotNull($manifest);
        self::assertSame(1, $manifest['version']);
        self::assertSame('docs', $manifest['set']);
        self::assertIsString($manifest['created'] ?? null);
        self::assertNotSame('', $manifest['created']);
        self::assertIsString($manifest['salt'] ?? null);
        self::assertSame(32, strlen((string) $manifest['salt']));
        self::assertTrue(ctype_xdigit((string) $manifest['salt']));

        $files = $manifest['files'] ?? [];
        self::assertIsArray($files);
        self::assertCount(3, $files);

        [$a, $b, $c] = $files;
        self::assertSame('a.txt', $a['path']);
        self::assertSame(strlen(self::A_CONTENT), $a['size']);
        self::assertSame($a['chunks'], $b['chunks']);
        self::assertSame('b.txt', $b['path']);
        self::assertSame('c.txt', $c['path']);
        self::assertNotSame($a['chunks'], $c['chunks']);

        $chunkHashes = $manifest['chunk_hashes'] ?? [];
        self::assertIsArray($chunkHashes);
        self::assertCount(2, array_unique($chunkHashes));
        self::assertCount(2, $chunkHashes);
        self::assertEqualsCanonicalizing(
            array_merge($a['chunks'], $c['chunks']),
            array_values($chunkHashes),
        );
    }

    public function test_second_unchanged_run_uploads_nothing_and_reuses_salt(): void
    {
        $root = self::makeSet();
        $vault = new InMemoryVault;

        BackupRunner::run(['paths' => [$root], 'set' => 'docs'], $vault, 'correct horse');
        $first = $vault->getLatestManifest();
        self::assertNotNull($first);

        $stats = BackupRunner::run(['paths' => [$root], 'set' => 'docs'], $vault, 'correct horse');

        self::assertSame(
            [
                'uploaded' => 0,
                'skipped' => 3,
                'files' => 3,
                'bytes' => strlen(self::A_CONTENT) * 2 + strlen(self::C_CONTENT),
            ],
            $stats,
        );

        $second = $vault->getLatestManifest();
        self::assertNotNull($second);
        self::assertSame($first['salt'], $second['salt']);
    }

    public function test_excludes_substring_match_is_honored(): void
    {
        $root = self::makeSet();
        file_put_contents($root.'/skipme.log', 'log line');
        file_put_contents($root.'/sub/nested.log', 'nested log');

        $vault = new InMemoryVault;
        $stats = BackupRunner::run(
            ['paths' => [$root], 'set' => 'docs', 'excludes' => ['.log']],
            $vault,
            'pw',
        );

        self::assertSame(3, $stats['files']);
        self::assertSame(2, $stats['uploaded']);
        self::assertSame(1, $stats['skipped']);

        $manifest = $vault->getLatestManifest();
        self::assertNotNull($manifest);
        $paths = array_column($manifest['files'], 'path');
        self::assertEqualsCanonicalizing(['a.txt', 'b.txt', 'c.txt'], $paths);
    }

    public function test_explicit_file_path_entry_uses_basename_as_relative_path(): void
    {
        $root = self::makeSet();
        $vault = new InMemoryVault;

        $stats = BackupRunner::run(['paths' => [$root.'/a.txt'], 'set' => 'docs'], $vault, 'pw');

        self::assertSame(['uploaded' => 1, 'skipped' => 0, 'files' => 1, 'bytes' => strlen(self::A_CONTENT)], $stats);
        $manifest = $vault->getLatestManifest();
        self::assertNotNull($manifest);
        self::assertSame('a.txt', $manifest['files'][0]['path']);
    }

    public function test_chunk_size_from_set_config_splits_files(): void
    {
        $root = self::makeSet();
        $vault = new InMemoryVault;

        $stats = BackupRunner::run(['paths' => [$root.'/c.txt'], 'set' => 'docs', 'chunk_size' => 10], $vault, 'pw');

        $manifest = $vault->getLatestManifest();
        self::assertNotNull($manifest);
        self::assertCount(3, $manifest['files'][0]['chunks']);
        self::assertSame(strlen(self::C_CONTENT), $manifest['files'][0]['size']);
        self::assertSame(3, $stats['uploaded']);
    }

    public function test_empty_file_produces_no_chunks_and_roundtrips_through_manifest(): void
    {
        $root = self::makeSet();
        file_put_contents($root.'/empty.txt', '');
        $vault = new InMemoryVault;

        $stats = BackupRunner::run(['paths' => [$root], 'set' => 'docs'], $vault, 'pw');

        self::assertSame(4, $stats['files']);
        $manifest = $vault->getLatestManifest();
        self::assertNotNull($manifest);
        $byPath = [];
        foreach ($manifest['files'] as $file) {
            $byPath[$file['path']] = $file;
        }
        self::assertSame([], $byPath['empty.txt']['chunks']);
        self::assertSame(0, $byPath['empty.txt']['size']);
    }

    public function test_missing_path_fails_loud(): void
    {
        $vault = new InMemoryVault;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not found');

        BackupRunner::run(['paths' => ['/nonexistent-p4-runner-'.uniqid()], 'set' => 'docs'], $vault, 'pw');
    }

    public function test_empty_paths_list_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        BackupRunner::run(['paths' => []], new InMemoryVault, 'pw');
    }

    public function test_run_acquires_and_releases_the_per_set_lock(): void
    {
        $root = self::makeSet();
        $vault = new InMemoryVault;
        $redis = new LockRedisConnection;
        BackupRunner::setLockConnection($redis);

        $stats = BackupRunner::run(['paths' => [$root], 'set' => 'docs'], $vault, 'correct horse');

        self::assertSame(2, $stats['uploaded']);
        self::assertNull($redis->lockValue('tg:backup:lock:docs'), 'lock must be released after a successful run');

        $setCalls = array_values(array_filter(
            $redis->calls(),
            static fn (array $call): bool => $call[0] === 'set',
        ));
        self::assertCount(1, $setCalls, 'exactly one SET on the lock key');
        self::assertSame('tg:backup:lock:docs', $setCalls[0][1][0]);
        self::assertSame(64, strlen((string) $setCalls[0][1][1]), 'token is 256-bit hex');
        self::assertSame(['NX', 'EX' => 14400], $setCalls[0][1][2], 'SET ... NX EX 14400');
    }

    public function test_run_fails_loud_when_the_set_lock_is_held(): void
    {
        $redis = new LockRedisConnection;
        $redis->primeLock('tg:backup:lock:docs', 'foreign-token');
        BackupRunner::setLockConnection($redis);

        try {
            BackupRunner::run(['paths' => [sys_get_temp_dir()], 'set' => 'docs'], new InMemoryVault, 'pw');
            self::fail('expected RuntimeException for a held set lock');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('already locked', $e->getMessage());
        }

        self::assertSame(
            'foreign-token',
            $redis->lockValue('tg:backup:lock:docs'),
            'the foreign holder\'s lock must survive the failed run',
        );
    }

    public function test_run_degrades_without_lock_when_redis_is_unavailable_and_logs_a_warning(): void
    {
        $root = self::makeSet();
        $vault = new InMemoryVault;

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning')->with(self::stringContains('unavailable'));
        BackupRunner::setLockLogger($logger);
        BackupRunner::setLockConnection(null); // no Redis client — Cache::store() throws in plain PHPUnit

        $stats = BackupRunner::run(['paths' => [$root], 'set' => 'docs'], $vault, 'pw');

        self::assertSame(3, $stats['files']);
        self::assertNotNull($vault->getLatestManifest());
    }

    private static function makeSet(): string
    {
        $root = sys_get_temp_dir().'/p4-runner-'.uniqid();
        mkdir($root.'/sub', 0777, true);
        file_put_contents($root.'/a.txt', self::A_CONTENT);
        file_put_contents($root.'/b.txt', self::A_CONTENT);
        file_put_contents($root.'/c.txt', self::C_CONTENT);

        return $root;
    }
}
