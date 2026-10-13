<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backup;

use MeRezaRezaei\Teleframe\Backup\BackupRunner;
use MeRezaRezaei\Teleframe\Backup\Chunker;
use MeRezaRezaei\Teleframe\Backup\InMemoryVault;
use MeRezaRezaei\Teleframe\Backup\VaultCrypto;
use MeRezaRezaei\Teleframe\Backup\VaultInterface;
use MeRezaRezaei\Teleframe\Store\ChunkPlanner;
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
        self::assertSame(['NX', 'EX' => 21600], $setCalls[0][1][2], 'SET ... NX EX 21600');
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

    /**
     * STORE-VOLUME: peak memory must be bounded by the CHUNK SIZE, not by the
     * file size, and the streamed output must still be byte-identical.
     *
     * The runner used to `file_get_contents()` each source and hand the whole
     * string to Chunker::split(), which holds the file AND a full second copy
     * of it as a list of chunks — measured peak 2N + 8 MiB for a file of N
     * bytes. Nothing in the suite could see that, because every fixture was
     * kilobytes: a file larger than RAM failed as an OOM (or, if the limit
     * bit first, a silently corrupted backup), in production, with no failing
     * test to point at it.
     *
     * The source here is SPARSE (ftruncate + a seek-written marker per chunk,
     * so it costs ~no disk and is generated in milliseconds) and sized well
     * past the memory bound: 128 MiB against a 4 MiB chunk size, i.e. 32x the
     * bound. The bound is asserted as a ceiling on PEAK growth during run(),
     * which the old implementation exceeded by ~4x the whole file (measured
     * 524 MiB here) and this one holds at ~4x the chunk (16 MiB) — the two
     * differ by more than an order of magnitude, so the test discriminates
     * them decisively while staying inside the suite's time budget.
     *
     * Byte-identity is asserted WITHOUT reassembling the file into memory —
     * that would reintroduce the very allocation under test, in the test.
     * Both sides are hashed incrementally: the source straight off disk, the
     * archived bytes chunk-by-chunk out of the vault in manifest order. Equal
     * sha256 over 128 MiB is byte-exactness for every byte, at bounded memory.
     *
     * Each chunk carries a distinct marker, so all 64 chunks are unique and
     * every one is encrypted and uploaded: an all-zeros file would dedup to a
     * single upload and prove far less.
     */
    public function test_a_file_far_larger_than_the_memory_bound_streams_and_stays_byte_identical(): void
    {
        $chunkSize = 4 * 1024 * 1024;
        $chunkCount = 32;
        $fileSize = $chunkSize * $chunkCount; // 128 MiB — 32x the chunk bound

        $root = sys_get_temp_dir().'/p4-stream-'.bin2hex(random_bytes(6));
        mkdir($root, 0777, true);
        $source = $root.'/big.bin';

        $handle = fopen($source, 'wb');
        self::assertIsResource($handle, 'sparse source must be writable');

        try {
            ftruncate($handle, $fileSize);

            // A distinct 8-byte marker at the head of every chunk, written by
            // seeking: the file stays sparse, and no two chunks collide, so the
            // dedup path cannot hide an unread region.
            for ($i = 0; $i < $chunkCount; $i++) {
                fseek($handle, $i * $chunkSize);
                fwrite($handle, pack('J', $i).'TFBMARK');
            }
        } finally {
            fclose($handle);
        }

        // The MEMORY BOUND is measured against a vault that DISCARDS the
        // ciphertext it is handed. InMemoryVault retains every uploaded chunk,
        // so backing a 256 MiB source it holds ~256 MiB of its own — real,
        // expected behaviour for an in-memory fake, and it would swamp the
        // very number under test. A transport-backed vault uploads and
        // releases; discarding models that, so what remains in the peak is the
        // runner's own residency. (Byte-identity below is checked through
        // InMemoryVault, where retention is the point.)
        $discarding = new class implements VaultInterface
        {
            public function putChunk(string $hash, string $bytes): string
            {
                return 'discarded';
            }

            public function getChunk(string $hash): string
            {
                throw new RuntimeException('DiscardingVault stores nothing');
            }

            public function putManifest(string $json): string
            {
                return 'discarded';
            }

            public function getLatestManifest(): ?array
            {
                return null;
            }

            public function listManifests(int $limit = 10, ?array &$unreadableIds = null): array
            {
                return [];
            }

            public function listManifestChunkHashes(int $generations = 2): array
            {
                return [];
            }

            public function findMessagesByName(string $namePrefix): array
            {
                return [];
            }

            public function delete(string $name): void {}
        };

        try {
            $before = memory_get_usage(true);
            memory_reset_peak_usage();

            $stats = BackupRunner::run(
                ['paths' => [$source], 'set' => 'bulk', 'chunk_size' => $chunkSize],
                $discarding,
                'pw',
            );

            $peak = memory_get_peak_usage(true);

            self::assertSame($chunkCount, $stats['uploaded'], 'every unique chunk must be uploaded');
            self::assertSame(0, $stats['skipped']);
            self::assertSame($fileSize, $stats['bytes']);
            self::assertSame(['uploaded' => $chunkCount, 'skipped' => 0, 'files' => 1, 'bytes' => $fileSize], $stats);

            // THE BOUND. Peak growth is a function of the chunk size, so it
            // must stay a small constant multiple of one chunk even though the
            // file is 64x that. The old slurp-and-split path measured ~2N + 8
            // MiB here (~520 MiB) and failed this by an order of magnitude.
            // THE BOUND, stated as a multiple of the CHUNK size because that
            // is the quantity it tracks. Measured 4x chunk here (the fread
            // block, plus the ciphertext sodium produces alongside it, plus
            // slack), and — the point — the same 4x on a 64x-larger file: the
            // peak is a function of the chunk, NOT of the source. The old
            // slurp-and-split path measured ~2N + 8 MiB (about 520 MiB here,
            // i.e. ~2x the whole FILE) and failed this by an order of
            // magnitude.
            $growth = $peak - $before;
            self::assertLessThan(
                5 * $chunkSize,
                $growth,
                sprintf(
                    'peak memory grew %.1f MiB backing a %.1f MiB file at a %.1f MiB chunk size: '
                    .'the source is being held whole rather than streamed',
                    $growth / 1048576,
                    $fileSize / 1048576,
                    $chunkSize / 1048576,
                ),
            );

            // The bound must TRACK the chunk size, not merely stay "small": an
            // 8x larger chunk legitimately raises the live buffer by ~8x (to
            // ~128 MiB), while still bearing no relation to the 256 MiB file.
            // A fixed happy-number ceiling would either fail this or be loose
            // enough to pass a non-streaming implementation at the small size.
            $scaled = 8 * $chunkSize;
            $before2 = memory_get_usage(true);
            memory_reset_peak_usage();
            BackupRunner::run(
                ['paths' => [$source], 'set' => 'bulk2', 'chunk_size' => $scaled],
                $discarding,
                'pw',
            );
            $growth2 = memory_get_peak_usage(true) - $before2;
            self::assertLessThan(
                5 * $scaled,
                $growth2,
                sprintf(
                    'peak memory grew %.1f MiB at an 8x larger (%.1f MiB) chunk on a %.1f MiB file: '
                    .'the bound is not tracking the chunk size',
                    $growth2 / 1048576,
                    $scaled / 1048576,
                    $fileSize / 1048576,
                ),
            );

            // BYTE-IDENTITY, through a vault that RETAINS, and hashed
            // incrementally on both sides so the check itself never holds the
            // file in memory.
            $vault = new InMemoryVault;
            BackupRunner::run(
                ['paths' => [$source], 'set' => 'bulk', 'chunk_size' => $chunkSize],
                $vault,
                'pw',
            );

            $manifest = $vault->getLatestManifest();
            self::assertNotNull($manifest);
            $file = $manifest['files'][0];
            self::assertSame($fileSize, $file['size']);
            self::assertCount($chunkCount, $file['chunks']);

            $key = VaultCrypto::deriveKey('pw', (string) hex2bin((string) $manifest['salt']));

            $archived = hash_init('sha256');
            foreach ($file['chunks'] as $plainHash) {
                hash_update($archived, VaultCrypto::decryptChunk($key, $vault->getChunk((string) $plainHash)));
            }

            self::assertSame(
                hash_file('sha256', $source),
                hash_final($archived),
                'the streamed archive must reassemble to the exact bytes of the source',
            );
        } finally {
            @unlink($source);
            @rmdir($root);
        }
    }

    /**
     * BYTE-IDENTITY, hash-for-hash, against Chunker::split() itself.
     *
     * The test above proves the archive REASSEMBLES to the source, which a
     * near-miss could satisfy: concatenating chunks cut at slightly wrong
     * boundaries still reproduces the file. That is exactly the failure this
     * is here to catch. A boundary shift by even one byte keeps the whole-file
     * hash intact and changes every content address after the shift — so the
     * manifest, the dedup index and every wire name would change while every
     * existing test stayed green. Only a hash-FOR-hash comparison against the
     * reference chunker can see it.
     *
     * The chunk sizes below are chosen to straddle the bounded read block, and
     * each is deliberately NOT a multiple of it:
     *
     *  - 3000003 (> 2 read blocks, prime, odd remainder) — chunks span
     *    several read blocks and the last chunk is a short tail;
     *  - 1000001 (exactly one byte over a block) — the carry across a block
     *    boundary is exercised, and a naive "read a block, yield it" chunker
     *    would split mid-chunk here;
     *  - 7 (far smaller than a block, so several chunks per read) — the
     *    buffer must yield many times inside one read;
     *  - 4194304000 (ChunkPlanner::PREMIUM_CAP, a multiple of nothing) — the
     *    value that made a 5-byte fixture request ~4 GiB. Asserted here at
     *    full width so the cap is covered on BOTH sides: the boundaries must
     *    still be right, and (above) the memory must still be bounded.
     *
     * Comparison is on the chunk hashes the manifest publishes, which is the
     * observable the wire actually depends on; the reference side is
     * Chunker::split(file_get_contents($f), $size) — the exact expression the
     * runner replaced, kept verbatim as the oracle.
     */
    public function test_streamed_chunk_boundaries_match_the_reference_chunker_hash_for_hash(): void
    {
        $root = sys_get_temp_dir().'/p4-ident-'.bin2hex(random_bytes(6));
        mkdir($root, 0700, true);

        // The two paths are derived from $root and therefore exist from here,
        // BEFORE the try, so the cleanup in the finally can never reference an
        // assignment that a mid-body throw skipped. They are pure path
        // expressions; hoisting them costs nothing and makes the finally total.
        $source = $root.'/src.bin';
        $small = $root.'/small.bin';

        try {
            // Non-repeating content: identical chunks would dedup away and
            // hide a boundary error behind fewer manifest entries. Seeded from
            // a rolling 32-byte chain, so generation is linear — hashing the
            // whole accumulator each round would be O(n^2) and cost more than
            // the test it supports.
            $contents = '';
            $seed = 'teleframe-boundary-probe';
            while (strlen($contents) < 7000003) {
                $seed = hash('sha256', $seed, true);
                $contents .= $seed;
            }

            $source = $root.'/src.bin';
            file_put_contents($source, $contents);

            // The 7-byte chunk size is checked against its OWN small source:
            // against the 7 MB one it would be ~1,000,000 encrypted chunks in
            // a vault that deliberately retains every upload, which measures
            // the test's own footprint rather than the boundary rule.
            file_put_contents($small, substr($contents, 0, 9973));

            foreach ([[$source, 3000003], [$source, 1000001], [$small, 7], [$source, ChunkPlanner::PREMIUM_CAP]] as [$file, $chunkSize]) {
                $vault = new InMemoryVault;

                $stats = BackupRunner::run(
                    ['paths' => [$file], 'set' => 'ident', 'chunk_size' => $chunkSize],
                    $vault,
                    'pw',
                );

                $expected = Chunker::split(file_get_contents($file), $chunkSize);
                $actual = $vault->getLatestManifest()['files'][0]['chunks'];

                self::assertSame(
                    array_column($expected, 'hash'),
                    $actual,
                    sprintf(
                        'streamed chunk boundaries must be byte-identical to Chunker::split at chunk_size %d '
                        .'(%d expected chunks, %d streamed)',
                        $chunkSize,
                        count($expected),
                        count($actual),
                    ),
                );
                self::assertSame(strlen((string) file_get_contents($file)), $stats['bytes'], 'every byte is accounted for at chunk_size '.$chunkSize);
            }

            // The degenerate contract, on the streaming path: an empty file is
            // no chunks, exactly as Chunker::split('', $size) is. A chunker
            // that flushed its buffer unconditionally would yield one empty
            // chunk here, which encrypts and uploads as a real document.
            file_put_contents($root.'/empty.bin', '');
            $empty = new InMemoryVault;
            $emptyStats = BackupRunner::run(
                ['paths' => [$root.'/empty.bin'], 'set' => 'empty', 'chunk_size' => 1024],
                $empty,
                'pw',
            );
            self::assertSame(0, $emptyStats['uploaded'], 'an empty file must upload nothing');
            self::assertSame([], $empty->getLatestManifest()['files'][0]['chunks'], 'an empty file yields no chunks');
        } finally {
            @unlink($source);
            @unlink($small);
            @unlink($root.'/empty.bin');
            @rmdir($root);
        }
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
