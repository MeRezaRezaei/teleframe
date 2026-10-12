<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store;

use InvalidArgumentException;
use LogicException;

/**
 * Chunk manager (verbatim S8): exact-cap math for Telegram uploads.
 *
 * Telegram documents are uploaded in 512 KiB parts (saveFilePart /
 * saveBigFilePart). The part count is limited (4000 free / 8000 premium
 * per TDLib's reference math), which caps one document at:
 *
 *   FREE_CAP    = 524288 * 4000 = 2097152000 bytes = 1.953125 GiB
 *   PREMIUM_CAP = 524288 * 8000 = 4194304000 bytes = 3.90625 GiB
 *
 * NEITHER is a round number of GiB — 512 KiB does not divide 1 GiB, so
 * the usual round-GiB phrasing for these caps is false by ~2.3% and must
 * not reappear. tests/Store/ChunkArithmeticTest.php pins these byte
 * values against this file, the shipped config, and CHANGELOG.md.
 *
 * THE DEFAULT IS FREE_CAP. `planChunks()`'s $premium parameter defaults
 * to false and the shipped `teleframe.tstore.premium` config key
 * defaults to false, so a default install chunks at 2097152000 bytes,
 * NOT at the premium cap. A host that wants PREMIUM_CAP must set
 * TELEFRAME_TSTORE_PREMIUM=1 (or bind premium: true), because the
 * account behind that config is a premium one — a free account is
 * rejected by Telegram at the part-count limit, so the default is
 * deliberately the cap that always works.
 *
 * Objects larger than the account cap split into multiple
 * chunk-documents of exactly the cap size (the last one short); each
 * chunk-document is its own Telegram document uploaded via the
 * always-big path (total_parts is known up front, so saveBigFilePart +
 * inputFileBig is always correct and avoids the 10 MB small-file
 * threshold guesswork).
 *
 * The planner is pure: it computes offsets/sizes and reads nothing. A
 * full-cap chunk is NEVER buffered — callers iterate the plan and
 * stream part-sized slices from a seekable source (see readPart).
 */
final class ChunkPlanner
{
    public const PART_SIZE = 524288; // 512 KiB, TDLib MAX_PART_SIZE

    public const FREE_MAX_PARTS = 4000;

    public const PREMIUM_MAX_PARTS = 8000;

    public const FREE_CAP = self::PART_SIZE * self::FREE_MAX_PARTS; // 2097152000 bytes = 1.953125 GiB (the default)

    public const PREMIUM_CAP = self::PART_SIZE * self::PREMIUM_MAX_PARTS; // 4194304000 bytes = 3.90625 GiB (opt-in)

    /**
     * Plan how a $size-byte object splits into chunk-documents under the
     * account cap. A zero-byte object plans as ONE empty chunk so an
     * empty file is still representable as a (degenerate) document.
     *
     * @return list<array{n: int, offset: int, size: int}> chunk index, byte offset into the object, chunk byte size (last may be short)
     */
    public static function planChunks(int $size, bool $premium = false): array
    {
        if ($size < 0) {
            throw new InvalidArgumentException('size must be >= 0');
        }

        $cap = $premium ? self::PREMIUM_CAP : self::FREE_CAP;

        if ($size === 0) {
            return [['n' => 0, 'offset' => 0, 'size' => 0]];
        }

        $chunks = [];
        $offset = 0;
        $n = 0;
        while ($offset < $size) {
            $chunkSize = min($cap, $size - $offset);
            $chunks[] = ['n' => $n, 'offset' => $offset, 'size' => $chunkSize];
            $offset += $chunkSize;
            $n++;
        }

        return $chunks;
    }

    /**
     * Plan the 512 KiB upload parts of one chunk-document. Always the
     * big-file shape: a total_parts count is known for every part, which
     * is what upload.saveBigFilePart requires. A zero-size chunk plans
     * as a single empty part (Telegram requires at least one part).
     *
     * @return list<array{part: int, offset: int, size: int, total_parts: int}> offsets are relative to the chunk start
     */
    public static function planParts(int $chunkSize): array
    {
        if ($chunkSize < 0) {
            throw new InvalidArgumentException('chunkSize must be >= 0');
        }

        if ($chunkSize > self::PREMIUM_CAP) {
            throw new InvalidArgumentException(
                'chunkSize '.$chunkSize.' exceeds the document cap of '.self::PREMIUM_CAP
                .' bytes (3.90625 GiB) — split first via planChunks',
            );
        }

        if ($chunkSize === 0) {
            return [['part' => 0, 'offset' => 0, 'size' => 0, 'total_parts' => 1]];
        }

        $total = intdiv($chunkSize + self::PART_SIZE - 1, self::PART_SIZE);

        $parts = [];
        $offset = 0;
        $part = 0;
        while ($offset < $chunkSize) {
            $size = min(self::PART_SIZE, $chunkSize - $offset);
            $parts[] = ['part' => $part, 'offset' => $offset, 'size' => $size, 'total_parts' => $total];
            $offset += $size;
            $part++;
        }

        return $parts;
    }

    /**
     * Read one planned part from a seekable stream without ever holding
     * more than one part in memory. The handle is fseek'd to the chunk
     * offset + part offset and read for exactly the planned size.
     *
     * @param  resource  $handle  seekable stream (fopen'd file, php://temp, ...)
     * @param  array{n: int, offset: int, size: int}  $chunk
     * @param  array{part: int, offset: int, size: int, total_parts: int}  $part
     */
    public static function readPart($handle, array $chunk, array $part): string
    {
        if (! is_resource($handle)) {
            throw new InvalidArgumentException('handle must be a stream resource');
        }

        $seekTo = $chunk['offset'] + $part['offset'];
        if (fseek($handle, $seekTo) === -1) {
            throw new LogicException('source stream is not seekable — TStore requires seekable sources');
        }

        $bytes = stream_get_contents($handle, $part['size']);
        if ($bytes === false) {
            throw new LogicException('short read from source stream at offset '.$seekTo);
        }

        if (strlen($bytes) !== $part['size']) {
            throw new LogicException(
                'short read from source stream: expected '.$part['size'].' bytes, got '.strlen($bytes),
            );
        }

        return $bytes;
    }
}
