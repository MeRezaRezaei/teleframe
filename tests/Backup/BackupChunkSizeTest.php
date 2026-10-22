<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backup;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Backup\BackupRunner;
use MeRezaRezaei\Teleframe\Backup\InMemoryVault;
use MeRezaRezaei\Teleframe\Store\ChunkPlanner;
use PHPUnit\Framework\TestCase;

/**
 * The backup chunk size is a DEDUP GRANULARITY, not a Telegram document cap.
 *
 * The owner's "it make files into 4 gig chunks" (verbatim 2026-09-24:40) is
 * capability S8 = TStore, already honoured by Store\ChunkPlanner. This suite
 * pins the three facts that were previously implicit and got conflated:
 *
 *  1. the default is 4 MiB of BYTES, stated as a number so a future edit
 *     cannot silently drift it; and
 *  2. the hard ceiling is read from ChunkPlanner — the same single source
 *     of truth the TStore path uses — rather than restated as a literal; and
 *  3. WHICH ceiling applies is THE ACCOUNT's, declared with `premium => true`
 *     on the backup set. A free account cannot send a PREMIUM_CAP document,
 *     so a set that claimed the premium cap without claiming to be premium
 *     used to validate cleanly and then fail at upload time (F12-1) — the
 *     single most expensive moment to discover a configuration error.
 */
final class BackupChunkSizeTest extends TestCase
{
    public function test_default_chunk_size_is_four_mebibytes_in_bytes(): void
    {
        self::assertSame(4 * 1024 * 1024, BackupRunner::DEFAULT_CHUNK_SIZE);
        self::assertSame(4194304, BackupRunner::DEFAULT_CHUNK_SIZE);
        self::assertNotSame(
            ChunkPlanner::PREMIUM_CAP,
            BackupRunner::DEFAULT_CHUNK_SIZE,
            'the backup default is a dedup granularity and must not be conflated with the 4 GiB document cap',
        );
    }

    public function test_default_is_derived_from_the_part_math_not_a_magic_number(): void
    {
        // The TStore cap math: 512 KiB parts, premium = 8000 parts.
        // NOTE the exact bytes: 512 KiB * 8000 = 4,194,304,000 = 3.90625 GiB,
        // NOT 4 GiB (4,294,967,296). The `// 4GiB` comments on
        // ChunkPlanner::FREE_CAP / PREMIUM_CAP are loose shorthand for "the
        // premium part budget"; these are the authoritative byte counts, and
        // tests/Store is the authority on the cap itself. What matters here is
        // only that the backup default stays far below either cap.
        self::assertSame(524288, ChunkPlanner::PART_SIZE);
        self::assertSame(8000, ChunkPlanner::PREMIUM_MAX_PARTS);
        self::assertSame(4194304000, ChunkPlanner::PREMIUM_CAP);
        self::assertSame(2097152000, ChunkPlanner::FREE_CAP);
    }

    public function test_the_default_stays_well_inside_every_accounts_document_cap(): void
    {
        // Safe for free AND premium: the 4 MiB default must never be near the cap.
        self::assertLessThan(ChunkPlanner::FREE_CAP, BackupRunner::DEFAULT_CHUNK_SIZE);
        self::assertLessThan(ChunkPlanner::PREMIUM_CAP, BackupRunner::DEFAULT_CHUNK_SIZE);
    }

    public function test_config_default_matches_the_runner_default(): void
    {
        // The published config and the runner must not disagree about the
        // default — a divergence here is what made the value ambiguous.
        $config = require __DIR__.'/../../src/Laravel/config/teleframe.php';
        self::assertIsArray($config);
        self::assertSame(BackupRunner::DEFAULT_CHUNK_SIZE, $config['backup']['chunk_size']);
    }

    public function test_chunk_size_at_the_premium_cap_is_accepted(): void
    {
        // Exactly the cap is legal: it is the largest single document any
        // account can send, so the guard must be 'greater than', not 'gte'.
        // It also has to be CLAIMED (`premium => true`) — see
        // test_free_account_refuses_the_premium_cap below.
        $root = self::makeSet();
        $vault = new InMemoryVault;

        $stats = BackupRunner::run(
            ['paths' => [$root.'/a.txt'], 'set' => 'docs', 'chunk_size' => ChunkPlanner::PREMIUM_CAP, 'premium' => true],
            $vault,
            'pw',
        );

        self::assertSame(['uploaded' => 1, 'skipped' => 0, 'files' => 1, 'bytes' => 18], $stats);
    }

    public function test_chunk_size_above_the_premium_cap_is_rejected(): void
    {
        $root = self::makeSet();
        $vault = new InMemoryVault;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('exceeds the Telegram document cap');

        BackupRunner::run(
            ['paths' => [$root.'/a.txt'], 'set' => 'docs', 'chunk_size' => ChunkPlanner::PREMIUM_CAP + 1, 'premium' => true],
            $vault,
            'pw',
        );
    }

    public function test_rejection_message_names_the_shared_cap_source(): void
    {
        $root = self::makeSet();
        $vault = new InMemoryVault;

        try {
            BackupRunner::run(
                ['paths' => [$root.'/a.txt'], 'set' => 'docs', 'chunk_size' => ChunkPlanner::PREMIUM_CAP + 1, 'premium' => true],
                $vault,
                'pw',
            );
            self::fail('expected the over-cap chunk_size to be rejected');
        } catch (InvalidArgumentException $e) {
            // The message quotes the cap from ChunkPlanner, so a change to the
            // part math surfaces here rather than as a stale hard-coded number.
            self::assertStringContainsString((string) ChunkPlanner::PREMIUM_CAP, $e->getMessage());
            self::assertStringContainsString('one whole document', $e->getMessage());
        }
    }

    // ------------------------------------------------------------------
    // F12-1 — the free-account gap. A chunk_size between FREE_CAP and
    // PREMIUM_CAP used to validate cleanly and then fail at UPLOAD time on
    // a non-premium account, which is the worst possible moment: after the
    // whole set has been scanned, chunked and encrypted.
    // ------------------------------------------------------------------

    public function test_free_account_refuses_the_premium_cap(): void
    {
        $root = self::makeSet();
        $vault = new InMemoryVault;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('exceeds the FREE-account Telegram document cap');

        BackupRunner::run(
            ['paths' => [$root.'/a.txt'], 'set' => 'docs', 'chunk_size' => ChunkPlanner::PREMIUM_CAP],
            $vault,
            'pw',
        );
    }

    public function test_free_account_refusal_names_the_free_cap_and_the_way_out(): void
    {
        $root = self::makeSet();
        $vault = new InMemoryVault;

        try {
            BackupRunner::run(
                ['paths' => [$root.'/a.txt'], 'set' => 'docs', 'chunk_size' => ChunkPlanner::FREE_CAP + 1],
                $vault,
                'pw',
            );
            self::fail('expected a free-account chunk_size above FREE_CAP to be rejected');
        } catch (InvalidArgumentException $e) {
            self::assertStringContainsString((string) ChunkPlanner::FREE_CAP, $e->getMessage());
            self::assertStringContainsString('premium => true', $e->getMessage());
            self::assertStringContainsString((string) ChunkPlanner::PREMIUM_CAP, $e->getMessage());
        }
    }

    public function test_chunk_size_exactly_at_the_free_cap_is_accepted(): void
    {
        $root = self::makeSet();
        $vault = new InMemoryVault;

        $stats = BackupRunner::run(
            ['paths' => [$root.'/a.txt'], 'set' => 'docs', 'chunk_size' => ChunkPlanner::FREE_CAP],
            $vault,
            'pw',
        );

        self::assertSame(['uploaded' => 1, 'skipped' => 0, 'files' => 1, 'bytes' => 18], $stats);
    }

    public function test_premium_flag_must_be_a_boolean(): void
    {
        $root = self::makeSet();
        $vault = new InMemoryVault;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('premium must be a boolean');

        BackupRunner::run(
            ['paths' => [$root.'/a.txt'], 'set' => 'docs', 'premium' => 'yes'],
            $vault,
            'pw',
        );
    }

    /**
     * One rejected size per case, so a failure names WHICH size was accepted.
     *
     * @return array<string, array{int}>
     */
    public static function nonPositiveChunkSizes(): array
    {
        return [
            'zero' => [0],
            'negative' => [-1],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('nonPositiveChunkSizes')]
    public function test_non_positive_chunk_size_is_still_rejected(int $bad): void
    {
        $root = self::makeSet();
        $vault = new InMemoryVault;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('positive integer');

        BackupRunner::run(
            ['paths' => [$root.'/a.txt'], 'set' => 'docs', 'chunk_size' => $bad],
            $vault,
            'pw',
        );
    }

    /**
     * The dedup rationale, asserted rather than merely asserted-in-prose: a
     * small chunk re-uploads less after a one-line edit than a large one would.
     */
    public function test_smaller_chunks_dedup_finer_than_the_premium_cap(): void
    {
        $root = self::makeSet();

        $small = new InMemoryVault;
        BackupRunner::run(
            ['paths' => [$root.'/c.txt'], 'set' => 'docs', 'chunk_size' => 10],
            $small,
            'pw',
        );
        $large = new InMemoryVault;
        BackupRunner::run(
            ['paths' => [$root.'/c.txt'], 'set' => 'docs', 'chunk_size' => ChunkPlanner::PREMIUM_CAP, 'premium' => true],
            $large,
            'pw',
        );

        $smallChunks = $small->getLatestManifest()['chunk_hashes'];
        $largeChunks = $large->getLatestManifest()['chunk_hashes'];

        self::assertCount(3, $smallChunks); // 25 bytes at 10 bytes/chunk
        self::assertCount(1, $largeChunks); // 25 bytes at 4 GiB/chunk
    }

    private static function makeSet(): string
    {
        $root = sys_get_temp_dir().'/tf-chunk-'.bin2hex(random_bytes(6));
        mkdir($root, 0700, true);
        file_put_contents($root.'/a.txt', 'hello backup world');
        file_put_contents($root.'/c.txt', 'totally different payload');

        return $root;
    }
}
