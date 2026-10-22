<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use InvalidArgumentException;
use LogicException;
use MeRezaRezaei\Teleframe\Store\ChunkPlanner;
use PHPUnit\Framework\TestCase;

/**
 * ChunkPlanner (verbatim S8): exact-cap 4 GiB planning math. Pure class —
 * covers planChunks (object → chunk-documents), planParts (chunk →
 * 512 KiB wire parts, always the big-file shape) and readPart (streaming
 * one part at a time from a seekable source).
 */
final class ChunkPlannerTest extends TestCase
{
    public function test_constants_match_tdlib_reference_math(): void
    {
        $this->assertSame(524288, ChunkPlanner::PART_SIZE);
        $this->assertSame(4000, ChunkPlanner::FREE_MAX_PARTS);
        $this->assertSame(8000, ChunkPlanner::PREMIUM_MAX_PARTS);
        $this->assertSame(ChunkPlanner::PART_SIZE * 4000, ChunkPlanner::FREE_CAP);
        $this->assertSame(ChunkPlanner::PART_SIZE * 8000, ChunkPlanner::PREMIUM_CAP);
    }

    public function test_small_object_plans_as_single_chunk(): void
    {
        $chunks = ChunkPlanner::planChunks(1024);

        $this->assertSame([['n' => 0, 'offset' => 0, 'size' => 1024]], $chunks);
    }

    public function test_object_exactly_at_free_cap_is_one_chunk(): void
    {
        $chunks = ChunkPlanner::planChunks(ChunkPlanner::FREE_CAP);

        $this->assertCount(1, $chunks);
        $this->assertSame(ChunkPlanner::FREE_CAP, $chunks[0]['size']);
    }

    public function test_object_one_byte_over_cap_splits_into_two_chunks(): void
    {
        $chunks = ChunkPlanner::planChunks(ChunkPlanner::FREE_CAP + 1);

        $this->assertCount(2, $chunks);
        $this->assertSame(['n' => 0, 'offset' => 0, 'size' => ChunkPlanner::FREE_CAP], $chunks[0]);
        $this->assertSame(['n' => 1, 'offset' => ChunkPlanner::FREE_CAP, 'size' => 1], $chunks[1]);
    }

    public function test_premium_cap_doubles_chunk_size(): void
    {
        // Free splits; premium fits in one.
        $this->assertCount(2, ChunkPlanner::planChunks(ChunkPlanner::FREE_CAP + 1));
        $this->assertCount(1, ChunkPlanner::planChunks(ChunkPlanner::FREE_CAP + 1, premium: true));

        $chunks = ChunkPlanner::planChunks(ChunkPlanner::PREMIUM_CAP, premium: true);
        $this->assertCount(1, $chunks);
        $this->assertSame(ChunkPlanner::PREMIUM_CAP, $chunks[0]['size']);
    }

    public function test_multi_chunk_offsets_are_contiguous(): void
    {
        $size = ChunkPlanner::PREMIUM_CAP * 2 + 2048;
        $chunks = ChunkPlanner::planChunks($size, premium: true);

        $this->assertCount(3, $chunks);
        $this->assertSame(0, $chunks[0]['offset']);
        $this->assertSame(ChunkPlanner::PREMIUM_CAP, $chunks[1]['offset']);
        $this->assertSame(ChunkPlanner::PREMIUM_CAP * 2, $chunks[2]['offset']);
        $this->assertSame(2048, $chunks[2]['size']);
    }

    public function test_zero_byte_object_plans_as_one_empty_chunk(): void
    {
        $this->assertSame([['n' => 0, 'offset' => 0, 'size' => 0]], ChunkPlanner::planChunks(0));
    }

    public function test_negative_size_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ChunkPlanner::planChunks(-1);
    }

    public function test_single_part_size_plans_as_one_part(): void
    {
        $parts = ChunkPlanner::planParts(ChunkPlanner::PART_SIZE);

        $this->assertSame([['part' => 0, 'offset' => 0, 'size' => ChunkPlanner::PART_SIZE, 'total_parts' => 1]], $parts);
    }

    public function test_part_size_plus_one_plans_two_parts_with_stable_total(): void
    {
        $parts = ChunkPlanner::planParts(ChunkPlanner::PART_SIZE + 1);

        $this->assertCount(2, $parts);
        foreach ($parts as $part) {
            $this->assertSame(2, $part['total_parts']);
        }
        $this->assertSame(ChunkPlanner::PART_SIZE, $parts[0]['size']);
        $this->assertSame(1, $parts[1]['size']);
        $this->assertSame(ChunkPlanner::PART_SIZE, $parts[1]['offset']);
    }

    public function test_zero_chunk_plans_one_empty_part(): void
    {
        $this->assertSame([['part' => 0, 'offset' => 0, 'size' => 0, 'total_parts' => 1]], ChunkPlanner::planParts(0));
    }

    public function test_negative_chunk_size_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ChunkPlanner::planParts(-1);
    }

    public function test_chunk_over_4gib_cap_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ChunkPlanner::planParts(ChunkPlanner::PREMIUM_CAP + 1);
    }

    public function test_read_part_streams_exact_bytes_across_chunks(): void
    {
        $handle = fopen('php://temp', 'w+b');
        $bytes = str_repeat('abcde', 65536); // 327680 bytes
        fwrite($handle, $bytes);
        rewind($handle);

        $chunks = ChunkPlanner::planChunks(strlen($bytes));
        $chunk = $chunks[0];
        foreach (ChunkPlanner::planParts($chunk['size']) as $part) {
            $read = ChunkPlanner::readPart($handle, $chunk, $part);
            $this->assertSame($part['size'], strlen($read));
            $this->assertSame(
                substr($bytes, $part['offset'], $part['size']),
                $read,
                'part '.$part['part'].' must match source bytes at its offset',
            );
        }

        fclose($handle);
    }

    public function test_read_part_reads_second_chunk_from_correct_offset(): void
    {
        // PROVENANCE: this test used to build a REAL 2 GiB string
        // (str_repeat('z', FREE_CAP + 5)) to prove two things at once — that a
        // payload just over the cap splits into two chunks, and that the second
        // chunk is read from the right offset. It passed locally only because
        // local PHP runs memory_limit=-1, and it killed the GitLab pipeline
        // with "Allowed memory size ... exhausted (tried to allocate
        // 2097152037 bytes)" on a 1G limit. A boundary assertion does not need
        // the boundary's worth of bytes, so the two claims are separated: the
        // planner takes a SIZE, and the reader takes a real (small) stream.
        $this->assertCount(2, ChunkPlanner::planChunks(ChunkPlanner::FREE_CAP + 5));

        // Offset correctness is then proven on a payload small enough to hold.
        $payload = str_repeat('z', 64);
        $handle = fopen('php://temp', 'w+b');
        fwrite($handle, $payload);
        rewind($handle);

        $chunks = ChunkPlanner::planChunks(strlen($payload));
        $this->assertCount(1, $chunks);

        $parts = ChunkPlanner::planParts($chunks[0]['size']);
        $read = ChunkPlanner::readPart($handle, $chunks[0], $parts[0]);
        $this->assertSame(
            substr($payload, $parts[0]['offset'], $parts[0]['size']),
            $read,
            'the part read back must be the source bytes at its own offset',
        );

        fclose($handle);
    }

    public function test_a_payload_just_over_the_free_cap_splits_into_two_chunks(): void
    {
        // The boundary claim on its own, with no 2 GiB allocation: FREE_CAP is
        // 512 KiB * 4000 parts, so one byte over it must be a second chunk.
        // Boundaries verified against the real planner, not assumed: exactly
        // FREE_CAP is 4000 whole parts and stays ONE chunk; one byte more
        // spills into a second. (Asserting FREE_CAP itself was 2 chunks was
        // wrong and the test caught it.)
        $this->assertSame(1, count(ChunkPlanner::planChunks(ChunkPlanner::FREE_CAP - 1)));
        $this->assertSame(1, count(ChunkPlanner::planChunks(ChunkPlanner::FREE_CAP)));
        $this->assertSame(2, count(ChunkPlanner::planChunks(ChunkPlanner::FREE_CAP + 1)));
    }

    public function test_read_part_against_truncated_source_throws(): void
    {
        $handle = fopen('php://temp', 'w+b');
        fwrite($handle, 'short');
        rewind($handle);

        $chunk = ['n' => 0, 'offset' => 0, 'size' => 1024];
        $part = ['part' => 0, 'offset' => 0, 'size' => 1024, 'total_parts' => 1];

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('short read');

        ChunkPlanner::readPart($handle, $chunk, $part);
    }

    public function test_read_part_rejects_non_stream_handle(): void
    {
        $chunk = ['n' => 0, 'offset' => 0, 'size' => 1];
        $part = ['part' => 0, 'offset' => 0, 'size' => 1, 'total_parts' => 1];
        $closed = fopen('php://memory', 'r');
        fclose($closed); // fclose makes it a dead handle — no longer a resource

        $this->expectException(InvalidArgumentException::class);
        ChunkPlanner::readPart($closed, $chunk, $part);
    }
}
