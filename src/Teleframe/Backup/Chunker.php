<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Backup;

use InvalidArgumentException;

/**
 * Pure fixed-size content-addressed chunking (no I/O — bytes in, chunks
 * out). Each chunk carries the sha256 hex digest of its data; identical
 * plaintext segments therefore collide on hash, which dedupIndex collapses
 * into a hash → first-index map for upload dedup.
 */
final class Chunker
{
    /**
     * @return list<array{hash: string, data: string}>
     */
    public static function split(string $data, int $size): array
    {
        if ($size < 1) {
            throw new InvalidArgumentException('chunk size must be >= 1, got ' . $size);
        }

        $chunks = [];
        $len = strlen($data);
        for ($offset = 0; $offset < $len; $offset += $size) {
            $block = substr($data, $offset, $size);
            $chunks[] = ['hash' => hash('sha256', $block), 'data' => $block];
        }

        return $chunks;
    }

    /**
     * @param list<string> $chunks
     */
    public static function reassemble(array $chunks): string
    {
        return implode('', $chunks);
    }

    /**
     * @param list<array{hash: string, data: mixed}> $chunks
     * @return array<string, int>
     */
    public static function dedupIndex(array $chunks): array
    {
        $index = [];
        foreach ($chunks as $i => $chunk) {
            if (!array_key_exists($hash = $chunk['hash'], $index)) {
                $index[$hash] = $i;
            }
        }

        return $index;
    }
}
