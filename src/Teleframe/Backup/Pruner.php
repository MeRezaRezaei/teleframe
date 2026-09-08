<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Backup;

/**
 * Chunk garbage collection (night W1-3, P4-carried M): files deleted
 * between backup runs drop their chunks out of every manifest file
 * list, but the vault kept them forever — a shrinking set never
 * shrank its channel. Prune closes that: given the LATEST manifest's
 * chunk_hashes as the keep-set, every chunk-shaped vault entry outside
 * it is deleted.
 *
 * "Chunk-shaped" is exactly 64 lowercase hex chars (sha256 content
 * addresses as Chunker emits them) — checked with strlen/strspn, no
 * regex (src/ is regex-free by policy). Anything else (manifest
 * messages, foreign traffic) is structurally immune, and delete() on
 * the vault is idempotent, so repeated prunes are no-ops.
 *
 * The inventory walk is duck-typed: a vault exposing listAllEntries()
 * (TelegramVault — an UNCAPPED getHistory pagination, because its
 * findMessagesByName is capped at SEARCH_LIMIT and would hide orphans
 * beyond the newest ~100 messages) gets the full walk; every other
 * VaultInterface implementation (InMemoryVault, test fakes) falls back
 * to findMessagesByName(''), which is already a complete listing.
 */
final class Pruner
{
    private const CHUNK_HASH_LENGTH = 64;

    private const HEX_DIGITS = '0123456789abcdef';

    /**
     * @param array<mixed> $keepHashes chunk_hashes of the latest manifest —
     *                                 raw manifest data, so non-string
     *                                 entries are ignored rather than trusted
     *
     * @return array{scanned: int, pruned: int} scanned counts EVERY vault entry walked
     */
    public static function prune(VaultInterface $vault, array $keepHashes): array
    {
        $keep = [];
        foreach ($keepHashes as $hash) {
            if (is_string($hash)) {
                $keep[$hash] = true;
            }
        }

        $inventory = method_exists($vault, 'listAllEntries')
            ? $vault->listAllEntries()
            : $vault->findMessagesByName('');

        $scanned = 0;
        $pruned = 0;
        foreach ($inventory as $entry) {
            $scanned++;
            $name = $entry['name'] ?? null;

            if (!is_string($name) || !self::isChunkName($name) || isset($keep[$name])) {
                continue;
            }

            $vault->delete($name);
            $pruned++;
        }

        return ['scanned' => $scanned, 'pruned' => $pruned];
    }

    /** sha256 hex content address: exactly 64 lowercase hex chars (string functions only). */
    private static function isChunkName(string $name): bool
    {
        return strlen($name) === self::CHUNK_HASH_LENGTH
            && strspn($name, self::HEX_DIGITS) === self::CHUNK_HASH_LENGTH;
    }
}
