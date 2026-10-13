<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backup;

/**
 * A CONFORMANT `listManifestChunkHashes()` for a vault double that keeps a
 * SINGLE manifest.
 *
 * WHY A TRAIT. The interface method exists so a prune can build a retention
 * keep-set spanning more than the newest generation. A double that answers it
 * wrongly is worse than one that refuses to answer: a double returning `[]`
 * for the newest generation would report a keep-set NARROWER than the
 * manifest the test just handed it, so a prune in that test would delete
 * live chunks and the test would be pinning a bug. This answers from the
 * manifest the double actually holds — the same `getLatestManifest()` its
 * other methods already serve — so the newest row is always there.
 *
 * It reports ONE row because this double keeps a single manifest history it
 * never populates: inventing older generations would be a fixture lying about
 * the vault. The doubles that model real history answer for real — the
 * decorators (SelectiveVault, CorruptingVault) DELEGATE to the inner vault
 * rather than using this, so wrapping a multi-generation vault does not
 * flatten its history — and the retention behaviour itself is tested against
 * the PRODUCTION vaults (`InMemoryVault`) and through the real
 * `teleframe:backup prune` command in BackupCommandTest, not against a
 * fixture's own idea of what a manifest list looks like.
 *
 * The ANONYMOUS class in BackupStalenessTest carries its own inline copy of
 * this body — a fixture defined inside a method body cannot import a named
 * trait without colliding with the enclosing file's `use` statements.
 */
trait RetentionStubTrait
{
    /**
     * The manifest this trait answers from. Declared abstract so the trait is
     * type-checked against its host class rather than calling a method the
     * analyser cannot see — and so a host that does not implement it fails at
     * bind time instead of answering `[]`.
     *
     * @return array<string, mixed>|null
     */
    abstract public function getLatestManifest(): ?array;

    /**
     * @return list<array{generation: int, created: string, chunk_hashes: list<string>}>
     */
    public function listManifestChunkHashes(int $generations = 2): array
    {
        $manifest = $this->getLatestManifest();
        if (! is_array($manifest)) {
            return [];
        }

        $hashes = $manifest['chunk_hashes'] ?? [];
        $hashes = is_array($hashes) ? array_values(array_filter($hashes, 'is_string')) : [];

        return [[
            'generation' => is_int($manifest['generation'] ?? null) ? $manifest['generation'] : 0,
            'created' => is_string($manifest['created'] ?? null) ? $manifest['created'] : 'unknown',
            'chunk_hashes' => $hashes,
        ]];
    }
}
