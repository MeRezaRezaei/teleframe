<?php

declare(strict_types=1);

namespace Tests\Store;

use MeRezaRezaei\Teleframe\Store\ChunkPlanner;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The arithmetic claims, pinned to real byte values.
 *
 * The defect this pins: ChunkPlanner::PREMIUM_CAP is 4194304000 bytes,
 * which is 3.90625 GiB, NOT "4 GiB" — and three separate places (the
 * ChunkPlanner docblock, the shipped teleframe.php config comment, and
 * CHANGELOG.md) asserted "4 GiB / 4GiB exactly". A comment that
 * disagrees with the constant is a claim a host plans around, so the
 * numbers themselves are asserted here AND the three prose sites are
 * asserted to agree with the constants.
 */
final class ChunkArithmeticTest extends TestCase
{
    public function test_part_size_is_512_kib(): void
    {
        $this->assertSame(524288, ChunkPlanner::PART_SIZE);
        $this->assertSame(512 * 1024, ChunkPlanner::PART_SIZE);
    }

    public function test_free_cap_is_exactly_2097152000_bytes(): void
    {
        $this->assertSame(524288 * 4000, ChunkPlanner::FREE_CAP);
        $this->assertSame(2097152000, ChunkPlanner::FREE_CAP);
        // 1.953125 GiB — NOT 2 GiB.
        $this->assertNotSame(2 * 1024 ** 3, ChunkPlanner::FREE_CAP);
    }

    public function test_premium_cap_is_exactly_4194304000_bytes(): void
    {
        $this->assertSame(524288 * 8000, ChunkPlanner::PREMIUM_CAP);
        $this->assertSame(4194304000, ChunkPlanner::PREMIUM_CAP);
        // 3.90625 GiB — NOT 4 GiB, and not 4 GB either (4 GB = 4e9).
        $this->assertNotSame(4 * 1024 ** 3, ChunkPlanner::PREMIUM_CAP);
        $this->assertNotSame(4000000000, ChunkPlanner::PREMIUM_CAP);
    }

    public function test_default_install_gets_the_free_cap(): void
    {
        // planChunks' $premium parameter default IS the shipped default,
        // so this pins "2 GB-ish unless the host opts in", not 4 GB.
        $params = (new \ReflectionMethod(ChunkPlanner::class, 'planChunks'))->getParameters();
        $this->assertFalse($params[1]->getDefaultValue(), 'premium must stay opt-in');

        // An object the premium cap would hold still splits under the default.
        $this->assertCount(2, ChunkPlanner::planChunks(ChunkPlanner::PREMIUM_CAP));
        $this->assertSame(
            ChunkPlanner::FREE_CAP,
            ChunkPlanner::planChunks(ChunkPlanner::PREMIUM_CAP)[0]['size'],
        );
        $this->assertCount(1, ChunkPlanner::planChunks(ChunkPlanner::PREMIUM_CAP, premium: true));
    }

    /**
     * Each prose site must carry the honest figure, and must not still
     * claim a bare "2 GiB"/"4 GiB" cap.
     */
    #[DataProvider('proseSites')]
    public function test_prose_states_the_real_caps_not_4_gib(string $file, string $freeCap, string $premiumCap): void
    {
        $this->assertFileExists($file);
        $text = (string) file_get_contents($file);

        $this->assertStringContainsString($freeCap, $text, basename($file).' must state the free cap honestly');
        $this->assertStringContainsString($premiumCap, $text, basename($file).' must state the premium cap honestly');

        foreach (explode("\n", $text) as $line) {
            // Only lines that STATE A CAP are linted: prose that merely
            // describes a historical allocation ("allocated 2 GiB to assert
            // a chunk boundary") is not a capacity claim.
            if (! str_contains($line, 'cap') && ! str_contains($line, 'CAP')) {
                continue;
            }
            $bare = str_contains($line, '2 GiB') || str_contains($line, '4 GiB')
                || str_contains($line, '4GiB') || str_contains($line, '2GiB');
            $this->assertFalse(
                $bare,
                basename($file).' still asserts a bare round cap: '.trim($line),
            );
        }
    }

    /**
     * @return list<array{0: string, 1: string, 2: string}>
     */
    public static function proseSites(): array
    {
        $root = __DIR__.'/../..';

        return [
            'ChunkPlanner docblock' => [$root.'/src/Teleframe/Store/ChunkPlanner.php', '1.953125 GiB', '3.90625 GiB'],
            'teleframe.php config' => [$root.'/src/Laravel/config/teleframe.php', '1.953125 GiB', '3.90625 GiB'],
            'CHANGELOG.md' => [$root.'/CHANGELOG.md', '1.953125 GiB', '3.90625 GiB'],
        ];
    }
}
