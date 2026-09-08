<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backup;

use MeRezaRezaei\Teleframe\Backup\Chunker;
use PHPUnit\Framework\TestCase;

final class ChunkerTest extends TestCase
{
    public function test_split_of_empty_data_yields_no_chunks(): void
    {
        self::assertSame([], Chunker::split('', 1024));
    }

    /**
     * @return list<array{string:int, len:int, size:int, counts:list<int>}>
     */
    public static function boundaryProvider(): array
    {
        return [
            ['len' => 1, 'size' => 1, 'counts' => [1]],
            ['len' => 5, 'size' => 1, 'counts' => [1, 1, 1, 1, 1]],
            ['len' => 8, 'size' => 8, 'counts' => [8]],
            ['len' => 9, 'size' => 8, 'counts' => [8, 1]],
            ['len' => 17, 'size' => 8, 'counts' => [8, 8, 1]],
            ['len' => 1, 'size' => 65536, 'counts' => [1]],
            ['len' => 65536, 'size' => 65536, 'counts' => [65536]],
            ['len' => 65537, 'size' => 65536, 'counts' => [65536, 1]],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('boundaryProvider')]
    public function test_split_boundaries_are_exact(int $len, int $size, array $counts): void
    {
        $data = self::data($len);
        $chunks = Chunker::split($data, $size);

        self::assertCount(count($counts), $chunks);
        foreach ($chunks as $i => $chunk) {
            self::assertSame($counts[$i], strlen($chunk['data']), "chunk {$i} size");
        }
        $cursor = 0;
        foreach ($chunks as $i => $chunk) {
            self::assertSame(substr($data, $cursor, $counts[$i]), $chunk['data'], "chunk {$i} bytes");
            $cursor += $counts[$i];
        }
    }

    /**
     * @return list<array{string:int}>
     */
    public static function invalidSizeProvider(): array
    {
        return [['size' => 0], ['size' => -1], ['size' => -65536]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidSizeProvider')]
    public function test_split_rejects_non_positive_size(int $size): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Chunker::split('data', $size);
    }

    public function test_split_hashes_are_sha256_hex_of_chunk_data(): void
    {
        $chunks = Chunker::split('abc', 2);

        self::assertSame(hash('sha256', 'ab'), $chunks[0]['hash']);
        self::assertSame(hash('sha256', 'c'), $chunks[1]['hash']);
        foreach ($chunks as $chunk) {
            self::assertSame(64, strlen($chunk['hash']));
            self::assertTrue(ctype_xdigit($chunk['hash']));
        }
    }

    public function test_identical_chunks_share_hash(): void
    {
        $chunks = Chunker::split('abab', 2);

        self::assertSame($chunks[0]['hash'], $chunks[1]['hash']);
    }

    public function test_reassemble_is_identity(): void
    {
        $data = self::data(1000);

        self::assertSame($data, Chunker::reassemble(array_column(Chunker::split($data, 64), 'data')));
    }

    public function test_reassemble_empty_chunk_list_is_empty_string(): void
    {
        self::assertSame('', Chunker::reassemble([]));
    }

    public function test_dedup_index_maps_hash_to_first_index(): void
    {
        $chunks = Chunker::split('abab', 2);

        self::assertSame(
            [Chunker::split('ab', 2)[0]['hash'] => 0],
            Chunker::dedupIndex($chunks),
        );
    }

    public function test_dedup_index_collapses_duplicates_across_many_chunks(): void
    {
        $chunks = Chunker::split('xaybxa', 2);
        $index = Chunker::dedupIndex($chunks);

        self::assertCount(2, $index);
        self::assertSame(0, $index[hash('sha256', 'xa')]);
        self::assertSame(1, $index[hash('sha256', 'yb')]);
    }

    public function test_dedup_index_on_empty_list_is_empty_map(): void
    {
        self::assertSame([], Chunker::dedupIndex([]));
    }

    private static function data(int $len): string
    {
        $out = '';
        for ($i = 0; $i < $len; $i++) {
            $out .= chr(97 + ($i % 26));
        }

        return $out;
    }
}
